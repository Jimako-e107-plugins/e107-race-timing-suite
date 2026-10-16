<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - includes/aktualne_build.php
 *
*/

/*
 * Full results (aktualne) matrix builder; column and row rules in
 * docs/plugins/racereports.md, "Full results (aktuálne)". It only DEFINES
 * functions and RETURNS ['html', 'data'] without echoing, so it is required by:
 *   - report_aktualne.php (echoes the html);
 *   - racetrack/includes/race_archive_generate.php (stores the html and the
 *     serialized data as the archive snapshot).
 * The caller must load the racetiming engine (race_clock + race_format) first; this
 * file only requires racers_name.php. The table id #report_aktualne is what
 * assets/datatables/init.js binds to (racetrack's archive page loads the same
 * DataTables library with its own init).
 *
 * Race id is (int)-cast; the bib stays a STRING; SQL values go through toDB() (here or
 * in the engine); output is escaped. No writes.
 */

if (!defined('e107_INIT'))
{
	exit;
}

// Shared racer display-name builder (aktualne_racer_name delegates to it).
require_once(e_PLUGIN . 'racers/includes/racers_name.php');

/**
 * Build the full per-race results matrix. RETURNS ['html', 'data'] - does not echo.
 *
 * Port of the old timetracker archive onto the racetiming engine: three sections
 * NORMAL / DNF / DSQ (engine isDnf/isDsq), each ranked by its ordertime but
 * emitted in racer_number order - DataTables then sorts the visible table by the
 * Por. column (col 0 asc).
 *
 * @param int         $raceId race id (caller int-casts; re-cast here defensively)
 * @param race_clock  $clock  built engine
 * @param race_report $report report helper (fetch + escaping)
 * @return array ['html' => string, 'data' => array]
 */
function racereports_aktualne_build($raceId, $clock, $report)
{
	// Load our own front LAN: the racetrack archive path does not, and the
	// LAN_RACEREPORTS_AKT_* constants would be undefined (fatal on PHP 8). Load-once,
	// so the second call from report_aktualne.php is a no-op. The define()
	// fallbacks cover an odd LAN path.
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

	// 'racers' name-display prefs (passed to aktualne_racer_name, which no longer
	// uses them - racers_name reads the same store itself).
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

	// Checkpoints of THIS race in course order (ASC), keyed by code;
	// race_report::fetchCheckpoints() is DESC, so read them here directly.
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

	// The DNF ordertime formula counts checkpoints INCLUDING start.
	$checkpointCount = count($checkpoints);

	// Racers of this race (racer_active), keyed by bib STRING, ORDER BY racer_number.
	$racers = aktualne_fetch_racers($raceId);

	// Partition NORMAL / DNF / DSQ. Exclusive: a racer flagged both DNF and DSQ is
	// listed once, as DNF (the old timetracker archive printed it in both blocks).
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

	// The Por. counter is CUMULATIVE across sections (order base = rows so far).
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
		// The checkpoint code is the column header (e.g. "K2", "finish").
		$ths .= '<th>' . $tp->toHTML($code, false, 'TITLE') . '</th>';
	}
	$ths .= '<th>' . $tp->toHTML(LAN_RACEREPORTS_AKT_COL_CATRANK, false) . '</th>';

	$colspan = 4 + count($cpCodes) + 1;

	// Stable hook #report_aktualne for assets/datatables/init.js.
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

	// ---- DATA (structured; stored with the archive snapshot) ---------------
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
 * racer_number.
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
 * Process ONE section (0 = NORMAL, 1 = DNF, 2 = DSQ) into rows keyed by bib, in the
 * ORIGINAL bib order; the Por. / catorder values carry the computed rank.
 *
 * @param race_clock $clock
 * @param array      $racers          [number => racer] for this section
 * @param int        $sectionType     0|1|2
 * @param array      $checkpoints     [code => race_point row] ASC (incl. start)
 * @param array      $cpCodes         column checkpoint codes (excl. start) ASC
 * @param int        $checkpointCount count incl. start (DNF ordertime)
 * @param array      $cats            [id => race_category row]
 * @param array      $prefs           'racers' name-display prefs
 * @param int        $orderBase       cumulative Por. base (count of prior sections)
 * @return array [number => row]
 */
function aktualne_process_section($clock, $racers, $sectionType, $checkpoints, $cpCodes, $checkpointCount, $cats, $prefs, $orderBase)
{
	$rows = array();

	// 1) Base cells, "Cas" (Time) and the sort key (ordertime).
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

		$catId = isset($racer['racer_category_id']) ? (int) $racer['racer_category_id'] : 0;
		if (isset($cats[$catId]))
		{
			$row['catid'] = $catId;
			$row['kat']   = isset($cats[$catId]['race_category_name']) ? $cats[$catId]['race_category_name'] : '';
		}

		// DNF: ordertime = (checkpoints - DNF point order) * 1e6 (+ the time at that
		// point, step 2), so the furthest DNF ranks first; "Cas" = DNF.
		if ($sectionType === 1)
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

			// Keep only a numeric time >= 0 with non-empty text (a no-finish cell has
			// text '-'/'---' and a non-numeric time). Written without PHP 8 loose
			// comparisons.
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

	// 2) Per-checkpoint HH:MM cells, blank after the DNF point, and the DNF
	//    ordertime += time at the DNF point.
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
					// After the DNF marker point: BLANK.
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

	// 3) Rank by ordertime ASC (stable), assign Por. (cumulative) and catorder
	//    (per-category, BLANK for DNF/DSQ).
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
 * Time cell of a racer at a point, archive variant (cf. race_report::diffOnPoint).
 * Returns ['text'=>..., 'time'=>..., 'ended'=>...]:
 *   - no usable start crossing -> text "-", time "";
 *   - usable crossing at the point -> the elapsed cut to tenths; when an ended
 *     marker is stamped here, the time is blanked ('') and, at the 'finish' point
 *     ONLY, the text is replaced by the marker;
 *   - no crossing but an ended marker stamped here -> text = marker, time "-1";
 *   - ended elsewhere (racer-wide DNF/DSQ) -> "DNF" / "DSQ", time "-1";
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
		// HARDCODED 1 decimal on purpose: this matrix is not governed by the
		// result_decimals pref (the cells are cut to HH:MM / HH:MM:SS anyway).
		$text = race_format::formatElapsed($elapsed, 1);
		$time = $elapsed;

		if ($ended !== '')
		{
			// Ended here: time blanked; at finish the text becomes the marker,
			// elsewhere the time text stays.
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
		return array('text' => 'DSQ', 'time' => '-1', 'ended' => '');
	}

	return array('text' => '---', 'time' => '', 'ended' => '');
}

/**
 * Matrix display name: "Surname Firstname  (number) [local] [(team)]" - the bib
 * is part of the name. Delegates to racers_name::build()
 * (racers/includes/racers_name.php), the single place the name is composed:
 * name_hash NOT applied, team appended when the display_team pref is on. HTML-safe.
 *
 * @param array $racer racer row
 * @param array $prefs 'racers' prefs - UNUSED, kept so the caller's signature does
 *                     not change (racers_name reads the same store itself)
 * @return string HTML-safe display name
 */
function aktualne_racer_name($racer, $prefs)
{
	return racers_name::build((array) $racer, array('bib' => true, 'hash' => false, 'team' => true));
}

/**
 * Render ONE matrix row. The Time column and every checkpoint cell carry a numeric
 * data-order (raw elapsed seconds) so DataTables never sorts the HH:MM text
 * lexicographically; blank/flag cells get a large sentinel so they sort last. The
 * name is already HTML-safe; all other text is escaped here.
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
