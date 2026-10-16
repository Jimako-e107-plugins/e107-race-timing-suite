<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - includes/race_clock.php
 *
*/

/*
 * Timing engine: pure read/compute over the race_time table. No ranking, no
 * HTML, no writes - ranking and presentation live in racereports. Elapsed times
 * and splits are computed on read, never stored (docs/plugins/racetiming.md).
 *
 * - Every value placed in SQL goes through $tp->toDB().
 * - The bib (race_time_racer_number) is always a string, never (int)-cast:
 *   leading zeros are data.
 * - Stored times are 'Y-m-d H:i:s.v' strings. is_numeric() guards every use of a
 *   parsed time (PHP 8 safe): a racer can carry a non-numeric 'DNF' marker at one
 *   point and valid times at later points, so never assume complete, monotonic
 *   or numeric data.
 * - Output deliberately matches the legacy timing code, quirks included; the
 *   'DSG' typo is the one intentional fix (now DSQ). See racetiming-NOTES.md in
 *   the repo root, "Intentional-fix candidates".
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
	 * map. One DB pass for crossings. Returns $this for chaining.
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
	 * The single crossing SELECT: every crossing, ordered so that for a duplicate
	 * (number,point) the LATEST crossing is processed last and therefore wins in
	 * buildSplits(). race_time_id is only read for crossingId(); it does not
	 * affect the time/ended computation. No user input is interpolated.
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
	 * Set of racer numbers carrying a given ended flag anywhere in race_time,
	 * keyed by race_time_racer_number (string). Reads race_time alone (no racer
	 * join). The flag is passed through $tp->toDB().
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
	 * DateTime::createFromFormat('Y-m-d H:i:s.v') - the stored format - with
	 * fallbacks 'Y-m-d H:i:s' and 'H:i:s.v'. NOT strtotime(), NOT new DateTime(),
	 * NOT DateTime::ATOM. Returns null on empty/invalid, which flows to
	 * NO_START / NO_CROSSING downstream.
	 *
	 * Timezone is the server default on purpose: an elapsed time is the
	 * difference of two same-zone crossings (racetiming-NOTES.md, D-10).
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
		// Legacy placeholder value that means "no time".
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
	 * Build the split map from raw crossing rows.
	 *
	 *   map[number][point] = ['savedtime'=>?float, 'ended'=>string, ...]
	 *
	 * - Latest crossing wins per (number,point): rows arrive ORDER BY number,
	 *   time, so a later assignment overwrites an earlier one.
	 * - The per-racer `ended` flag propagates forward: reset to '' when the racer
	 *   number changes, set once a row carries race_time_ended, and stamped onto
	 *   every cell from that row on (a mid-race 'DNF' marker reaches the racer's
	 *   later crossings).
	 * - A cell is created even when the time does not parse (savedtime = null):
	 *   it still means "this racer has a row at this point", which the online
	 *   report's furthest-point bucketing relies on (isset on the cell).
	 * - crossing_id (for the report's admin edit link) is the LOWEST race_time_id
	 *   of the (number,point), unlike the time. For a duplicate (number,point) the
	 *   displayed time is the latest crossing while the edit link targets the
	 *   lowest-id row - the same row the legacy `LIMIT 1` lookup (no ORDER BY)
	 *   resolved to. Data is untrusted, so the choice is explicit and stable.
	 *
	 * $points is accepted for API symmetry only; it does not restrict or
	 * pre-seed the map.
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

			// Keep the LOWEST race_time_id for this (number,point); unlike the time
			// fields this does NOT follow latest-crossing-wins.
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
	 * Unlike elapsedToPoint() this is the ABSOLUTE crossing time, not a duration
	 * (used for the start time in the start report and the start list). A
	 * non-numeric / 'DNF' / placeholder / missing crossing returns null.
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
	 * Used by the online report's furthest-point test.
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
	 * no row at the point. The report's admin edit link uses it to open the
	 * race_time admin on the exact row.
	 *
	 * A row has an id even when its time is unusable ('DNF' marker, unparseable
	 * time). For a duplicate (number,point) the LOWEST id is returned (see
	 * buildSplits()).
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
	 * The checkpoint code at which a racer carries the given ended marker (the
	 * race_time_point of the row whose race_time_ended = $flag), or null when
	 * there is none. The furthest-point / blank-after-DNF logic of the full
	 * results matrix needs it; the propagated `ended` flag in the split map
	 * cannot tell which cell was the marker.
	 *
	 * One race_time read per flag, lazily cached. GROUP BY race_time_racer_number
	 * picks one row per racer - unambiguous for one marker per racer. The read is
	 * global; the report scopes it to its own bibs, as with isDnf()/isDsq().
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
	 * single race_time read (GROUP BY race_time_racer_number, as the legacy
	 * lookup did). The flag goes through $tp->toDB().
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
	 * Reports use it for the display time of an ended racer that still has a
	 * crossing at the point; elapsedToPoint() below is the ranking-safe variant.
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
	 * Full precision: sort order never depends on the displayed decimals
	 * (race_format is display-only).
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
	 * No new read, no state. Returns only the float (or null); display text and
	 * DNF handling stay in the presentation layer. Like elapsedRaw() it ignores
	 * any ended flag.
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
	 * Display only (never a sort key; timeOfDay()'s float is the value).
	 *
	 * The millisecond field is ROUNDED here, whereas race_format::formatElapsed
	 * TRUNCATES: the absolute epoch carries float residue (format('U.v') of a
	 * ~1.75e9 value), so .700 can land at .6999996 -> *1000 = 699.9996; round()
	 * gives 700 (".7") but (int) gives 699 (".699"). The $decimals cut on top is
	 * the same substr truncation as formatElapsed.
	 *
	 * racers/startlist.php passes $decimals = 3 (full .mmm); 0 gives HH:MM:SS.
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
	 * TIME-OF-DAY clock string (server-local, via date()), NOT a duration: ms via
	 * round(), trailing zeros trimmed, ".000" for a whole second. Parse and format
	 * use the same (server default) zone, so H:i:s round-trips the stored
	 * wall-clock value (racetiming-NOTES.md, D-10). Legacy quirk kept: ms rounding
	 * to 1000 gives ".1".
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
	 * Precedence (as in the legacy timing code):
	 *   1. DNS  - racer_active = false (from the racer table; passed in, never
	 *             queried here);
	 *   2. NO_START - no usable start crossing, checked before any DNF/DSQ
	 *      lookup so a no-start racer is NO_START even if flagged;
	 *   3. crossing present at the point -> DNF/DSQ if the cell carries that
	 *      ended marker, else OK (a crossing at the point is OK even if the racer
	 *      DNF'd elsewhere later);
	 *   4. no crossing at the point -> the cell's ended marker, else the
	 *      racer-wide DNF/DSQ set, else NO_CROSSING.
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
	 * Map a stored ended marker to a status constant. 'DSG' (a legacy typo) is
	 * folded to DSQ. Any other non-empty marker - including 'DNS' - is treated as
	 * DNF.
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
	 * Test seam: inject a pre-built split map, to exercise the pure compute
	 * without a database. Not used in production paths.
	 */
	public function setSplits(array $splits)
	{
		$this->splits = $splits;
		$this->built  = true;

		return $this;
	}

	/**
	 * Test seam: inject the DNF/DSQ sets (keyed [number => true]).
	 */
	public function setEndedSets(array $dnf, array $dsq)
	{
		$this->dnf = $dnf;
		$this->dsq = $dsq;

		return $this;
	}
}
