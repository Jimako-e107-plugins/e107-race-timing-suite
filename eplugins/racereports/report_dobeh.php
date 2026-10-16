<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_dobeh.php
 *
*/

/*
 * Arrivals board (docs/plugins/racereports.md, "Arrivals board"). Query:
 * ?r=<race_sef>&p=<race_point_sef>, komplet allowed for both.
 * Display order = arrival (race_clock::timeOfDay, the absolute crossing time) DESC;
 * the in-category rank uses the ELAPSED time, so it does not depend on the display
 * order. Time cell = race_report::diffOnPoint at result_decimals, as in the
 * checkpoint report.
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
define('e_IFRAME', true);

// Auto-refresh (docs/plugins/racereports.md, "Tab: Refresh"): ?refresh wins even
// when 0 ("off for this window"), hence isset(), not ?:. e107::meta() MUST run
// before HEADERF, or the refresh meta never reaches <head>.
$refresh = isset($_GET['refresh'])
	? (int) e107::getParser()->filter($_GET['refresh'], 'int')                   // URL present -> wins, even 0
	: (int) e107::getPlugConfig('racereports')->get('dobeh_refresh_interval', 0); // else pref
if ($refresh > 0)
{
	e107::meta(null, $refresh, array('http-equiv' => 'refresh'));
}

require_once(HEADERF);
e107::lan('racereports', '', true); // front strings: languages/English/English_front.php

// Defensive fallbacks so a missing/odd LAN load path can never fatal a front page
// on an undefined constant (PHP 8). The loaded front file is the source of truth.
if (!defined('LAN_RACEREPORTS_NO_RACE'))      define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_NO_POINT'))     define('LAN_RACEREPORTS_NO_POINT', 'No checkpoint selected.');
if (!defined('LAN_RACEREPORTS_COL_BIB'))      define('LAN_RACEREPORTS_COL_BIB', 'Bibnumber');
if (!defined('LAN_RACEREPORTS_COL_NAME'))     define('LAN_RACEREPORTS_COL_NAME', 'Name');
if (!defined('LAN_RACEREPORTS_COL_CATEGORY')) define('LAN_RACEREPORTS_COL_CATEGORY', 'Category');
if (!defined('LAN_RACEREPORTS_COL_TIME'))     define('LAN_RACEREPORTS_COL_TIME', 'Time');

// Engine (racetiming) + shared report helper.
require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

$raceSef = isset($_GET['r']) ? $tp->toDB($_GET['r']) : '';
$pointId = isset($_GET['p']) ? $tp->toDB($_GET['p']) : '';

if ($raceSef === '')
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}

if ($raceSef === 'komplet')
{
	// Every race, one board per checkpoint (skipping start/finish).
	foreach ($report->fetchAllRaces() as $race)
	{
		$raceId = (int) $race['race_id'];
		foreach ($report->fetchCheckpoints($raceId) as $point)
		{
			if ($point['race_point_code'] === 'start') continue;
			if ($point['race_point_code'] === 'finish') continue;

			echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
				. " - " . $tp->toHTML($point['race_point_name'], false, 'TITLE') . "</h2>";

			render_dobeh_list($report, $clock, $raceId, $point);
		}
	}

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

if ($pointId === '')
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_POINT . "</p></div>";
	require_once(FOOTERF);
	exit;
}

if ($pointId === 'komplet')
{
	// Every checkpoint of the race except start/finish.
	foreach ($report->fetchCheckpoints($raceId) as $point)
	{
		if ($point['race_point_code'] === 'start') continue;
		if ($point['race_point_code'] === 'finish') continue;

		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
			. " - " . $tp->toHTML($point['race_point_name'], false, 'TITLE') . "</h2>";

		render_dobeh_list($report, $clock, $raceId, $point);
	}
}
else
{
	$point = $report->fetchPointByCode($raceId, $pointId);
	if (empty($point))
	{
		echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_POINT . "</p></div>";
		require_once(FOOTERF);
		exit;
	}

	echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
		. " - " . $tp->toHTML($point['race_point_name'], false, 'TITLE') . "</h2>";

	render_dobeh_list($report, $clock, $raceId, $point);
}

/**
 * Render one checkpoint ARRIVALS board: racers with a valid crossing here
 * (isRanked), in-category rank by ELAPSED time, display sorted by arrival DESC,
 * # column counting down.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param int         $raceId
 * @param array       $point  race_point row (uses race_point_code)
 */
function render_dobeh_list($report, $clock, $raceId, $point)
{
	$tp      = e107::getParser();
	$pointId = (string) $point['race_point_code'];

	// result_decimals (display-only, truncates), passed explicitly to diffOnPoint.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers  = $report->fetchRacers($raceId); // keyed by bib string
	$arrived = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		$racer['racer_name'] = $report->getRacerName($racer);

		$diff = $report->diffOnPoint($clock, $number, $pointId, $resDec);

		// "Arrived": a valid, non-ended crossing here.
		if (!$report->isRanked($diff))
		{
			continue;
		}

		$racer['difftime'] = $diff['time'];
		// Time cell = text . " " . ended (always empty here; the trailing space is
		// kept from the old timetracker output).
		$racer['difftext'] = $diff['text'] . " " . $diff['ended'];
		// Arrival sort key: the absolute crossing epoch; non-null for a valid
		// crossing.
		$racer['crossed_at'] = (float) $clock->timeOfDay($number, $pointId);

		$arrived[] = $racer;
	}

	// Rank-within-category by elapsed time ascending (fastest = 1st), computed BEFORE
	// the display sort so each row shows "Category — Nth" regardless of arrival order.
	$categoryRanks = dobeh_compute_category_ranks($arrived);

	// Display sort: latest crossing first (arrival DESC).
	usort($arrived, function ($a, $b)
	{
		return $b['crossed_at'] <=> $a['crossed_at'];
	});

	echo '<div class="table-responsive">';
	echo "<table class='table table-striped table-bordered table-hover'>";

	echo "<thead><tr>";
	echo "<th>#</th>";
	echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_COL_BIB, false) . "</th>";
	echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_COL_NAME, false) . "</th>";
	echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_COL_CATEGORY, false) . "</th>";
	echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_COL_TIME, false) . "</th>";
	echo "</tr></thead><tbody>";

	// Rank column counts DOWN: newest arrival (top row) shows the current total,
	// the first racer to cross (bottom row) shows 1.
	$displayOrder = count($arrived);
	foreach ($arrived as $racer)
	{
		render_dobeh_row($tp, $racer, $categoryRanks, $displayOrder);
		$displayOrder--;
	}

	echo "</tbody></table>";
	echo "</div>";
}

/**
 * Map racer_number => rank within its category, by ELAPSED time at the point
 * ascending (fastest = 1st).
 *
 * @param array $arrived arrived racers, each carrying 'difftime' + 'racer_category_id'
 * @return array [racer_number(string) => int rank]
 */
function dobeh_compute_category_ranks(array $arrived)
{
	$byCat = array();
	foreach ($arrived as $racer)
	{
		$catId = (int) $racer['racer_category_id'];
		$byCat[$catId][] = $racer;
	}

	$ranks = array();
	foreach ($byCat as $group)
	{
		usort($group, function ($a, $b)
		{
			return $a['difftime'] <=> $b['difftime'];
		});

		$rank = 1;
		foreach ($group as $racer)
		{
			$ranks[(string) $racer['racer_number']] = $rank;
			$rank++;
		}
	}

	return $ranks;
}

/**
 * Render one arrival row: # | bib | name | "Category — N." | time, coloured by
 * category. The bib stays a STRING; every cell is escaped.
 *
 * @param e_parse $tp
 * @param array   $racer         arrived racer row (racer_name, difftext, category id)
 * @param array   $categoryRanks output of dobeh_compute_category_ranks()
 * @param int     $displayOrder  count-down position shown in column 1
 */
function render_dobeh_row($tp, $racer, $categoryRanks, $displayOrder)
{
	$number = (string) $racer['racer_number'];
	$catId  = (int) $racer['racer_category_id'];
	$cat    = dobeh_category_info($catId);

	$catCell = $tp->toHTML($cat['name'], false, 'TITLE');
	if (isset($categoryRanks[$number]))
	{
		$catCell .= ' — ' . (int) $categoryRanks[$number] . '.';
	}

	$color = (string) $cat['color'];

	echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
	echo "<td>" . (int) $displayOrder . "</td>";
	echo "<td>" . $tp->toHTML($number, false, 'TITLE') . "</td>";
	echo "<td>" . $racer['racer_name'] . "</td>";
	echo "<td>" . $catCell . "</td>";
	echo "<td>" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
	echo "</tr>";
}

/**
 * Category name + colour for a category id (static cache for the request).
 *
 * @param int $catId
 * @return array ['name' => string, 'color' => string]
 */
function dobeh_category_info($catId)
{
	static $cats = null;
	if ($cats === null)
	{
		$cats = e107::getDb()->retrieve('race_category', '*', true, true, 'race_category_id');
		if (!is_array($cats)) $cats = array();
	}

	$catId = (int) $catId;

	return array(
		'name'  => isset($cats[$catId]['race_category_name'])  ? $cats[$catId]['race_category_name']  : '',
		'color' => isset($cats[$catId]['race_category_color']) ? $cats[$catId]['race_category_color'] : '',
	);
}

require_once(FOOTERF);
