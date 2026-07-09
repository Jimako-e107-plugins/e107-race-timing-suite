<?php
/*
 * e107 website system
 *
 * racereports plugin - shared report helpers (PART B, presentation layer).
 *
 * Thin glue between the racetiming compute engine (race_clock / race_format) and
 * the two online report pages (report_online.php = online, report_point.php =
 * point). Does
 * ONLY presentation-side work: fetch racers (racer table), read checkpoint order
 * from racetrack (race_point / race_point_order), and turn an engine result into
 * the exact legacy display cell. It performs NO time math (all of that is in
 * racetiming) and NO ranking decision beyond what each page drives.
 *
 * SECURITY (written safe from the start - the legacy raw-$_GET-into-SQL pattern
 * is NOT carried over): every value placed in SQL goes through $tp->toDB(); race
 * ids are (int)-cast (they are ints); the bib/start number stays a string and is
 * quoted, never cast; output goes through $tp->toHTML()/toAttribute().
 */

class race_report
{
	/** @var array racers plugin prefs (name display options), loaded once */
	protected $racerPrefs;

	public function __construct()
	{
		$this->racerPrefs = e107::pref('racers');
		if (!is_array($this->racerPrefs))
		{
			$this->racerPrefs = array();
		}
	}

	// --------------------------------------------------------------- fetch

	/**
	 * Resolve a single race by its SEF slug. $sef is sanitised via toDB.
	 * @return array|false the #race row, or false
	 */
	public function fetchRaceBySef($sef)
	{
		$tp  = e107::getParser();
		$sql = e107::getDb();
		$sef = $tp->toDB($sef);

		$query = "SELECT * FROM " . MPREFIX . "race WHERE race_sef = '" . $sef . "'";

		return $sql->retrieve($query);
	}

	/**
	 * Resolve a single race by its integer race_id. Mirrors the legacy stu.php
	 * lookup (timetracker/stu.php:36-37 `WHERE race_id=$_GET['p']`): the stu route's
	 * ?p selects a TRACK by race_id, not a sef. race id is an int.
	 * @return array|false the #race row, or false
	 */
	public function fetchRaceById($raceId)
	{
		$sql    = e107::getDb();
		$raceId = (int) $raceId;

		$query = "SELECT * FROM " . MPREFIX . "race WHERE race_id = " . $raceId;

		return $sql->retrieve($query);
	}

	/** All #race rows (for the r=komplet all-races view). */
	public function fetchAllRaces()
	{
		$sql  = e107::getDb();
		$rows = $sql->retrieve("SELECT * FROM " . MPREFIX . "race", true);

		return is_array($rows) ? $rows : array();
	}

	/**
	 * Categories of a race resolved by category SEF (constrained to the race via
	 * FIND_IN_SET). Both inputs sanitised; race id is an int.
	 * @return array race_category rows
	 */
	public function fetchCategoriesBySef($raceId, $categorySef)
	{
		$tp     = e107::getParser();
		$sql    = e107::getDb();
		$raceId = (int) $raceId;
		$sef    = $tp->toDB($categorySef);

		$query = "SELECT * FROM " . MPREFIX . "race_category AS rc
			WHERE race_category_sef = '" . $sef . "'
			AND FIND_IN_SET(" . $raceId . ", rc.race_category_race)";

		$rows = $sql->retrieve($query, true);

		return is_array($rows) ? $rows : array();
	}

	/**
	 * Checkpoints of a race, ordered race_point_order DESC (finish-first), the
	 * order the online furthest-point bucketing walks.
	 * @return array race_point rows
	 */
	public function fetchCheckpoints($raceId)
	{
		$sql    = e107::getDb();
		$raceId = (int) $raceId;

		$query = "SELECT * FROM " . MPREFIX . "race_point AS rp
			WHERE FIND_IN_SET(" . $raceId . ", rp.race_point_race)
			ORDER BY race_point_order DESC";

		$rows = $sql->retrieve($query, true);

		return is_array($rows) ? $rows : array();
	}

	/**
	 * A single checkpoint of a race resolved by point code. Sanitised.
	 * @return array|false race_point row
	 */
	public function fetchPointByCode($raceId, $code)
	{
		$tp     = e107::getParser();
		$sql    = e107::getDb();
		$raceId = (int) $raceId;
		$code   = $tp->toDB($code);

		$query = "SELECT * FROM " . MPREFIX . "race_point AS rp
			WHERE race_point_code = '" . $code . "'
			AND FIND_IN_SET(" . $raceId . ", rp.race_point_race)";

		return $sql->retrieve($query);
	}

	/**
	 * Racers of a race (optionally a single category), keyed by bib number
	 * (string). Race/category ids are ints.
	 * @return array [racer_number => racer row]
	 */
	public function fetchRacers($raceId, $categoryId = null)
	{
		$sql    = e107::getDb();
		$raceId = (int) $raceId;

		$query = "SELECT * FROM " . MPREFIX . "racer AS rr WHERE racer_race_id = " . $raceId;
		if ($categoryId !== null)
		{
			$query .= " AND racer_category_id = " . (int) $categoryId;
		}

		$rows = $sql->retrieve($query, true);

		$byNumber = array();
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$byNumber[(string) $row['racer_number']] = $row;
			}
		}

		return $byNumber;
	}

	// ----------------------------------------------------------- name

	/**
	 * Display name for a racer. Reproduces the legacy getRacerName
	 * (timetrackerMain_class.php:27-52) - surname + firstname, optional name_hash,
	 * optional local marker, optional team suffix - but routes the
	 * admin-entered surname/firstname through $tp->toHTML() as well (the legacy
	 * left those two fields unescaped, a stored-XSS surface; see NOTES.md). For
	 * plain-ASCII names toHTML is a no-op, so display parity holds.
	 *
	 * @param array  $racer racer row
	 * @param string $type  "team" includes the team suffix (default), as legacy
	 * @return string HTML-safe display name
	 */
	public function getRacerName($racer = array(), $type = 'team')
	{
		$tp    = e107::getParser();
		$prefs = $this->racerPrefs;

		$surname   = isset($racer['racer_surname']) ? $racer['racer_surname'] : '';
		$firstname = isset($racer['racer_firstname']) ? $racer['racer_firstname'] : '';

		$racerName = $tp->toHTML($surname, false, 'TITLE') . ' ' . $tp->toHTML($firstname, false, 'TITLE');

		if (!empty($prefs['name_hash']))
		{
			// Hash is over the raw name, as legacy (md5 output is safe).
			$racerName = md5($surname . ' ' . $firstname);
		}

		if (!empty($prefs['display_local']) && !empty($prefs['text_local']) && !empty($racer['racer_local']))
		{
			$racerName .= $tp->toHTML($prefs['text_local'], 'TITLE', true);
		}

		if ($type === 'team' && !empty($prefs['display_team']) && !empty($racer['racer_team']))
		{
			$team = $tp->toHTML($racer['racer_team'], 'TITLE', true);
			$racerName .= "&nbsp;<small><i>(" . $team . ")</i></small>";
		}

		return $racerName;
	}

	// --------------------------------------------------------- diff cell

	/**
	 * Reproduce the legacy get_racer_time_on_point() return shape for the "point"
	 * type (timetracker_class.php:248-310), built entirely from the engine. This
	 * is the single source of truth both report pages format their time cell
	 * from, so their output matches the legacy pages branch-for-branch.
	 *
	 * Returns ['time'=>..., 'text'=>..., 'ended'=>...] where:
	 *   - time : the round(...,3) elapsed float for a ranked racer; '' for
	 *            no-start / no-crossing; -1 for an ended/flagged racer with no
	 *            usable time at the point (the legacy magic values, preserved so
	 *            the page-level ranking tests behave identically);
	 *   - text : the display string (formatElapsed at $decimals digits, or a flag);
	 *   - ended: the propagated ended marker ('' | 'DNF' | 'DSQ').
	 *
	 * Differences from legacy, both intentional and in scope:
	 *   - NO result-freeze / GEN_RESULTS side effect (that terminovka-owned write
	 *     path stays in timetracker and is out of scope for the live read);
	 *   - the legacy 'DSG' typo is rendered 'DSQ' (intentional fix, flagged by the
	 *     parity comparator).
	 *
	 * @param race_clock $clock
	 * @param string     $number
	 * @param string     $point
	 * @param int        $decimals displayed fractional digits (default 1 = legacy)
	 * @return array
	 */
	public function diffOnPoint($clock, $number, $point, $decimals = 1)
	{
		$number = (string) $number;

		// No usable start crossing -> legacy "-" (startpoint == 0 early return).
		if (!$clock->hasStart($number))
		{
			return array('text' => '-', 'time' => '', 'ended' => '');
		}

		$endedFlag = $clock->endedAt($number, $point);

		// A usable crossing at the point: compute the time (ended does NOT blank
		// it in "point" type - legacy only blanked under $type=="archive").
		if ($clock->hasCrossingAt($number, $point))
		{
			$elapsed = $clock->elapsedRaw($number, $point);

			return array(
				'time'  => $elapsed,
				'text'  => race_format::formatElapsed($elapsed, $decimals),
				'ended' => $endedFlag,
			);
		}

		// No usable crossing at the point but an ended marker is stamped here.
		if ($endedFlag !== '')
		{
			return array('text' => $endedFlag, 'time' => -1, 'ended' => $endedFlag);
		}

		// Ended somewhere else (racer-wide DNF/DSQ set), no crossing here.
		if ($clock->isDnf($number))
		{
			return array('time' => -1, 'text' => 'DNF', 'ended' => '');
		}
		if ($clock->isDsq($number))
		{
			// Legacy emitted the 'DSG' typo here; clean output is 'DSQ'.
			return array('time' => -1, 'text' => 'DSQ', 'ended' => '');
		}

		// Neither: no crossing at all -> legacy "---".
		return array('time' => '', 'text' => '---', 'ended' => '');
	}

	/**
	 * Is this diff a ranked (NORMAL) result? Mirrors the legacy page test
	 *   $diff['time'] > 0 && $diff['text'] != "" && $diff['ended'] == ""
	 * used identically by both online (:244) and point (:166).
	 */
	public function isRanked($diff)
	{
		return $diff['time'] > 0 && $diff['text'] !== '' && $diff['ended'] === '';
	}
}

/**
 * CENTRAL, opt-in DataTables loader for the racereports front reports.
 *
 * Registers the racereports-OWNED DataTables assets (the bs5 build: DataTables
 * 1.13.1 + Responsive 2.4.0) plus the racereports init. This is the ONE place the
 * assets are wired - report pages opt in with a single call to this helper rather
 * than copy-pasting the e107::css/js lines. racereports owns its OWN copy under
 * assets/datatables/ so it does NOT depend on the timetracker copy (which is being
 * decommissioned separately).
 *
 * jQuery: e107 ships core jQuery and auto-loads it on the front-side, but the
 * dependency is DECLARED explicitly here ('jquery') rather than left to implicit
 * load order, so the DataTables plugin and the init both run after jQuery is ready.
 *
 * Once-guard: a static flag makes repeated calls (e.g. from more than one report on
 * a page, or a future point opt-in) register the assets exactly once.
 *
 * Opt-in policy (see NOTES.md): only report_stu.php calls this today.
 * report_online.php deliberately does NOT (it is live/auto-refresh - DataTables
 * would fight the meta refresh). report_point.php can opt in later with a single
 * call to this helper - no other change needed.
 */
function race_report_load_datatables()
{
	static $loaded = false;
	if ($loaded)
	{
		return;
	}
	$loaded = true;

	e107::css('racereports', 'assets/datatables/css/datatables-bs5.min.css');
	e107::js('racereports', 'assets/datatables/js/datatables-bs5.min.js', 'jquery'); // declare jQuery dep
	e107::js('racereports', 'assets/datatables/init.js', 'jquery');
}
