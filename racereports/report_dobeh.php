<?php
/*
 * e107 website system
 *
 * racereports plugin - DOBEH (checkpoint arrivals board) report, the clean
 * equivalent of timetracker/timetracker_dobeh.php.
 *
 * A LIVE board for checkpoint staff / a big-screen display: the racers who have a
 * valid crossing at ONE checkpoint, sorted by ARRIVAL (latest crossing first), the
 * rank column counting DOWN (newest row = current total, first arrival = 1). Each
 * row's category cell shows "<category> — <Nth>", where Nth is the racer's rank
 * WITHIN their category by ELAPSED time at this checkpoint (NOT arrival order), so
 * it is stable regardless of the display sort. This is distinct from the finish /
 * online reports (which sort by elapsed and count UP).
 *
 * All time math comes from the racetiming engine (race_clock + race_format); this
 * page does ONLY presentation: fetch racers, read the checkpoint, keep the ones
 * with a valid crossing here, sort by arrival DESC, rank within category by elapsed
 * and render. NO export, NO DataTables - it is a live, auto-refreshing board.
 *
 *   ?r=<race_sef>&p=<race_point_sef>
 *   r=komplet -> every race, each of its checkpoints (skipping start/finish).
 *   p=komplet -> every checkpoint of the race (skipping start/finish).
 *
 *   Arrival sort key  : race_clock::timeOfDay($number,$point) - the ABSOLUTE
 *                       wall-clock crossing epoch (legacy sorted on the same
 *                       race_savedtime); no engine change was needed (see NOTES.md).
 *   Time cell         : elapsed start->point, formatted like the / point report
 *                       (race_report::diffOnPoint at the central result_decimals
 *                       pref, default 2; see NOTES.md).
 *
 * SECURITY: r/p go through $tp->toDB(); race id is (int)-cast; the bib/start number
 * stays a STRING (never cast - leading zeros are data); every output cell is
 * escaped (text via toHTML, the category colour via toAttribute). No extract().
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

// Auto-refresh resolution (live board): the ?refresh= URL param ALWAYS wins over
// the pref, even ?refresh=0 (which means "disable on THIS window", NOT "fall back to
// the pref") - hence the isset() distinction, not ?:. When no ?refresh is present we
// fall back to the plugin's OWN dobeh_refresh_interval pref (a racereports pref - NOT
// timetracker's/racerfid's refresh_interval the legacy page read). Default 0 = no
// auto-refresh. The dobeh board gets its OWN refresh pref (separate from the online
// report's online_refresh_interval) because the two are independent live surfaces an
// operator may pace differently - matching the per-report pref convention (see NOTES).
// e107::meta() MUST run BEFORE the header (HEADERF) renders, or the
// <meta http-equiv="refresh"> never lands in <head> - keep it in this pre-HEADERF spot.
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

// Engine (racetiming) + shared report helper. Loaded by path so no <dependency>
// on racetiming is required (the skeleton deps are left unchanged).
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
	// Every race, each of its checkpoints (skipping start/finish) - one live arrival
	// board per checkpoint. e_IFRAME is already defined above, so this all-view
	// renders without the site chrome, the same as a single board.
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
 * Render one checkpoint ARRIVALS board for a race+point.
 *
 * Keep the racers with a valid crossing at the point (the legacy "arrived" set:
 * isRanked - a usable, non-ended crossing here), rank each WITHIN their category by
 * ELAPSED time ascending (compute_category_ranks parity), then DISPLAY-sort by
 * arrival (absolute crossing time) DESCENDING and render with the rank column
 * counting DOWN (newest row = total, oldest = 1). Reproduces legacy
 * timetracker_dobeh.php::display_point_list().
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

	// Central RESULT-time precision (DISPLAY-ONLY, truncates). Default 2 enforced
	// here at the consumer; passed explicitly to diffOnPoint (helper default left 1).
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers  = $report->fetchRacers($raceId); // keyed by bib string
	$arrived = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		$racer['racer_name'] = $report->getRacerName($racer);

		$diff = $report->diffOnPoint($clock, $number, $pointId, $resDec);

		// "Arrived": a valid, non-ended crossing here (legacy test
		// time>0 && text!='' && ended=='' -> race_report::isRanked).
		if (!$report->isRanked($diff))
		{
			continue;
		}

		$racer['difftime'] = $diff['time'];
		// Legacy dobeh time cell = text . " " . ended (trailing space when no flag;
		// arrived rows carry no ended flag) - reproduced for parity.
		$racer['difftext'] = $diff['text'] . " " . $diff['ended'];
		// Arrival sort key: the ABSOLUTE wall-clock crossing epoch (legacy sorted on
		// race_savedtime). timeOfDay() returns exactly that; a valid crossing here
		// guarantees it is non-null.
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
 * Map racer_number => rank within its category, ranked by ELAPSED time at the point
 * ascending (fastest = 1st). Reproduces legacy compute_category_ranks(): group the
 * arrived racers by category id, sort each group by difftime ascending, number 1..N.
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
 * Render one arrival row: rank-down # | bib | name | "Category — Nth" | time.
 *
 * The category cell shows the category name plus the racer's in-category rank (from
 * dobeh_compute_category_ranks) as " — N." (legacy em-dash + trailing dot). The row
 * is coloured by the category colour, exactly as the legacy dobeh page (every arrived
 * row carries a category background). The bib stays a STRING; every cell is escaped.
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
		// Legacy: htmlspecialchars(name) . ' — ' . rank . '.'
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
 * Category name + colour for a category id, looked up the same way the legacy page
 * did (timetracker::$cats[id] -> race_category_name / race_category_color). Read once
 * (static cache) to keep the engine free of presentation concerns.
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
