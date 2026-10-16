<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_custom.php
 *
*/

/*
 * Segment (Od-Do) report (docs/plugins/racereports.md, "Segment (Od–Do)"). Query:
 * ?trasa=<race_sef>&od=<point code>&do=<point code>. The segment is the engine's
 * race_clock::elapsedBetween() (savedTime(do) - savedTime(od), full ms precision,
 * null when either crossing is missing).
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

// Engine (racetiming) + shared report helper.
require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map

// ?trasa (race_sef), ?od (point A code), ?do (point B code).
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
 * Render the SEGMENT table for one race between two points: only racers with a
 * positive segment, fastest first. Plain rows, no admin column.
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

	// result_decimals (display-only, truncates); segtime stays the full float.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId); // keyed by bib string

	$ranked = array(); // racers with a usable segment -> ranked ASC

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;

		$seg = $clock->elapsedBetween($number, $odCode, $doCode);

		// Only a positive segment; a missing crossing (null) or a non-positive
		// split is dropped.
		if ($seg === null || $seg <= 0)
		{
			continue;
		}

		$racer['racer_name'] = $report->getRacerName($racer);
		$racer['segtime']    = $seg;                                  // full-precision sort key
		$racer['segtext']    = race_format::formatElapsed($seg, $resDec); // result_decimals (display-only)
		$ranked[] = $racer;
	}

	// Fastest first, by the full-precision float.
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
		echo "<tr>"; // not coloured
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
