<?php
/*
 * e107 website system
 *
 * racereports plugin - CUSTOM / SEGMENT (split) report between TWO arbitrary
 * points of ONE race (clean equivalent of timetracker/timetracker_custom.php).
 *
 * ONE race + TWO checkpoints (?trasa=<race_sef>&od=<point A code>&do=<point B
 * code>). Lists every racer's time ELAPSED BETWEEN point A (Od/from) and point B
 * (Do/to) - a SPLIT - ranked fastest-first (ASC). Columns: rank | bib (string) |
 * name | segment time. The page heading names the race and BOTH points
 * ("Od - <A> / Do - <B>").
 *
 * All time math comes from the racetiming engine: the segment is the ADDITIVE
 * race_clock::elapsedBetween($number, $od, $do) (savedTime(do) - savedTime(od),
 * full ms precision, null when either crossing is missing). This page does ONLY
 * presentation: resolve the race + both points, walk the racers, keep those with a
 * usable segment, rank them ASC and render. Output is escaped.
 *
 * LEGACY PARITY: the legacy custom page (timetracker_custom.php ->
 * get_racer_time_between_points) ranks ONLY the racers with a positive segment
 * time ($diff['time'] > 0); racers missing EITHER crossing are collected into a
 * no-time bucket that the legacy page NEVER echoes. This report mirrors that: a
 * racer with a null/<=0 segment is simply NOT shown. The legacy table is also NOT
 * coloured (its $racer_color is never assigned) and its ADMIN cells are dead
 * (empty $racer_point / $adminicon) - so this report is plain (no per-category
 * background) and carries NO admin column.
 *
 * SECURITY: the legacy raw-$_GET-into-SQL pattern and its extract($racer) are NOT
 * carried over. trasa/od/do go through $tp->toDB() (race/point resolution lives in
 * the shared helper, which quotes them); the bib/start number stays a STRING
 * (never (int)-cast - leading zeros are data); output is escaped via
 * $tp->toHTML(). NO DataTables, NO export.
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);
e107::lan('racereports', '', true); // front strings: languages/English/English_front.php

// Defensive fallbacks so a missing/odd LAN load path can never fatal a front page
// on an undefined constant (PHP 8). The loaded front file is the source of truth.
if (!defined('LAN_RACEREPORTS_NO_RACE'))  define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_NO_POINT')) define('LAN_RACEREPORTS_NO_POINT', 'No checkpoint selected.');
if (!defined('LAN_RACEREPORTS_OD'))       define('LAN_RACEREPORTS_OD', 'Od');
if (!defined('LAN_RACEREPORTS_DO'))       define('LAN_RACEREPORTS_DO', 'Do');

// Engine (racetiming) + shared report helper. Loaded by path so no <dependency>
// on racetiming is required (the skeleton deps are left unchanged).
require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map

// Legacy query contract: ?trasa (race_sef), ?od (point A code), ?do (point B code).
$raceSef = isset($_GET['trasa']) ? $tp->toDB($_GET['trasa']) : '';
$odCode  = isset($_GET['od'])    ? $tp->toDB($_GET['od'])    : '';
$doCode  = isset($_GET['do'])    ? $tp->toDB($_GET['do'])    : '';

if ($raceSef === '')
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}

$race = $report->fetchRaceBySef($raceSef);
if (empty($race))
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}
$raceId = (int) $race['race_id'];

// Resolve BOTH points (constrained to this race via FIND_IN_SET in the helper).
// Either one missing -> error panel (the segment is undefined).
$od = ($odCode !== '') ? $report->fetchPointByCode($raceId, $odCode) : false;
$do = ($doCode !== '') ? $report->fetchPointByCode($raceId, $doCode) : false;

if (empty($od) || empty($do))
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_POINT . "</p></div>";
	require_once(FOOTERF);
	exit;
}

// Heading: race name, then BOTH points named ("Od - <A> / Do - <B>").
echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . "</h2>";
echo "<h3>"
	. $tp->toHTML(LAN_RACEREPORTS_OD, false, 'TITLE') . " &ndash; "
	. $tp->toHTML($od['race_point_name'], false, 'TITLE')
	. " / "
	. $tp->toHTML(LAN_RACEREPORTS_DO, false, 'TITLE') . " &ndash; "
	. $tp->toHTML($do['race_point_name'], false, 'TITLE')
	. "</h3>";

render_segment_list($report, $clock, $raceId, (string) $od['race_point_code'], (string) $do['race_point_code']);

/**
 * Render the SEGMENT (split) table for one race between two points. Each racer's
 * segment is race_clock::elapsedBetween(number, $odCode, $doCode); only racers with
 * a usable positive segment are ranked (ASC, fastest first) and shown - racers
 * missing either crossing are dropped, exactly as the legacy custom page collects
 * but never echoes them. Plain rows (no per-category colour), no admin column.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param int         $raceId
 * @param string      $odCode race_point_code of the FROM point (Od)
 * @param string      $doCode race_point_code of the TO point (Do)
 */
function render_segment_list($report, $clock, $raceId, $odCode, $doCode)
{
	$tp = e107::getParser();

	// Central RESULT-time precision (DISPLAY-ONLY, truncates). Default 2 enforced
	// here at the consumer; the segtime sort key stays the full-precision float.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId); // keyed by bib string

	$ranked = array(); // racers with a usable segment -> ranked ASC

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;

		$seg = $clock->elapsedBetween($number, $odCode, $doCode);

		// Legacy predicate: include ONLY a positive segment ($diff['time'] > 0). A
		// missing crossing (null) or a non-positive split is dropped (the legacy
		// no-time bucket, collected but never echoed).
		if ($seg === null || $seg <= 0)
		{
			continue;
		}

		$racer['racer_name'] = $report->getRacerName($racer);
		$racer['segtime']    = $seg;                                  // full-precision sort key
		$racer['segtext']    = race_format::formatElapsed($seg, $resDec); // result_decimals (display-only)
		$ranked[] = $racer;
	}

	// Fastest-first: sort by the full-precision float (display-independent), matching
	// the legacy usort($racers2, $a['difftime'] <=> $b['difftime']).
	usort($ranked, function ($a, $b)
	{
		return $a['segtime'] <=> $b['segtime'];
	});

	echo '<div class="table-responsive">';
	echo "<table class='table table-striped table-bordered table-hover'>";

	$rank = 0;
	foreach ($ranked as $racer)
	{
		$rank++;
		echo "<tr>"; // NOT coloured (legacy custom table is plain)
		echo "<td>" . (int) $rank . "</td>";
		echo "<td>" . $tp->toHTML((string) $racer['racer_number'], false, 'TITLE') . "</td>";
		echo "<td>" . $racer['racer_name'] . "</td>";
		echo "<td>" . $tp->toHTML($racer['segtext'], false, 'TITLE') . "</td>";
		echo "</tr>";
	}

	echo "</table>";
	echo "</div>";
}

require_once(FOOTERF);
