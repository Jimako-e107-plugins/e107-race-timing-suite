<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - includes/race_report.php
 *
*/

/*
 * Shared report helpers: fetch races / categories / checkpoints / racers and turn a
 * racetiming engine result into the display cell. No time math here - that is the
 * racetiming engine (race_clock / race_format).
 *
 * Every value placed in SQL goes through toDB(); race ids are (int)-cast; the bib is a
 * string (quoted, never cast - leading zeros are data); output goes through
 * toHTML()/toAttribute().
 */

// Shared racer display-name builder (getRacerName delegates to it).
require_once(e_PLUGIN . 'racers/includes/racers_name.php');

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
	 * Resolve a single race by its integer race_id (the stu / aktualne routes'
	 * ?p selects a track by race_id, not by sef).
	 * @return array|false the #race row, or false
	 */
	public function fetchRaceById($raceId)
	{
		$sql    = e107::getDb();
		$raceId = (int) $raceId;

		$query = "SELECT * FROM " . MPREFIX . "race WHERE race_id = " . $raceId;

		return $sql->retrieve($query);
	}

	/** All #race rows (for the all-tracks views: r=komplet / r=overview). */
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
	 * order the online furthest-point bucketing walks. Callers that need course
	 * order re-sort.
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
	 * Display name for a racer (HTML-safe). Delegates to racers_name::build()
	 * (racers/includes/racers_name.php), the single place the name is composed.
	 *
	 * @param array  $racer racer row
	 * @param string $type  "team" includes the team suffix (default)
	 * @return string HTML-safe display name
	 */
	public function getRacerName($racer = array(), $type = 'team')
	{
		return racers_name::build((array) $racer, array('team' => ($type === 'team')));
	}

	// --------------------------------------------------------- diff cell

	/**
	 * Time cell of a racer at a point (online / checkpoint / arrivals reports),
	 * built entirely from the engine.
	 *
	 * Returns ['time'=>..., 'text'=>..., 'ended'=>...] where:
	 *   - time : the round(...,3) elapsed float for a ranked racer; '' for
	 *            no-start / no-crossing; -1 for an ended/flagged racer with no
	 *            usable time at the point (magic values kept from the old
	 *            timetracker code - the pages' ranking tests rely on them);
	 *   - text : the display string (formatElapsed at $decimals digits, a flag,
	 *            '-' for no start or '---' for no crossing);
	 *   - ended: the ended marker stamped at this point ('' | 'DNF' | 'DSQ').
	 *
	 * @param race_clock $clock
	 * @param string     $number
	 * @param string     $point
	 * @param int        $decimals displayed fractional digits
	 * @return array
	 */
	public function diffOnPoint($clock, $number, $point, $decimals = 1)
	{
		$number = (string) $number;

		// No usable start crossing -> "-".
		if (!$clock->hasStart($number))
		{
			return array('text' => '-', 'time' => '', 'ended' => '');
		}

		$endedFlag = $clock->endedAt($number, $point);

		// A usable crossing at the point: the time is shown even when an ended
		// marker is stamped here (only the archive variant blanks it).
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
			return array('time' => -1, 'text' => 'DSQ', 'ended' => '');
		}

		// Neither: no crossing at all -> "---".
		return array('time' => '', 'text' => '---', 'ended' => '');
	}

	/**
	 * Is this diff a ranked result (positive time, non-empty text, no ended
	 * marker at this point)?
	 */
	public function isRanked($diff)
	{
		return $diff['time'] > 0 && $diff['text'] !== '' && $diff['ended'] === '';
	}
}

/**
 * Opt-in DataTables loader for the front reports: registers the plugin's own
 * assets/datatables/ copy (bs5 build: DataTables 1.13.1 + Responsive 2.4.0) plus
 * init.js, which binds to #report_stu and #report_aktualne. Called by
 * report_stu.php and report_aktualne.php; report_online.php deliberately does not
 * (DataTables would fight the meta refresh). racetrack's archive page loads the
 * same library files directly, with its own init.
 *
 * The jQuery dependency is declared explicitly ('jquery') rather than left to load
 * order. A static once-guard registers the assets only once per request.
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
