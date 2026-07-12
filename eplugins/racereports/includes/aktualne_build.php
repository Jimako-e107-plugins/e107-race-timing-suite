<?php
/*
 * e107 website system
 *
 * racereports plugin - AKTUALNE builder (the FULL per-race results matrix).
 *
 * PURE, side-effect-free file: it only DEFINES racereports_aktualne_build() and its
 * helpers (guarded by e107_INIT). It echoes nothing and renders no page chrome, so
 * it can be required by:
 *   - report_aktualne.php  (the on-screen page: echoes the returned ['html']);
 *   - parity/parity_aktualne.php (the parity harness: compares the returned cells
 *     against the legacy timetrackerArchive output);
 *   - Phase 2 (archive writing: persists the SAME ['html'] + ['data']).
 *
 * The caller MUST have loaded the racetiming engine (race_clock + race_format)
 * before calling racereports_aktualne_build() - the helpers reference those classes
 * at call time. This file requires nothing itself, so it never double-loads them.
 *
 * Faithful port of timetracker/classes/timetrackerArchive_class.php onto the engine.
 * See report_aktualne.php's header and racereports/NOTES.md for the contract (the
 * two distinct legacy truncations HH:MM / HH:MM:SS, the DNF furthest-point order,
 * the blank-after-DNF cells, catorder blank for DNF/DSQ, the 'racers' name prefs).
 *
 * SECURITY: race id is (int)-cast; the bib/start number stays a STRING; every SQL
 * value goes through $tp->toDB() (here or in the engine); output is escaped via
 * toHTML/toAttribute. No extract(), no raw-$_GET-into-SQL, no writes.
 */

if (!defined('e107_INIT'))
{
	exit;
}

/**
 * Build the FULL per-race results matrix as a NEUTRAL structure that BOTH the page
 * (echoes ['html']) and the future Phase-2 archive (persists ['html'] + ['data'])
 * render from. RETURNS - does not echo.
 *
 * Faithful port of timetrackerArchive_class.php onto the racetiming engine:
 *   - racers: racer_race_id = race_id AND racer_active, ORDER BY racer_number;
 *   - three sections NORMAL / DNF / DSQ (engine isDnf/isDsq), each ordered by the
 *     legacy ordertime and rendered in racer_number order (DataTables then sorts the
 *     visible table by the Por. column, col 0 asc);
 *   - per-checkpoint cell = elapsed start->checkpoint truncated to HH:MM; "Cas" =
 *     finish elapsed truncated to HH:MM:SS;
 *   - DNF furthest-point ordering + blank-after-DNF cells; catorder blank for DNF/DSQ.
 *
 * @param int         $raceId race id (caller int-casts; re-cast here defensively)
 * @param race_clock  $clock  built engine
 * @param race_report $report report helper (fetch + escaping)
 * @return array ['html' => string, 'data' => array]
 */
function racereports_aktualne_build($raceId, $clock, $report)
{
	// SELF-CONTAINED LAN: this include references LAN_RACEREPORTS_AKT_* (the
	// column headers + empty-row text) but does not own a page, so it must load
	// its OWN front LAN rather than rely on the caller. report_aktualne.php (the
	// front page) loads it, but the ARCHIVE generate path (racetrack calling
	// racereports_aktualne_build()) does not - without this load those constants
	// are undefined and PHP 8 fatals. The LAN registry is load-once, so a second
	// call from report_aktualne.php is a harmless no-op. Mirrors how
	// raceevent/includes/event_overview.php loads its own LAN. The defensive
	// define() fallbacks below are belt-and-suspenders for an odd LAN path.
	e107::lan('racereports', '', true); // front strings (array form)

	if (!defined('LAN_RACEREPORTS_AKT_EMPTY'))       define('LAN_RACEREPORTS_AKT_EMPTY', 'No racers.');
	if (!defined('LAN_RACEREPORTS_AKT_COL_POR'))     define('LAN_RACEREPORTS_AKT_COL_POR', 'Pos.');
	if (!defined('LAN_RACEREPORTS_AKT_COL_NAME'))    define('LAN_RACEREPORTS_AKT_COL_NAME', 'Name');
	if (!defined('LAN_RACEREPORTS_AKT_COL_CAT'))     define('LAN_RACEREPORTS_AKT_COL_CAT', 'Cat.');
	if (!defined('LAN_RACEREPORTS_AKT_COL_TIME'))    define('LAN_RACEREPORTS_AKT_COL_TIME', 'Time');
	if (!defined('LAN_RACEREPORTS_AKT_COL_CATRANK')) define('LAN_RACEREPORTS_AKT_COL_CATRANK', 'Rank in category');

	$tp     = e107::getParser();
	$sql    = e107::getDb();
	$raceId = (int) $raceId;

	$race     = $report->fetchRaceById($raceId);
	$raceName = (is_array($race) && isset($race['race_name'])) ? (string) $race['race_name'] : '';

	// Name-display prefs now live in the 'racers' plugin (the legacy archive read
	// them from 'timetracker'; that pref store has moved with the racer model -
	// see NOTES.md). race_report::getRacerName reads the same store. We need the
	// archive-specific name format (bib IS part of the name), so we read directly.
	$prefs = e107::pref('racers');
	if (!is_array($prefs))
	{
		$prefs = array();
	}

	// Categories keyed by id (for the Kat. cell + catorder counters).
	$cats = $sql->retrieve('race_category', '*', true, true, 'race_category_id');
	if (!is_array($cats))
	{
		$cats = array();
	}

	// Checkpoints of THIS race, ORDER BY race_point_order ASC, keyed by code. The
	// legacy archive walks them ascending (set_table_columns / set_point_times);
	// race_report::fetchCheckpoints() is DESC (online furthest-first), so we read
	// ascending here directly.
	$checkpoints = aktualne_fetch_checkpoints($raceId); // [code => race_point row], ASC

	// Column checkpoint codes: every checkpoint EXCEPT 'start' (the 'finish'
	// checkpoint is the last column before Rank).
	$cpCodes = array();
	foreach ($checkpoints as $code => $point)
	{
		if ($code === 'start')
		{
			continue;
		}
		$cpCodes[] = $code;
	}

	// count(self::$checkpoints) in the legacy DNF ordertime INCLUDES start.
	$checkpointCount = count($checkpoints);

	// Racers of this race (racer_active), keyed by bib STRING, ORDER BY racer_number.
	$racers = aktualne_fetch_racers($raceId);

	// Partition NORMAL / DNF / DSQ (engine membership). Exclusive: a racer flagged
	// both DNF and DSQ goes to DNF only (legacy would list it in BOTH the DNF and
	// DSQ blocks - a latent double-print; we list once, flagged as an intentional
	// fix in NOTES.md / the parity harness).
	$normal = array();
	$dnf    = array();
	$dsq    = array();
	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		if ($clock->isDnf($number))
		{
			$dnf[$number] = $racer;
		}
		elseif ($clock->isDsq($number))
		{
			$dsq[$number] = $racer;
		}
		else
		{
			$normal[$number] = $racer;
		}
	}

	// Process each section. The Por. counter is CUMULATIVE across sections (legacy
	// passes count(normal), then count(normal)+count(dnf) as the order base).
	$order  = 0;
	$rows0  = aktualne_process_section($clock, $normal, 0, $checkpoints, $cpCodes, $checkpointCount, $cats, $prefs, $order);
	$order += count($rows0);
	$rows1  = aktualne_process_section($clock, $dnf, 1, $checkpoints, $cpCodes, $checkpointCount, $cats, $prefs, $order);
	$order += count($rows1);
	$rows2  = aktualne_process_section($clock, $dsq, 2, $checkpoints, $cpCodes, $checkpointCount, $cats, $prefs, $order);

	$sections = array(0 => $rows0, 1 => $rows1, 2 => $rows2);

	// ---- HTML --------------------------------------------------------------
	// Header.
	$ths  = '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_POR, false) . '</th>';
	$ths .= '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_NAME, false) . '</th>';
	$ths .= '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_CAT, false) . '</th>';
	$ths .= '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_TIME, false) . '</th>';
	foreach ($cpCodes as $code)
	{
		// The checkpoint code IS the legacy header (e.g. "K2", "finish").
		$ths .= '<th>' . $tp->toHTML($code, false, 'TITLE') . '</th>';
	}
	$ths .= '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_CATRANK, false) . '</th>';

	$colspan = 4 + count($cpCodes) + 1;

	// Stable hook (#report_aktualne) + race-report class for the central DataTables
	// init (assets/datatables/init.js targets #report_aktualne, searching:true).
	$html  = "<div class='table-responsive'>";
	$html .= "<table id='report_aktualne' class='table table-striped table-bordered table-hover race-report dt-responsive nowrap'"
		. " data-order='[[0, \"asc\"]]' width='100%'>";
	$html .= '<thead><tr>' . $ths . '</tr></thead><tbody>';

	$total = count($rows0) + count($rows1) + count($rows2);
	if ($total === 0)
	{
		$html .= "<tr><td colspan='" . (int) $colspan . "'><em>"
			. $tp->toHTML(LAN_RACEREPORTS_AKT_EMPTY, false) . "</em></td></tr>";
	}

	foreach ($sections as $sectionRows)
	{
		foreach ($sectionRows as $row)
		{
			$html .= aktualne_render_row($row, $cpCodes, $tp);
		}
	}

	$html .= '</tbody></table></div>';

	// ---- DATA (structured; Phase 2 persists this) --------------------------
	$dataSections = array();
	foreach ($sections as $type => $sectionRows)
	{
		$out = array();
		foreach ($sectionRows as $number => $row)
		{
			$points = array();
			foreach ($cpCodes as $code)
			{
				$points[$code] = isset($row['points'][$code]['cas']) ? $row['points'][$code]['cas'] : '';
			}
			$out[] = array(
				'number'   => $row['number'],
				'name'     => $row['name'],
				'kat'      => $row['kat'],
				'cas'      => $row['cas'],
				'order'    => $row['order'],
				'catorder' => $row['catorder'],
				'points'   => $points,
			);
		}
		$dataSections[$type] = $out;
	}

	$data = array(
		'race'        => array(
			'id'   => $raceId,
			'name' => $raceName,
		),
		'checkpoints' => $cpCodes,
		'sections'    => $dataSections, // 0 = NORMAL, 1 = DNF, 2 = DSQ
	);

	return array('html' => $html, 'data' => $data);
}

/**
 * Checkpoints of a race, ORDER BY race_point_order ASC, keyed by race_point_code.
 * The legacy archive (init) walks them ascending; race_report::fetchCheckpoints()
 * is DESC, so this reads ascending directly. Race id is an int.
 *
 * @param int $raceId
 * @return array [code => race_point row]
 */
function aktualne_fetch_checkpoints($raceId)
{
	$sql    = e107::getDb();
	$raceId = (int) $raceId;

	$query = "SELECT * FROM " . MPREFIX . "race_point AS rp
		WHERE FIND_IN_SET(" . $raceId . ", rp.race_point_race)
		ORDER BY race_point_order";

	$rows = $sql->retrieve($query, true);

	$byCode = array();
	if (is_array($rows))
	{
		foreach ($rows as $row)
		{
			$byCode[(string) $row['race_point_code']] = $row;
		}
	}

	return $byCode;
}

/**
 * Racers of a race that are racer_active, keyed by bib STRING, ORDER BY
 * racer_number (legacy archive init filter, kept verbatim). Race id is an int.
 *
 * @param int $raceId
 * @return array [number => racer row]
 */
function aktualne_fetch_racers($raceId)
{
	$sql    = e107::getDb();
	$raceId = (int) $raceId;

	$query = "SELECT * FROM " . MPREFIX . "racer
		WHERE racer_race_id = " . $raceId . " AND racer_active
		ORDER BY racer_number";

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

/**
 * Process ONE section (0 = NORMAL, 1 = DNF, 2 = DSQ) into datarows keyed by bib.
 * Reproduces the legacy set_racer_data + get_finish_time + set_point_times +
 * set_correct_orders pipeline for that section, on the engine. Returns the rows in
 * the ORIGINAL bib order (legacy renders datarows in racer_number order; the Por./
 * catorder values carry the computed rank, and DataTables sorts the visible table
 * by Por. col 0 asc).
 *
 * @param race_clock $clock
 * @param array      $racers          [number => racer] for this section
 * @param int        $sectionType     0|1|2
 * @param array      $checkpoints     [code => race_point row] ASC (incl. start)
 * @param array      $cpCodes         column checkpoint codes (excl. start) ASC
 * @param int        $checkpointCount count incl. start (legacy DNF ordertime)
 * @param array      $cats            [id => race_category row]
 * @param array      $prefs           'racers' name-display prefs
 * @param int        $orderBase       cumulative Por. base (count of prior sections)
 * @return array [number => row]
 */
function aktualne_process_section($clock, $racers, $sectionType, $checkpoints, $cpCodes, $checkpointCount, $cats, $prefs, $orderBase)
{
	$rows = array();

	// 1) set_racer_data + get_finish_time: base cells, "Cas", and the sort key.
	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;

		$row = array(
			'number'    => (string) $racer['racer_number'],
			'name'      => aktualne_racer_name($racer, $prefs),
			'kat'       => '',
			'catid'     => null,
			'cas'       => '',
			'cassort'   => null, // Cas-column numeric sort key
			'order'     => '',
			'catorder'  => '',
			'points'    => array(),
			'ordertime' => null,
			'section'   => $sectionType,
		);

		$catId = isset($racer['race_category_id']) ? (int) $racer['race_category_id'] : 0;
		if (isset($cats[$catId]))
		{
			$row['catid'] = $catId;
			$row['kat']   = isset($cats[$catId]['race_category_name']) ? $cats[$catId]['race_category_name'] : '';
		}

		if ($sectionType === 1) // DNF: ordertime = furthest-point key, "Cas" = DNF
		{
			$dnfPoint   = $clock->endedPoint($number, 'DNF');
			$pointOrder = ($dnfPoint !== null && isset($checkpoints[$dnfPoint]))
				? (int) $checkpoints[$dnfPoint]['race_point_order']
				: 0;
			$row['ordertime'] = ($checkpointCount - $pointOrder) * 1000000;
			$row['cas']       = 'DNF';
		}
		elseif ($sectionType === 2) // DSQ: "Cas" = DSQ, ordertime stays null
		{
			$row['cas'] = 'DSQ';
		}
		else // NORMAL: "Cas" = finish elapsed HH:MM:SS, ordertime = finish elapsed
		{
			$finish = aktualne_archive_diff($clock, $number, 'finish');
			$ftime  = $finish['time'];

			// Legacy keeps the finish time UNLESS (time < 0 OR text == "") - here
			// expressed without the PHP-8 loose-comparison quirk: keep only a
			// positive-or-zero numeric time with non-empty text (identical result set:
			// a no-finish cell has text '-'/'---' with a non-numeric time -> skipped).
			$skip = ($finish['text'] === '') || !is_numeric($ftime) || ($ftime < 0);
			if (!$skip)
			{
				$row['cas']       = substr((string) $finish['text'], 0, 8); // HH:MM:SS
				$row['ordertime'] = (float) $ftime;
				$row['cassort']   = (float) $ftime;
			}
		}

		$rows[$number] = $row;
	}

	// 2) set_point_times: per-checkpoint HH:MM cells + DNF blank-after-marker, and
	//    the DNF ordertime accumulation (legacy ordertime += difftime).
	foreach ($rows as $number => &$row)
	{
		$ended    = false;
		$difftime = 0;

		foreach ($cpCodes as $point)
		{
			$cell = '';
			$sort = null;

			if ($sectionType === 1) // DNF
			{
				$dnfPoint = $clock->endedPoint($number, 'DNF');

				if ($dnfPoint === $point)
				{
					$diff     = aktualne_archive_diff($clock, $number, $point);
					$cell     = substr((string) $diff['text'], 0, 5);
					$difftime = is_numeric($diff['time']) ? (float) $diff['time'] : 0;
					$sort     = $clock->hasCrossingAt($number, $point) ? $clock->elapsedRaw($number, $point) : null;
					$ended    = true;
				}
				elseif ($ended)
				{
					// After the DNF marker point: BLANK (legacy $ended flag).
					$cell     = '';
					$difftime = 0;
				}
				else
				{
					$diff     = aktualne_archive_diff($clock, $number, $point);
					$cell     = substr((string) $diff['text'], 0, 5);
					$difftime = is_numeric($diff['time']) ? (float) $diff['time'] : 0;
					$sort     = $clock->hasCrossingAt($number, $point) ? $clock->elapsedRaw($number, $point) : null;
				}
			}
			else // NORMAL or DSQ
			{
				$diff = aktualne_archive_diff($clock, $number, $point);
				$cell = substr((string) $diff['text'], 0, 5);
				$sort = $clock->hasCrossingAt($number, $point) ? $clock->elapsedRaw($number, $point) : null;
			}

			$row['points'][$point] = array('cas' => $cell, 'sort' => $sort);
		}

		if ($sectionType === 1)
		{
			$base = is_numeric($row['ordertime']) ? (float) $row['ordertime'] : 0;
			$row['ordertime'] = $base + $difftime;
		}
	}
	unset($row);

	// 3) set_correct_orders: rank by ordertime ASC (stable), assign Por. (cumulative)
	//    and catorder (per-category, BLANK for DNF/DSQ).
	$sorted = $rows; // copy; stable uasort keeps bib order for equal ordertime
	uasort($sorted, function ($a, $b)
	{
		return $a['ordertime'] <=> $b['ordertime'];
	});

	$catCounters = array();
	$pos = (int) $orderBase;
	foreach ($sorted as $number => $r)
	{
		$pos += 1;
		$rows[$number]['order'] = $pos;

		if ($sectionType === 1 || $sectionType === 2)
		{
			$rows[$number]['catorder'] = '';
			continue;
		}

		// NORMAL: per-category running position.
		$catKey = ($rows[$number]['catid'] === null) ? '' : $rows[$number]['catid'];
		if (!isset($catCounters[$catKey]))
		{
			$catCounters[$catKey] = 0;
		}
		$catCounters[$catKey] += 1;
		$rows[$number]['catorder'] = $catCounters[$catKey];
	}

	return $rows;
}

/**
 * Reproduce the legacy get_racer_time_on_point() "archive" return shape
 * (timetracker_class.php:248-309, $type=="archive") built entirely from the engine.
 * Returns ['text'=>..., 'time'=>..., 'ended'=>...]:
 *   - no usable start crossing -> text "-", time "";
 *   - usable crossing at the point -> the elapsed cut to tenths (= substr(
 *     secondsToTime, 0, 10)); when an ended marker is stamped here, the time is
 *     blanked ('') and, at the 'finish' point ONLY, the text is replaced by the
 *     ended marker (the legacy archive-type rule);
 *   - no crossing but an ended marker stamped here -> text = marker, time "-1";
 *   - ended elsewhere (racer-wide DNF/DSQ) -> "DNF" / "DSQ" (the legacy 'DSG' typo
 *     is rendered 'DSQ' - an intentional fix, flagged by the parity harness),
 *     time "-1";
 *   - nothing -> text "---", time "".
 *
 * @param race_clock $clock
 * @param string     $number
 * @param string     $point
 * @return array
 */
function aktualne_archive_diff($clock, $number, $point)
{
	$number = (string) $number;
	$point  = (string) $point;

	$start = $clock->savedTime($number, 'start');
	if (!is_numeric($start) || $start <= 0)
	{
		return array('text' => '-', 'time' => '', 'ended' => '');
	}

	$actual = $clock->savedTime($number, $point);
	$ended  = $clock->endedAt($number, $point);

	if (is_numeric($actual) && $actual > 0)
	{
		$elapsed = $clock->elapsedRaw($number, $point); // round($actual-$start,3)
		// legacy: substr(secondsToTime($elapsed), 0, 10) === formatElapsed($elapsed, 1).
		// HARDCODED 1 on purpose: Aktualne is the overview matrix (mid-times / finish
		// cells of the live grid), NOT a result list, so it is deliberately NOT governed
		// by the central result_decimals pref - it keeps the legacy tenths render.
		$text = race_format::formatElapsed($elapsed, 1);
		$time = $elapsed;

		if ($ended !== '')
		{
			// archive type: ended racer's time is blanked; at finish the text
			// becomes the ended marker, elsewhere the time text stays.
			$time = '';
			if ($point === 'finish')
			{
				$text = $ended;
			}
		}

		return array('text' => $text, 'time' => $time, 'ended' => $ended);
	}

	// No usable crossing but an ended marker is stamped at this cell.
	if ($ended !== '')
	{
		return array('text' => $ended, 'time' => '-1', 'ended' => $ended);
	}

	// Ended somewhere else (racer-wide set), no crossing here.
	if ($clock->isDnf($number))
	{
		return array('text' => 'DNF', 'time' => '-1', 'ended' => '');
	}
	if ($clock->isDsq($number))
	{
		// Legacy emitted the 'DSG' typo here; clean output is 'DSQ' (intentional fix).
		return array('text' => 'DSQ', 'time' => '-1', 'ended' => '');
	}

	return array('text' => '---', 'time' => '', 'ended' => '');
}

/**
 * Archive-specific display name: "Surname Firstname  (number) [local] [(team)]".
 * Reproduces the legacy timetrackerArchive::getRacerName composition where the BIB
 * NUMBER is part of the name. The display_local/text_local/display_team prefs now
 * live in the 'racers' plugin store (passed in). Surname/firstname/number/team are
 * escaped via toHTML (the legacy left surname/firstname raw - a stored-XSS surface;
 * for plain-ASCII names toHTML is a no-op so display parity holds - see NOTES.md).
 *
 * @param array $racer racer row
 * @param array $prefs 'racers' prefs
 * @return string HTML-safe display name
 */
function aktualne_racer_name($racer, $prefs)
{
	$tp = e107::getParser();

	$surname   = isset($racer['racer_surname'])   ? (string) $racer['racer_surname']   : '';
	$firstname = isset($racer['racer_firstname']) ? (string) $racer['racer_firstname'] : '';
	$number    = isset($racer['racer_number'])    ? (string) $racer['racer_number']    : '';

	$name = $tp->toHTML($surname, false, 'TITLE') . ' ' . $tp->toHTML($firstname, false, 'TITLE')
		. "  (" . $tp->toHTML($number, false, 'TITLE') . ") ";

	if (!empty($prefs['display_local']) && !empty($prefs['text_local']) && !empty($racer['racer_local']))
	{
		$name .= $tp->toHTML($prefs['text_local'], 'TITLE', true);
	}

	if (!empty($prefs['display_team']) && !empty($racer['racer_team']))
	{
		$team = $tp->toHTML($racer['racer_team'], 'TITLE', true);
		$name .= "&nbsp;<small><i>(" . $team . ")</i></small>";
	}

	return $name;
}

/**
 * Render ONE matrix row. Por. (col 0) is the rank DataTables sorts on. The "Cas"
 * column and every per-checkpoint cell carry a numeric data-order (raw elapsed
 * seconds) so the HH:MM / HH:MM:SS text never sorts lexicographically (same trick
 * as report_stu); blank/flag cells get a large sentinel so they sort last. The
 * name is already HTML-safe (built by aktualne_racer_name); all other text is
 * escaped here.
 *
 * @param array  $row
 * @param array  $cpCodes column checkpoint codes (excl. start)
 * @param object $tp      parser
 * @return string one <tr>
 */
function aktualne_render_row($row, $cpCodes, $tp)
{
	$sentinel = 999999999; // sort blanks / DNF / DSQ / "---" last on a time-column sort

	$html  = '<tr>';
	$html .= '<td>' . (int) $row['order'] . '</td>';
	$html .= '<td>' . $row['name'] . '</td>'; // already escaped
	$html .= '<td>' . $tp->toHTML((string) $row['kat'], false, 'TITLE') . '</td>';

	$casOrder = is_numeric($row['cassort']) ? (float) $row['cassort'] : $sentinel;
	$html .= "<td data-order='" . $casOrder . "'>" . $tp->toHTML((string) $row['cas'], false, 'TITLE') . '</td>';

	foreach ($cpCodes as $code)
	{
		$cell  = isset($row['points'][$code]['cas']) ? (string) $row['points'][$code]['cas'] : '';
		$rawS  = isset($row['points'][$code]['sort']) ? $row['points'][$code]['sort'] : null;
		$order = is_numeric($rawS) ? (float) $rawS : $sentinel;
		$html .= "<td data-order='" . $order . "'>" . $tp->toHTML($cell, false, 'TITLE') . '</td>';
	}

	$html .= '<td>' . $tp->toHTML((string) $row['catorder'], false, 'TITLE') . '</td>';
	$html .= '</tr>';

	return $html;
}
