<?php
/*
 * e107 website system
 *
 * racetiming plugin - checkpoint timing engine (PART A: compute + read).
 *
 * Pure read/compute over the race_time table ONLY. NO ranking, NO HTML, NO
 * pages, NO writes, NO result-freeze, NO terminovka. Ranking/standings and all
 * presentation live in racereports; the legacy result/archive freezes are out of
 * scope and stay in timetracker/terminovka untouched.
 *
 * This consolidates the legacy time-computation that was copy-pasted across
 * timetracker's math classes. Method names deliberately drop the legacy
 * misnomers; each maps to the legacy it replaces:
 *
 *   parseCrossingTime()  <- timetrackerMain::ISO8601ToMicrotime  (Main:62)
 *   buildSplits()        <- the duplicated load loops
 *                           timetracker::__construct :65-84 AND
 *                           timetracker::get_points_times :559-580
 *   elapsedToPoint()     <- core of timetracker::get_racer_time_on_point :248-310
 *   timeOfDay()          <- get_racer_start_time / get_starttime_from_point
 *                           (timetrackerStart_class.php:9-43) + ISO8601ToMicrotime
 *   formatTimeOfDay()    <- timetrackerMain::microtimeToSeconds (Main:92-121); a
 *                           TIME-OF-DAY clock string, NOT a duration. NOTE it
 *                           ROUNDS the ms field where formatElapsed TRUNCATES -
 *                           different legacy formatters (see formatTimeOfDay).
 *   pointStatus()        <- the magic return values (-1 / '' / '---' / '-') and
 *                           the get_dnf_racers/get_dsq_racers lookups; the legacy
 *                           'DSG' typo is fixed here to DSQ (see NOTES.md).
 *
 * SECURITY: every query goes through the e107 db class with $tp->toDB() applied
 * to any value placed in SQL. The start/bib number is ALWAYS a string
 * (race_time_racer_number VARCHAR) and is never (int)-cast - leading zeros are
 * data. is_numeric() guards every stored-time consumption (PHP-8 safe): real
 * data legitimately stores a non-numeric 'DNF' marker at one point with valid
 * times at later points, so the engine never assumes complete / monotonic /
 * numeric data.
 */

class race_clock
{
	const STATUS_OK          = 'OK';
	const STATUS_DNF         = 'DNF';
	const STATUS_DSQ         = 'DSQ';
	const STATUS_DNS         = 'DNS';
	const STATUS_NO_START    = 'NO_START';
	const STATUS_NO_CROSSING = 'NO_CROSSING';

	/** @var array map[number][point] = ['savedtime'=>?float,'ended'=>string,'crossing_id'=>?int, ...] */
	protected $splits = array();

	/** @var array set of racer numbers flagged DNF anywhere (keyed by number) */
	protected $dnf = array();

	/** @var array set of racer numbers flagged DSQ anywhere (keyed by number) */
	protected $dsq = array();

	/** @var bool */
	protected $built = false;

	// ------------------------------------------------------------------ build

	/**
	 * Load all crossings + the DNF/DSQ sets from race_time and build the split
	 * map. One DB pass for crossings (replacing the two duplicated legacy
	 * loops). Returns $this for chaining.
	 */
	public function build()
	{
		$crossings   = $this->loadCrossings();
		$this->splits = self::buildSplits($crossings);
		$this->dnf    = $this->fetchEndedSet('DNF');
		$this->dsq    = $this->fetchEndedSet('DSQ');
		$this->built  = true;

		return $this;
	}

	public function isBuilt()
	{
		return $this->built;
	}

	// --------------------------------------------------------- DB read (only)

	/**
	 * The single crossing SELECT, identical to the legacy load
	 * (timetracker_class.php:57-58 / :553-554): every crossing, ordered so that
	 * for a duplicate (number,point) the LATEST crossing is processed last and
	 * therefore wins in buildSplits().
	 *
	 * race_time_id is selected ADDITIVELY (the legacy load did not read it) so
	 * buildSplits() can carry the crossing IDENTITY of each (number,point) cell -
	 * the row the report's admin edit link targets (crossingId()). It does NOT
	 * affect the time/ended computation; the existing time accessors are unchanged.
	 *
	 * No user input is interpolated, so there is nothing to parametrise here;
	 * the legacy raw-$_GET concatenation lived only in the report pages and is
	 * NOT carried over (see racereports pages / NOTES.md).
	 *
	 * @return array raw rows; race_time_racer_number kept as string
	 */
	public function loadCrossings()
	{
		$sql   = e107::getDb();
		$query = "SELECT race_time_id, race_time_point, race_time_racer_number, race_time_time, race_time_ended
			FROM " . MPREFIX . "race_time AS rt
			WHERE rt.race_time_point != ''
			ORDER BY race_time_racer_number, race_time_time";

		$rows = $sql->retrieve($query, true);

		return is_array($rows) ? $rows : array();
	}

	/**
	 * Set of racer numbers carrying a given ended flag anywhere in race_time.
	 * Replaces get_dnf_racers/get_dsq_racers (timetracker_class.php:136-154 /
	 * :117-134). The legacy LEFT JOIN racer was only used to fetch racer columns
	 * the callers ignored for membership; here we read race_time alone and key by
	 * race_time_racer_number (string). The flag is passed through $tp->toDB().
	 *
	 * @param string $flag e.g. 'DNF' or 'DSQ'
	 * @return array keyed set [number => true]
	 */
	public function fetchEndedSet($flag)
	{
		$tp   = e107::getParser();
		$sql  = e107::getDb();
		$flag = $tp->toDB($flag);

		$query = "SELECT race_time_racer_number
			FROM " . MPREFIX . "race_time
			WHERE race_time_ended = '" . $flag . "'
			GROUP BY race_time_racer_number";

		$rows = $sql->retrieve($query, true);

		$set = array();
		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$set[(string) $r['race_time_racer_number']] = true;
			}
		}

		return $set;
	}

	// --------------------------------------------------------- pure compute

	/**
	 * Parse a stored crossing string into an epoch float.
	 *
	 * Uses DateTime::createFromFormat('Y-m-d H:i:s.v', $s) - the stored format
	 * (producer timetracker_class.php:447, reader timetrackerMain_class.php:72) -
	 * with fallbacks 'Y-m-d H:i:s' and 'H:i:s.v'. NOT strtotime(), NOT
	 * new DateTime(), NOT DateTime::ATOM. Replaces ISO8601ToMicrotime (the name
	 * is a double misnomer and is dropped).
	 *
	 * Returns null on empty/invalid (the legacy returned 0; null is the clean
	 * equivalent - both flow to NO_START / NO_CROSSING downstream).
	 *
	 * Timezone is intentionally left at the server default to match legacy
	 * (elapsed = difference of two same-zone crossings is timezone-safe). Pinning
	 * UTC is a post-parity intentional-fix candidate (NOTES.md D-10).
	 *
	 * @param string|null $s
	 * @return float|null
	 */
	public static function parseCrossingTime($s)
	{
		if ($s === null)
		{
			return null;
		}

		$s = trim((string) $s);

		if ($s === '')
		{
			return null;
		}
		// The legacy placeholder sentinel that means "no time".
		if ($s === '202x-mm-dd 00:00:00,000')
		{
			return null;
		}

		$dt = DateTime::createFromFormat('Y-m-d H:i:s.v', $s);
		if ($dt === false)
		{
			$dt = DateTime::createFromFormat('Y-m-d H:i:s', $s);
			if ($dt === false)
			{
				$dt = DateTime::createFromFormat('H:i:s.v', $s);
				if ($dt === false)
				{
					return null;
				}
			}
		}

		return (float) $dt->format('U.v');
	}

	/**
	 * Build the split map from raw crossing rows. ONE loader replacing the two
	 * byte-identical legacy loops (timetracker_class.php:65-84 and :559-580).
	 *
	 *   map[number][point] = ['savedtime'=>?float, 'ended'=>string, ...]
	 *
	 * - latest-crossing-wins per (number,point): rows arrive ORDER BY number,
	 *   time, so a later assignment overwrites an earlier one for the same point;
	 * - the per-racer `ended` flag is propagated forward exactly as legacy: it is
	 *   reset to '' when the racer number changes, then set to the row's
	 *   race_time_ended once any row carries one, and stamped onto every cell as
	 *   of that row's processing order. (This is the real-data Cinko case: a
	 *   mid-race 'DNF' marker propagates to the racer's later crossings.)
	 * - an entry is created even when the crossing time is unpar. a row with an
	 *   invalid/empty time still marks "this racer has a row at this point"
	 *   (savedtime = null), which the online report's furthest-point bucketing
	 *   relies on (isset on the map cell).
	 * - crossing_id carries the race_time_id of the (number,point) cell ADDITIVELY
	 *   (crossing identity, used by the report's admin edit link). Where TIME follows
	 *   latest-crossing-wins (above), the ID is kept DETERMINISTICALLY as the LOWEST
	 *   race_time_id for that (number,point): the legacy point page looked the row up
	 *   with `... LIMIT 1` and NO ORDER BY, which returns physical/PK order (the
	 *   first-inserted = lowest id). So for a duplicate (number,point) the displayed
	 *   time is the latest crossing while the edit link targets the lowest-id row -
	 *   matching what the legacy LIMIT 1 lookup resolved to. Data shouldn't have
	 *   duplicates, but is untrusted, so the choice is made explicit and stable.
	 *
	 * $points (the checkpoint list) is accepted for API symmetry but, like the
	 * legacy loops, the map is built directly from the crossings; it is not used
	 * to restrict or pre-seed the map.
	 *
	 * @param array $crossings raw race_time rows (string numbers)
	 * @param array $points    optional checkpoint list (unused; see above)
	 * @return array
	 */
	public static function buildSplits(array $crossings, array $points = array())
	{
		$map   = array();
		$ended = array();
		$id1   = null;

		foreach ($crossings as $row)
		{
			$number = (string) $row['race_time_racer_number'];

			if ($id1 !== $number)
			{
				$id1         = $number;
				$ended[$id1] = '';
			}

			$point     = (string) $row['race_time_point'];
			$savedtime = self::parseCrossingTime(isset($row['race_time_time']) ? $row['race_time_time'] : null);

			if (!empty($row['race_time_ended']))
			{
				$ended[$id1] = (string) $row['race_time_ended'];
			}

			// Crossing identity, carried ADDITIVELY: keep the LOWEST race_time_id for
			// this (number,point) (legacy LIMIT 1 / no ORDER BY -> lowest id). Unlike
			// the time fields this does NOT follow latest-crossing-wins.
			$rowId  = isset($row['race_time_id']) ? (int) $row['race_time_id'] : null;
			$keepId = $rowId;
			if (isset($map[$id1][$point]['crossing_id']) && $map[$id1][$point]['crossing_id'] !== null)
			{
				$existing = (int) $map[$id1][$point]['crossing_id'];
				if ($rowId === null || $existing < $rowId)
				{
					$keepId = $existing;
				}
			}

			$map[$id1][$point] = array(
				'savedtime'              => $savedtime,
				'ended'                  => $ended[$id1],
				'crossing_id'            => $keepId,
				'race_time_racer_number' => $number,
				'race_time_point'        => $point,
			);
		}

		return $map;
	}

	// ---------------------------------------------------------- accessors

	/**
	 * Parsed epoch float of a racer's crossing at a point, or null if there is
	 * no (parsed) crossing there.
	 *
	 * @param string $number
	 * @param string $point
	 * @return float|null
	 */
	public function savedTime($number, $point)
	{
		$number = (string) $number;

		if (isset($this->splits[$number][$point]['savedtime']))
		{
			return $this->splits[$number][$point]['savedtime'];
		}

		return null;
	}

	/**
	 * Raw TIME-OF-DAY (wall-clock epoch float, full ms precision) of a racer's
	 * crossing at a point, or null when there is no usable crossing there.
	 *
	 * This is the clean replacement for the legacy start-time read chain
	 * get_racer_start_time / get_starttime_from_point -> ISO8601ToMicrotime ->
	 * microtimeToSeconds (timetrackerStart_class.php:9-43, timetrackerMain
	 * _class.php:62-121). Unlike elapsedToPoint() this is the ABSOLUTE crossing
	 * time, NOT a duration: the start report's "Čas štartu" column needs the
	 * raw moment the racer crossed, not the elapsed-since-start.
	 *
	 * Reuses the same parseCrossingTime() + buildSplits() loader as every other
	 * accessor (no second parser). Defensive: a non-numeric / 'DNF' / sentinel /
	 * missing crossing all flow to null via savedTime() (the engine never assumes
	 * complete data). $number stays a STRING.
	 *
	 * @param string $number
	 * @param string $point
	 * @return float|null
	 */
	public function timeOfDay($number, $point)
	{
		$epoch = $this->savedTime((string) $number, $point);

		if (!is_numeric($epoch) || $epoch <= 0)
		{
			return null;
		}

		return (float) $epoch;
	}

	/**
	 * The propagated ended flag stamped on a racer's cell at a point ('' when
	 * none, or the stored marker e.g. 'DNF'/'DSQ').
	 *
	 * @param string $number
	 * @param string $point
	 * @return string
	 */
	public function endedAt($number, $point)
	{
		$number = (string) $number;

		if (isset($this->splits[$number][$point]['ended']))
		{
			return (string) $this->splits[$number][$point]['ended'];
		}

		return '';
	}

	/**
	 * Does this racer have a row at this point at all (valid OR invalid time)?
	 * Mirrors the legacy `isset($item[$code])` furthest-point test.
	 */
	public function hasRowAt($number, $point)
	{
		$number = (string) $number;

		return isset($this->splits[$number][$point]);
	}

	/** Does this racer have a usable (numeric, > 0) crossing time at this point? */
	public function hasCrossingAt($number, $point)
	{
		$t = $this->savedTime($number, $point);

		return is_numeric($t) && $t > 0;
	}

	/**
	 * The race_time_id of this racer's crossing at a point, or null when there is
	 * no row at the point. This is crossing IDENTITY (the stored row's PK), NOT
	 * presentation - the report's admin edit link uses it to open the racetiming
	 * race_time admin on the exact row.
	 *
	 * A row carries an id even when its time is unusable (an unparseable / 'DNF'
	 * marker row still has a PK), so an ended/waiting racer that DOES have a stored
	 * crossing here returns its id; a racer with no row here returns null.
	 *
	 * For a duplicate (number,point) - data is untrusted - the LOWEST race_time_id
	 * is returned (set in buildSplits), matching the legacy point page's
	 * `SELECT race_time_id ... LIMIT 1` (no ORDER BY -> PK order -> lowest id).
	 * The start/bib number stays a STRING (never (int)-cast - leading zeros are
	 * data); the point is keyed by race_time_point as elsewhere.
	 *
	 * @param string $number
	 * @param string|mixed $point
	 * @return int|null
	 */
	public function crossingId(string $number, $point): ?int
	{
		$number = (string) $number;

		if (isset($this->splits[$number][$point]['crossing_id'])
			&& $this->splits[$number][$point]['crossing_id'] !== null)
		{
			return (int) $this->splits[$number][$point]['crossing_id'];
		}

		return null;
	}

	public function hasStart($number)
	{
		return $this->hasCrossingAt($number, 'start');
	}

	public function isDnf($number)
	{
		return isset($this->dnf[(string) $number]);
	}

	public function isDsq($number)
	{
		return isset($this->dsq[(string) $number]);
	}

	/** @var array per-flag map [flag => [number => point]] of the ended-marker point, lazily built */
	protected $endedPointMap = array();

	/**
	 * The checkpoint code at which a racer carries the given ended marker - i.e.
	 * the race_time_point of the race_time row whose race_time_ended = $flag. This
	 * is the value the legacy archive report read as
	 * `self::$dnf_racers[$number]['race_time_point']` (get_dnf_racers /
	 * get_dsq_racers, timetracker_class.php:136-154): the FURTHEST-point /
	 * blank-after-DNF logic of the full results matrix needs the marker's point,
	 * which the propagated `ended` flag in the split map cannot recover (it is
	 * stamped onto every later cell, losing which cell was the marker).
	 *
	 * ADDITIVE: this is a NEW, read-only accessor. It does NOT touch build(),
	 * buildSplits(), the split map, the DNF/DSQ membership sets, or any existing
	 * method - those are unchanged. It performs ONE race_time read per flag
	 * (lazily, cached), mirroring the legacy GROUP BY race_time_racer_number pick:
	 * for the realistic one-marker-per-racer case the grouped race_time_point is
	 * unambiguous. Engine reads race_time ONLY (no racer join); the report already
	 * scopes the lookup to its own race's bibs, so the global read is consistent
	 * with how isDnf()/isDsq() (and the whole engine) already work.
	 *
	 * The flag is sanitised via $tp->toDB(); the number stays a STRING (never
	 * (int)-cast - leading zeros are data). Returns null when the racer carries no
	 * such marker.
	 *
	 * @param string $number
	 * @param string $flag e.g. 'DNF' or 'DSQ' (default 'DNF')
	 * @return string|null
	 */
	public function endedPoint($number, $flag = 'DNF')
	{
		$number = (string) $number;
		$flag   = (string) $flag;

		if (!isset($this->endedPointMap[$flag]))
		{
			$this->endedPointMap[$flag] = $this->fetchEndedPointMap($flag);
		}

		return isset($this->endedPointMap[$flag][$number])
			? $this->endedPointMap[$flag][$number]
			: null;
	}

	/**
	 * Build the [number => point] map of the ended-marker point for one flag, in a
	 * single race_time read. Mirrors the legacy get_dnf_racers/get_dsq_racers query
	 * shape (GROUP BY race_time_racer_number) so the grouped race_time_point picked
	 * here is the SAME row the legacy archive read. The flag goes through
	 * $tp->toDB(); numbers are kept as strings.
	 *
	 * @param string $flag
	 * @return array [number => point]
	 */
	protected function fetchEndedPointMap($flag)
	{
		$tp   = e107::getParser();
		$sql  = e107::getDb();
		$flag = $tp->toDB($flag);

		$query = "SELECT race_time_racer_number, race_time_point
			FROM " . MPREFIX . "race_time
			WHERE race_time_ended = '" . $flag . "'
			GROUP BY race_time_racer_number";

		$rows = $sql->retrieve($query, true);

		$map = array();
		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$map[(string) $r['race_time_racer_number']] = (string) $r['race_time_point'];
			}
		}

		return $map;
	}

	/** All racer numbers present in the split map (strings). */
	public function racerNumbers()
	{
		return array_keys($this->splits);
	}

	// ---------------------------------------------------------- compute

	/**
	 * Raw elapsed start -> point, at full millisecond precision, IGNORING any
	 * ended flag. round($point - $start, 3, HALF_UP). null when there is no
	 * usable start crossing or no usable crossing at the point.
	 *
	 * This is the pure subtraction the legacy `actualtime > 0` branch performed
	 * (timetracker_class.php:267). The report uses it for the display time of an
	 * ended racer that nonetheless has a crossing at the point (the legacy
	 * "time DNF" cell), while elapsedToPoint() below is the ranking-safe variant.
	 *
	 * @return float|null
	 */
	public function elapsedRaw($number, $point)
	{
		$start  = $this->savedTime($number, 'start');
		$actual = $this->savedTime($number, $point);

		if (!is_numeric($start) || $start <= 0)
		{
			return null;
		}
		if (!is_numeric($actual) || $actual <= 0)
		{
			return null;
		}

		return round($actual - $start, 3, PHP_ROUND_HALF_UP);
	}

	/**
	 * Ranking-safe elapsed start -> point: the raw elapsed, but ONLY when the
	 * racer's status at that point is OK (positive time, not ended/DNF/DSQ/DNS,
	 * has a start and a crossing). null otherwise. This is the value to sort by.
	 *
	 * Display-precision is NOT applied here (that is race_format's job and is
	 * display-only); the returned float is full precision so sort order never
	 * depends on the number of displayed decimals.
	 *
	 * @return float|null
	 */
	public function elapsedToPoint($number, $point)
	{
		if ($this->pointStatus($number, $point) !== self::STATUS_OK)
		{
			return null;
		}

		return $this->elapsedRaw($number, $point);
	}

	/**
	 * Raw elapsed BETWEEN two arbitrary points (a SPLIT/segment), at full
	 * millisecond precision: savedTime(number,$pointB) - savedTime(number,$pointA),
	 * round($pb - $pa, 3, HALF_UP). null when EITHER crossing is missing/unusable
	 * (the racer is simply not in this segment).
	 *
	 * ADDITIVE: this is purely a subtraction of two existing savedTime() lookups -
	 * it introduces NO new read, NO ranking, NO state, and does not touch any
	 * existing method. It is the clean equivalent of the legacy
	 * get_racer_time_between_points() time computation (timetracker_class.php:358-414,
	 * `round($actualtime - $startpoint, 3, PHP_ROUND_HALF_UP)`, where $startpoint is
	 * the saved time at the FROM point and $actualtime the saved time at the TO
	 * point); UNLIKE that legacy method this returns ONLY the float time (or null) -
	 * the display text / DNF magic-value shape stays in the presentation layer.
	 *
	 * Like elapsedRaw() it ignores any ended flag (the difference of two stored
	 * crossings is well-defined whenever both exist) and keeps the bib a STRING.
	 * is_numeric()/>0 guards each crossing (PHP-8 safe; never assumes complete data).
	 *
	 * @param string       $number
	 * @param string|mixed $pointA the FROM point (Od)
	 * @param string|mixed $pointB the TO point (Do)
	 * @return float|null
	 */
	public function elapsedBetween(string $number, $pointA, $pointB): ?float
	{
		$pa = $this->savedTime($number, $pointA);
		$pb = $this->savedTime($number, $pointB);

		if (!is_numeric($pa) || $pa <= 0)
		{
			return null;
		}
		if (!is_numeric($pb) || $pb <= 0)
		{
			return null;
		}

		return round($pb - $pa, 3, PHP_ROUND_HALF_UP);
	}

	/**
	 * Format a TIME-OF-DAY epoch (from timeOfDay()) as an HH:MM:SS[.mmm]
	 * wall-clock string, truncated to $decimals displayed fractional digits.
	 *
	 * This is the clean replacement for the legacy start-time formatter
	 * timetrackerMain::microtimeToSeconds (timetrackerMain_class.php:92-121).
	 * That function - despite its name - emits a TIME-OF-DAY clock string
	 * (date('H:i:s', floor(epoch)) plus an rtrim'd `.mmm` / ".000" suffix), NOT a
	 * count of seconds. We reproduce it BYTE-FOR-BYTE so the swapped start-list
	 * column is visually identical.
	 *
	 * PARITY NUANCE: the legacy START-time formatter ROUNDS the millisecond field
	 * (round($micro*1000,0)), whereas race_format::formatElapsed (the DURATION
	 * formatter, mirroring secondsToTime) TRUNCATES. They are different legacy
	 * functions. Because the absolute epoch carries float residue
	 * (format('U.v') of a ~1.75e9 value), .700 can land at .6999996 -> *1000 =
	 * 699.9996; round() -> 700 (".7") but (int) -> 699 (".699"). To stay
	 * string-equal to microtimeToSeconds we ROUND here. The $decimals cut on top
	 * uses the SAME substr truncation approach as formatElapsed and is
	 * DISPLAY-ONLY (never a sort key; timeOfDay()'s float is the value).
	 *
	 * The start list passes $decimals = 3 to reproduce the legacy full `.mmm`
	 * form; $decimals = 0 yields a bare HH:MM:SS.
	 *
	 * @param float|int|string $epoch    time-of-day epoch (full ms precision)
	 * @param int              $decimals displayed fractional digits (default 0)
	 * @return string
	 */
	public static function formatTimeOfDay($epoch, $decimals = 0)
	{
		if (!is_numeric($epoch))
		{
			// Defensive: never assume a numeric value reached us.
			return '';
		}

		$full = self::legacyTimeOfDay((float) $epoch);

		$decimals = (int) $decimals;
		if ($decimals < 0)
		{
			$decimals = 0;
		}

		// "HH:MM:SS" = 8 chars; + "." + N fractional digits. Same substr cut as
		// race_format::formatElapsed.
		$len = 8 + ($decimals > 0 ? 1 + $decimals : 0);

		return substr($full, 0, $len);
	}

	/**
	 * Reproduce timetrackerMain::microtimeToSeconds byte-for-byte: a TIME-OF-DAY
	 * clock string (server-local, via date()), NOT a duration. ms via round()
	 * (matching the legacy start-time formatter), then rtrim of trailing zeros,
	 * with ".000" for a whole second.
	 *
	 * Timezone is intentionally the server default to match legacy: parse and
	 * format use the same zone, so the rendered H:i:s round-trips the stored
	 * wall-clock value (NOTES.md D-10 - pinning UTC is a post-parity candidate).
	 * The round-to-1000 overflow quirk (ms == 1000 -> ".1") is reproduced too.
	 *
	 * @param float $epoch
	 * @return string
	 */
	private static function legacyTimeOfDay($epoch)
	{
		$seconds = floor($epoch);

		$miliseconds  = 0;
		$microseconds = $epoch - $seconds;
		if ($microseconds > 0)
		{
			$miliseconds = round($microseconds * 1000, 0);
		}

		$out = date('H:i:s', (int) $seconds);

		if ($miliseconds > 0)
		{
			$out .= rtrim(sprintf('.%03d', $miliseconds), '0');
		}
		else
		{
			$out .= ".000";
		}

		return $out;
	}

	/**
	 * Clean status enum for a racer at a point:
	 *   OK | DNF | DSQ | DNS | NO_START | NO_CROSSING
	 *
	 * Replaces the legacy magic returns (-1 / '' / '---' / '-') and fixes the
	 * 'DSG' typo to DSQ. Precedence matches legacy
	 * get_racer_time_on_point (timetracker_class.php:248-309):
	 *   1. DNS  - page-level racer_active = false (the source stays the racer
	 *             table; it is passed in, never queried here);
	 *   2. NO_START - no usable start crossing (legacy "-"), checked before any
	 *      DNF/DSQ lookup so a no-start racer is NO_START even if flagged;
	 *   3. crossing present at the point -> DNF/DSQ if the cell carries that
	 *      ended marker, else OK (a crossing at the point is OK even if the racer
	 *      DNF'd elsewhere later);
	 *   4. no crossing at the point -> the cell's ended marker, else the
	 *      racer-wide DNF/DSQ set, else NO_CROSSING (legacy "---").
	 *
	 * @param string $number
	 * @param string $point
	 * @param bool   $racerActive racer table's racer_active (default true)
	 * @return string one of the STATUS_* constants
	 */
	public function pointStatus($number, $point, $racerActive = true)
	{
		$number = (string) $number;

		if (!$racerActive)
		{
			return self::STATUS_DNS;
		}

		$start = $this->savedTime($number, 'start');
		if (!is_numeric($start) || $start <= 0)
		{
			return self::STATUS_NO_START;
		}

		$endedFlag = $this->endedAt($number, $point);
		$actual    = $this->savedTime($number, $point);
		$hasActual = is_numeric($actual) && $actual > 0;

		if ($hasActual)
		{
			if ($endedFlag === '')
			{
				return self::STATUS_OK;
			}

			return $this->normalizeEnded($endedFlag);
		}

		// No usable crossing at this point.
		if ($endedFlag !== '')
		{
			return $this->normalizeEnded($endedFlag);
		}
		if ($this->isDnf($number))
		{
			return self::STATUS_DNF;
		}
		if ($this->isDsq($number))
		{
			return self::STATUS_DSQ;
		}

		return self::STATUS_NO_CROSSING;
	}

	/**
	 * Map a stored ended marker to a clean status constant. The schema only ever
	 * stores '', 'DNF' or 'DSQ'; the legacy 'DSG' typo (a display-only string,
	 * never stored) is folded to DSQ here. Any unexpected non-empty marker is
	 * treated as DNF (the generic "did not finish").
	 */
	protected function normalizeEnded($flag)
	{
		if ($flag === 'DSQ' || $flag === 'DSG')
		{
			return self::STATUS_DSQ;
		}

		return self::STATUS_DNF;
	}

	// ------------------------------------------------------- test seams

	/**
	 * Inject a pre-built split map (for the parity self-test, which exercises the
	 * pure compute without a database). Not used in production paths.
	 */
	public function setSplits(array $splits)
	{
		$this->splits = $splits;
		$this->built  = true;

		return $this;
	}

	/**
	 * Inject the DNF/DSQ sets (keyed [number => true]) for the self-test.
	 */
	public function setEndedSets(array $dnf, array $dsq)
	{
		$this->dnf = $dnf;
		$this->dsq = $dsq;

		return $this;
	}
}
