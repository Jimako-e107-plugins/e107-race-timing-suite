<?php
/*
 * e107 website system
 *
 * racereports plugin - SUT report (clean equivalent of timetracker/stu.php).
 *
 * Per-TRACK FINISHERS-ONLY results list, produced for central reporting after a
 * race closes. ONE final time per racer = elapsed start->finish. Only racers with
 * a clean finish (status OK at the 'finish' checkpoint) are listed; DNF/DSQ/DNS
 * and any racer without a usable finish crossing are intentionally excluded
 * (FINISHERS ONLY - this is the documented scope difference from the legacy stu
 * page, which also printed DNF/DSQ blocks). Finishers are sorted by final time
 * ASCENDING.
 *
 * Legacy contract mirrored: ?p selects a TRACK by race_id (timetracker/stu.php:36
 * `WHERE race_id=$_GET['p']`; timetracker/e_url.php 'stu' sef `{alias}/{race_id}/`).
 *
 * All time math comes from the racetiming engine (race_clock::elapsedToPoint +
 * race_format::formatElapsed) - NO new engine ops are added; this page does ONLY
 * presentation: fetch racers, ask the engine for the finish elapsed, sort, render.
 * Output is escaped.
 *
 * Columns (legacy stu order, timetrackerStu_class.php:234-253 / :269-290):
 *   Ranking | Time | Family Name | First Name | Gender | Birthdate | Nationality
 *   | Bibnumber(string)
 *
 * Coloring: per-category row background is OPT-IN via the stu_colors pref
 * (default OFF = clean list). When ON, ranked/normal racers get the per-category
 * background via the shared race_report_color() path (same as report_online.php /
 * report_point.php). Every row here is a finisher, hence ranked, hence colorable.
 *
 * SECURITY: the legacy raw-$_GET-into-SQL pattern is NOT carried over - ?p is
 * (int)-cast (it is a race_id), every SQL value goes through $tp->toDB() in the
 * shared helper, the bib stays a quoted string, and output is escaped via
 * $tp->toHTML()/toAttribute(). No writes, no result-freeze (out of scope).
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

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// ?p selects a TRACK by race_id (legacy parity). It is an int, so (int)-cast.
$raceId = isset($_GET['p']) ? (int) $_GET['p'] : 0;
$race   = $raceId > 0 ? $report->fetchRaceById($raceId) : false;

// Build the neutral structure ONCE (headers + display-formatted rows + the
// text-protected column indices) and feed BOTH the export and the HTML table from
// it - there is no second, divergent row set. Only built when a race resolves.
$built = !empty($race) ? stu_build_rows($report, $clock, $raceId) : null;

// EXPORT BRANCH: stream and exit BEFORE HEADERF / any output. The cells are exactly
// what the HTML table shows (same start-number string, same time string), so the
// file is byte-identical to the page. The start number (leading zeros) and the time
// string are marked as text columns so spreadsheets do not mangle them.
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

// Sortable columns for the finishers table. CENTRAL opt-in loader (one call);
// the assets + init are registered once in includes/race_report.php. stu is the
// only report enabling DataTables today (online is live/auto-refresh; point left
// as-is). The Time column sorts by a NUMERIC data-order key, see below.
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
 * Build the finishers-only results for one track as a NEUTRAL structure that both
 * the HTML table and the CSV/XLS export render from. Keep only racers with a clean
 * finish (engine status OK at 'finish' -> elapsedToPoint returns a float), sort by
 * that elapsed ASC, rank 1..N and format each cell EXACTLY as displayed.
 *
 * Returns:
 *   headers   : ordered display labels (the report's own column labels)
 *   rows      : array of rows; each row = ordered array of display-formatted cells
 *               [rank, time, surname, firstname, gender, birthdate, nationality, bib]
 *   textCols  : indices of columns to force to TEXT on export (time + start number)
 *   meta      : per-row HTML-only decoration, parallel to rows:
 *                 order = raw elapsed seconds (DataTables numeric sort key)
 *                 color = per-category background ('' when colours off)
 *   useColors : whether the per-category row background is on
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param int         $raceId
 * @return array
 */
function stu_build_rows($report, $clock, $raceId)
{
	// Finish-time decimal places, from the plugin prefs (admin SETTINGS page).
	// Unset/absent pref => 0 => formatElapsed cuts to whole seconds (HH:MM:SS),
	// byte-for-byte the legacy render. max(0, …) guards a stray negative value.
	$dec = max(0, (int) e107::getPlugConfig('racereports')->get('stu_decimals', 0));

	// Per-category row coloring toggle (admin SETTINGS page). Default 0 = OFF is
	// enforced HERE at the consumer (not in the $prefs definition), so the report
	// renders clean - no category backgrounds - before the admin ever opens
	// settings. ON restores the per-category background via race_report_color().
	$useColors = (int) e107::getPlugConfig('racereports')->get('stu_colors', 0);

	$racers = $report->fetchRacers($raceId); // keyed by bib string

	$finishers = array();

	foreach ($racers as $number => $racer)
	{
		// Legacy stu lists only racer_active racers (timetrackerStu_class.php:48).
		if (empty($racer['racer_active']))
		{
			continue;
		}

		// FINISHERS ONLY: the engine's ranking-safe total elapsed start->finish.
		// null = no clean finish (no start, no finish crossing, or DNF/DSQ/DNS) ->
		// excluded. NO new engine op is introduced (elapsedToPoint already exists).
		$elapsed = $clock->elapsedToPoint($number, 'finish');
		if ($elapsed === null)
		{
			continue;
		}

		// Time cell mirrors legacy stu byte-for-byte: legacy took
		// substr(secondsToTime(elapsed), 0, 8) = "HH:MM:SS"
		// (timetrackerStu_class.php:221 over get_racer_time_on_point 'archive').
		// formatElapsed(elapsed, 0) is exactly that 8-char cut. Display-only; the
		// sort key stays the full-precision float. $dec defaults to 0 (unset pref)
		// so the render is byte-for-byte the legacy cut unless the organizer opts in.
		$racer['stu_time'] = race_format::formatElapsed($elapsed, $dec);
		$racer['stu_sort'] = $elapsed;

		$finishers[] = $racer;
	}

	// Sort finishers by final time ASC (full-precision float, display-independent).
	usort($finishers, function ($a, $b)
	{
		return $a['stu_sort'] <=> $b['stu_sort'];
	});

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
		// The bib stays a string (leading zeros preserved); the time is the already
		// formatted string; the birthdate goes through the legacy normalisation.
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
 * Render the finishers-only results table for one track from the neutral structure
 * built by stu_build_rows(). The cells are the SAME ones the export streams; this
 * function only adds the HTML decoration (DataTables data-order sort key, optional
 * per-category row background, the export button group). The visible table cells
 * are byte-for-byte the pre-refactor output.
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

	// Export button group above the table (HTML view only). Links re-request THIS
	// report with &export=csv / &export=xls appended; the plugin path keeps both the
	// ?p (track) and ?export params regardless of SEF. btn-csv / btn-excel mirror the
	// users.php export-button look.
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
	// Stable hook (#report_stu) + race-report class for the central DataTables init
	// (assets/datatables/init.js targets #report_stu). thead/tbody are the existing
	// 8-col structure - only the hook is added.
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
		// All rows are finishers (ranked). Per-category background is now opt-in
		// via the stu_colors pref (default OFF = clean table). When ON, the same
		// race_report_color() path as the online/point reports applies. (SUT lists
		// finishers only - DNF/DSQ/ended rows are already excluded upstream, so the
		// "ended rows never coloured" rule holds trivially regardless of the toggle.)
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
		// Time column: DISPLAY the formatted HH:MM:SS[.mmm] cell, but make DataTables
		// SORT by a numeric key via native data-order = the raw full-precision elapsed
		// seconds (the engine float in meta['order']). Lexicographic sort of the formatted
		// string breaks once widths differ (stu_decimals>0, or sub/over-hour lengths);
		// sorting by seconds keeps the row order identical regardless of stu_decimals.
		// (float) cast guarantees a numeric attribute value - no escaping needed.
		// Defensive sentinel for a missing time (stu is finishers-only, so N/A here):
		// a large value sorts any blank cell consistently last.
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
 * Reproduce the legacy stu birthdate normalisation
 * (timetrackerStu_class.php:110-122): a stored "19.2.1996" (dotted, possibly with
 * spaces) is rendered as "1996-02-19". On an unparseable value the legacy emitted
 * the literal "Zlý formát"; here the original string is passed through unchanged
 * (escaped at output) rather than a hardcoded marker - presentation-only.
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
 * Per-category row background colour (race_category.race_category_color), looked
 * up the same way the legacy pages did. Static cache for the request. Identical
 * to the helper in report_online.php / report_point.php.
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
