<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_stu.php
 *
*/

/*
 * Results - SUT (docs/plugins/racereports.md, "Results — SUT"). ?p selects the track by
 * race_id. All time math comes from the racetiming engine
 * (race_clock::elapsedToPoint + race_format::formatElapsed); the page and the
 * ?export=csv|xls download render from ONE row structure (stu_build_rows()).
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// LAN + engine/report classes are loaded FIRST (before HEADERF / any output) so an
// ?export=csv|xls request can stream the file and exit at the top of the page,
// without emitting page chrome. The HTML path requires HEADERF further down.
e107::lan('racereports', '', true); // front strings: languages/English/English_front.php

require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

// Defensive fallbacks so a missing/odd LAN load path can never fatal a front page
// on an undefined constant (PHP 8). The loaded front file is the source of truth.
if (!defined('LAN_RACEREPORTS_NO_RACE'))      define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_SUT_TITLE'))    define('LAN_RACEREPORTS_SUT_TITLE', 'Results');
if (!defined('LAN_RACEREPORTS_SUT_NO_FINISH')) define('LAN_RACEREPORTS_SUT_NO_FINISH', 'No finishers yet.');
if (!defined('LAN_RACEREPORTS_COL_RANK'))     define('LAN_RACEREPORTS_COL_RANK', 'Ranking');
if (!defined('LAN_RACEREPORTS_COL_TIME'))     define('LAN_RACEREPORTS_COL_TIME', 'Time');
if (!defined('LAN_RACEREPORTS_COL_SURNAME'))  define('LAN_RACEREPORTS_COL_SURNAME', 'Family Name');
if (!defined('LAN_RACEREPORTS_COL_FIRSTNAME')) define('LAN_RACEREPORTS_COL_FIRSTNAME', 'First Name');
if (!defined('LAN_RACEREPORTS_COL_GENDER'))   define('LAN_RACEREPORTS_COL_GENDER', 'Gender');
if (!defined('LAN_RACEREPORTS_COL_BIRTHDATE')) define('LAN_RACEREPORTS_COL_BIRTHDATE', 'Birthdate');
if (!defined('LAN_RACEREPORTS_COL_NATIONALITY')) define('LAN_RACEREPORTS_COL_NATIONALITY', 'Nationality');
if (!defined('LAN_RACEREPORTS_COL_BIB'))      define('LAN_RACEREPORTS_COL_BIB', 'Bibnumber');
if (!defined('LAN_RACEREPORTS_EXPORT_CSV'))   define('LAN_RACEREPORTS_EXPORT_CSV', 'CSV');
if (!defined('LAN_RACEREPORTS_EXPORT_XLS'))   define('LAN_RACEREPORTS_EXPORT_XLS', 'XLS');
if (!defined('LAN_RACEREPORTS_NUM_DNF'))      define('LAN_RACEREPORTS_NUM_DNF', 'DNF');
if (!defined('LAN_RACEREPORTS_NUM_DSQ'))      define('LAN_RACEREPORTS_NUM_DSQ', 'DSQ');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// ?p selects a TRACK by race_id, (int)-cast.
$raceId = isset($_GET['p']) ? (int) $_GET['p'] : 0;
$race   = $raceId > 0 ? $report->fetchRaceById($raceId) : false;

// Build the row structure ONCE and feed both the export and the HTML table from
// it. Only built when a race resolves.
$built = !empty($race) ? stu_build_rows($report, $clock, $raceId) : null;

// EXPORT BRANCH: stream and exit BEFORE HEADERF / any output.
$export = isset($_GET['export']) ? (string) $_GET['export'] : '';
if ($built !== null && ($export === 'csv' || $export === 'xls'))
{
	require_once(e_PLUGIN . 'racereports/includes/race_export.php');

	// Filename: stu_<race_sef>_<Y-m-d>. race_sef is sanitised to a safe slug so it
	// can never inject into the Content-Disposition header.
	$slug  = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $race['race_sef']);
	$fname = 'stu_' . $slug . '_' . date('Y-m-d');

	if ($export === 'csv')
	{
		race_export::csv($fname, $built['headers'], $built['rows'], $built['textCols']); // exits
	}
	race_export::xls($fname, $built['headers'], $built['rows'], $built['textCols']); // exits
}

// ---- HTML PATH ----------------------------------------------------------------
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);

// Sortable columns (DataTables, see race_report_load_datatables()). The Time
// column sorts by a NUMERIC data-order key, see below.
race_report_load_datatables();

if (empty($race))
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}

echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
	. " &mdash; " . $tp->toHTML(LAN_RACEREPORTS_SUT_TITLE, false, 'TITLE') . "</h2>";

render_stu_list($built, $raceId);

/**
 * Build the results of one track as a NEUTRAL structure that both the HTML table
 * and the CSV/XLS export render from (row order: docs, "Results — SUT").
 *
 * Returns:
 *   headers   : ordered display labels (the report's own column labels)
 *   rows      : array of rows; each row = ordered array of display-formatted cells
 *               [rank, time, surname, firstname, gender, birthdate, nationality, bib]
 *   textCols  : indices of columns to force to TEXT on export (time + start number)
 *   meta      : per-row HTML-only decoration, parallel to rows:
 *                 order = raw elapsed seconds (DataTables numeric sort key; DNF/DSQ
 *                         rows get a large sentinel so they sort AFTER finishers)
 *                 color = per-category background ('' when colours off; always ''
 *                         for DNF/DSQ - ended rows are never coloured)
 *   useColors : whether the per-category row background is on
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param int         $raceId
 * @return array
 */
function stu_build_rows($report, $clock, $raceId)
{
	// stu_decimals (default 0 = HH:MM:SS); max(0, …) guards a stray negative value.
	$dec = max(0, (int) e107::getPlugConfig('racereports')->get('stu_decimals', 0));

	// stu_colors, default 0 (off) applied here at the consumer.
	$useColors = (int) e107::getPlugConfig('racereports')->get('stu_colors', 0);

	$racers = $report->fetchRacers($raceId); // keyed by bib string

	$finishers = array();
	$finished  = array(); // bib-string set of clean finishers (to skip in the DNF/DSQ pass)

	foreach ($racers as $number => $racer)
	{
		// Only racer_active racers are listed.
		if (empty($racer['racer_active']))
		{
			continue;
		}

		// The engine's ranking-safe elapsed start->finish; null = no clean finish
		// (DNF/DSQ racers are appended in the second pass below).
		$elapsed = $clock->elapsedToPoint($number, 'finish');
		if ($elapsed === null)
		{
			continue;
		}

		$finished[(string) $number] = true;

		// Display-only (truncated to $dec digits); the sort key stays the
		// full-precision float.
		$racer['stu_time'] = race_format::formatElapsed($elapsed, $dec);
		$racer['stu_sort'] = $elapsed;

		$finishers[] = $racer;
	}

	// Sort finishers by final time ASC (full-precision float, display-independent).
	usort($finishers, function ($a, $b)
	{
		return $a['stu_sort'] <=> $b['stu_sort'];
	});

	// SECOND PASS - DNF, then DSQ (engine isDnf/isDsq), each in fetchRacers() order,
	// skipping clean finishers. racer_active only, as above.
	$dnfRacers = array();
	$dsqRacers = array();

	foreach ($racers as $number => $racer)
	{
		if (empty($racer['racer_active']))
		{
			continue;
		}
		if (isset($finished[(string) $number]))
		{
			continue; // a clean finisher is never also listed as DNF/DSQ
		}

		// DNF takes precedence over DSQ if a racer somehow carries both markers, so
		// they appear once. The marker string is the Time-cell text for that row.
		if ($clock->isDnf($number))
		{
			$racer['stu_marker'] = LAN_RACEREPORTS_NUM_DNF;
			$dnfRacers[] = $racer;
		}
		elseif ($clock->isDsq($number))
		{
			$racer['stu_marker'] = LAN_RACEREPORTS_NUM_DSQ;
			$dsqRacers[] = $racer;
		}
	}

	// The ordered tail: all DNF rows, then all DSQ rows.
	$ended = array_merge($dnfRacers, $dsqRacers);

	$headers = array(
		LAN_RACEREPORTS_COL_RANK,
		LAN_RACEREPORTS_COL_TIME,
		LAN_RACEREPORTS_COL_SURNAME,
		LAN_RACEREPORTS_COL_FIRSTNAME,
		LAN_RACEREPORTS_COL_GENDER,
		LAN_RACEREPORTS_COL_BIRTHDATE,
		LAN_RACEREPORTS_COL_NATIONALITY,
		LAN_RACEREPORTS_COL_BIB,
	);

	$rows = array();
	$meta = array();
	$rank = 0;

	foreach ($finishers as $racer)
	{
		$rank++;

		// The SINGLE formatted cell set, consumed verbatim by both HTML and export.
		// The bib stays a string (leading zeros preserved).
		$rows[] = array(
			$rank,                                                                          // 0 rank
			$racer['stu_time'],                                                             // 1 time (TEXT col)
			isset($racer['racer_surname']) ? $racer['racer_surname'] : '',                  // 2 surname
			isset($racer['racer_firstname']) ? $racer['racer_firstname'] : '',              // 3 first name
			isset($racer['racer_gender']) ? $racer['racer_gender'] : '',                    // 4 gender
			stu_format_birthdate(isset($racer['racer_birthday']) ? $racer['racer_birthday'] : ''), // 5 birthdate
			isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',          // 6 nationality
			(string) $racer['racer_number'],                                                // 7 bib (TEXT col)
		);

		$meta[] = array(
			// DataTables numeric sort key for the Time column (full-precision float).
			'order' => isset($racer['stu_sort']) ? (float) $racer['stu_sort'] : PHP_INT_MAX,
			// Per-category background (only used when colours are on).
			'color' => $useColors ? race_report_color($clock, $racer) : '',
		);
	}

	// DNF/DSQ rows: no elapsed to sort by, so the DataTables sort key is a sentinel
	// (PHP_INT_MAX) that keeps them after every finisher; never coloured.
	foreach ($ended as $racer)
	{
		$rank++;

		$rows[] = array(
			$rank,                                                                          // 0 rank
			$racer['stu_marker'],                                                           // 1 time -> "DNF"/"DSQ" (TEXT col)
			isset($racer['racer_surname']) ? $racer['racer_surname'] : '',                  // 2 surname
			isset($racer['racer_firstname']) ? $racer['racer_firstname'] : '',              // 3 first name
			isset($racer['racer_gender']) ? $racer['racer_gender'] : '',                    // 4 gender
			stu_format_birthdate(isset($racer['racer_birthday']) ? $racer['racer_birthday'] : ''), // 5 birthdate
			isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',          // 6 nationality
			(string) $racer['racer_number'],                                                // 7 bib (TEXT col)
		);

		$meta[] = array(
			'order' => PHP_INT_MAX, // no time -> always sort after finishers
			'color' => '',          // ended rows are never coloured
		);
	}

	return array(
		'headers'   => $headers,
		'rows'      => $rows,
		// Force the time (1) and start number (7) to TEXT on export so a spreadsheet
		// keeps "0042" and "12:17:55.48" verbatim instead of reinterpreting them.
		'textCols'  => array(1, 7),
		'meta'      => $meta,
		'useColors' => $useColors,
	);
}

/**
 * Render the results table of one track from stu_build_rows(). The cells are the
 * SAME ones the export streams; this only adds the HTML decoration (data-order
 * sort key, optional row colour, the export buttons).
 *
 * @param array $built  the stu_build_rows() structure
 * @param int   $raceId for the export button URLs
 */
function render_stu_list($built, $raceId)
{
	$tp = e107::getParser();

	$headers   = $built['headers'];
	$rows      = $built['rows'];
	$meta      = $built['meta'];
	$useColors = $built['useColors'];

	// Export buttons: plugin path, so ?p and ?export survive regardless of SEF.
	$base   = e_PLUGIN_ABS . 'racereports/report_stu.php?p=' . (int) $raceId;
	$csvUrl = $base . '&export=csv';
	$xlsUrl = $base . '&export=xls';

	echo "<div class='btn-group' style='margin-bottom:8px;'>";
	echo "<a class='btn btn-default btn-csv' href='" . $tp->toAttribute($csvUrl) . "'>"
		. $tp->toHTML(LAN_RACEREPORTS_EXPORT_CSV, false) . "</a>";
	echo "<a class='btn btn-default btn-excel' href='" . $tp->toAttribute($xlsUrl) . "'>"
		. $tp->toHTML(LAN_RACEREPORTS_EXPORT_XLS, false) . "</a>";
	echo "</div>";

	echo '<div class="table-responsive">';
	// Stable hook #report_stu for assets/datatables/init.js.
	echo "<table id='report_stu' class='table table-striped table-bordered table-hover race-report'>";

	echo "<thead><tr>";
	foreach ($headers as $header)
	{
		echo "<th>" . $tp->toHTML($header, false) . "</th>";
	}
	echo "</tr></thead><tbody>";

	if (empty($rows))
	{
		echo "<tr><td colspan='8'><em>"
			. $tp->toHTML(LAN_RACEREPORTS_SUT_NO_FINISH, false) . "</em></td></tr>";
	}

	foreach ($rows as $i => $row)
	{
		// Row colour only when stu_colors is on (DNF/DSQ rows carry '').
		if ($useColors)
		{
			$color = isset($meta[$i]['color']) ? $meta[$i]['color'] : '';
			echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
		}
		else
		{
			echo "<tr>";
		}
		echo "<td>" . (int) $row[0] . "</td>";
		// Time column: display the formatted cell, but let DataTables sort by
		// data-order = raw elapsed seconds (a string sort breaks once widths differ,
		// e.g. stu_decimals > 0). The (float) cast makes escaping unnecessary.
		$rawSeconds = isset($meta[$i]['order']) ? (float) $meta[$i]['order'] : PHP_INT_MAX;
		echo "<td data-order='" . $rawSeconds . "'>" . $tp->toHTML($row[1], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[2], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[3], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[4], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[5], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[6], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($row[7], false, 'TITLE') . "</td>";
		echo "</tr>";
	}

	echo "</tbody></table>";
	echo "</div>";
}

/**
 * Birthdate normalisation: a stored "19.2.1996" (dotted, spaces allowed) becomes
 * "1996-02-19"; an unparseable value is passed through unchanged (escaped at
 * output).
 *
 * @param string $birthday raw racer_birthday
 * @return string normalised Y-m-d, or the original string when unparseable
 */
function stu_format_birthdate($birthday)
{
	$raw = (string) $birthday;
	if ($raw === '')
	{
		return '';
	}

	$dateStr   = str_replace(' ', '', $raw);   // "19.2.1996"
	$dateStr   = str_replace('.', '-', $dateStr); // "19-2-1996"
	$timestamp = strtotime($dateStr);

	if ($timestamp === false)
	{
		return $raw;
	}

	return date('Y-m-d', $timestamp);
}

/**
 * Per-category row background colour (race_category.race_category_color), static
 * cache for the request. Each report page defines its own copy.
 */
function race_report_color($clock, $racer)
{
	static $cats = null;
	if ($cats === null)
	{
		$cats = e107::getDb()->retrieve('race_category', '*', true, true, 'race_category_id');
		if (!is_array($cats)) $cats = array();
	}
	$cid = isset($racer['racer_category_id']) ? (int) $racer['racer_category_id'] : 0;

	return isset($cats[$cid]['race_category_color']) ? $cats[$cid]['race_category_color'] : '';
}

require_once(FOOTERF);
