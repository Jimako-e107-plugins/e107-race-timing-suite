<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_start.php
 *
*/

/*
 * Start list (docs/plugins/racereports.md, "Start list").
 * Query: ?r=<race_sef>&c=<race_category_sef>, r=overview = all tracks.
 *
 * A starter is racer_active (the manual "Nastúpil" flag, NOT derived from the start
 * time) AND has a 'start' crossing (race_clock::timeOfDay(number, 'start') !== null),
 * the same engine read racers/startlist.php uses. Everyone else is in the
 * not-started group. Starters are sorted by NAME on purpose (mass starts share one
 * start time). The page and the ?export=csv|xls download render from ONE row
 * structure (racereports_start_build()); export covers a single track only.
 *
 * Every SQL value goes through toDB() in the shared helper; race/category ids are
 * (int)-cast; the bib stays a STRING; output is escaped.
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
if (!defined('LAN_RACEREPORTS_NO_RACE'))         define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_ALL_CATEGORIES'))  define('LAN_RACEREPORTS_ALL_CATEGORIES', 'all categories');
if (!defined('LAN_RACEREPORTS_START_TITLE'))     define('LAN_RACEREPORTS_START_TITLE', 'Start list');
if (!defined('LAN_RACEREPORTS_COL_RANK'))        define('LAN_RACEREPORTS_COL_RANK', 'Ranking');
if (!defined('LAN_RACEREPORTS_COL_BIB'))         define('LAN_RACEREPORTS_COL_BIB', 'Bibnumber');
if (!defined('LAN_RACEREPORTS_COL_NAME'))        define('LAN_RACEREPORTS_COL_NAME', 'Name');
if (!defined('LAN_RACEREPORTS_COL_GENDER'))      define('LAN_RACEREPORTS_COL_GENDER', 'Gender');
if (!defined('LAN_RACEREPORTS_COL_NATIONALITY')) define('LAN_RACEREPORTS_COL_NATIONALITY', 'Nationality');
if (!defined('LAN_RACEREPORTS_COL_TIME'))        define('LAN_RACEREPORTS_COL_TIME', 'Time');

$tp     = e107::getParser();
$report = new race_report();

// One read of race_time -> split map. build() returns $this.
$clock = (new race_clock())->build();

// start_colors, default 1 (on) applied here at the consumer.
$useColors = (int) e107::getPlugConfig('racereports')->get('start_colors', 1);

$raceSef = isset($_GET['r']) ? $tp->toDB($_GET['r']) : '';
$export  = isset($_GET['export']) ? (string) $_GET['export'] : '';

// EXPORT BRANCH: stream and exit BEFORE HEADERF / any output. Single track only;
// $forExport = true gives a PLAIN-text name cell. An overview or unresolved scope
// falls through to the HTML page.
if ($export === 'csv' || $export === 'xls')
{
	if ($raceSef !== '' && $raceSef !== 'overview')
	{
		$race = $report->fetchRaceBySef($raceSef);
		if (!empty($race))
		{
			$categorySef = isset($_GET['c']) ? $tp->toDB($_GET['c']) : '';
			$built = racereports_start_build_scope($report, $clock, $race, $categorySef, $useColors, true);

			if ($built !== null)
			{
				require_once(e_PLUGIN . 'racereports/includes/race_export.php');

				// The sef parts are reduced to a safe slug so they cannot inject
				// into the Content-Disposition header.
				$slug    = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $raceSef);
				$catSlug = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $categorySef);
				$fname   = 'start_' . $slug
					. (($catSlug !== '' && $catSlug !== 'komplet') ? '_' . $catSlug : '')
					. '_' . date('Y-m-d');

				if ($export === 'csv')
				{
					race_export::csv($fname, $built['headers'], $built['rows'], $built['textCols']); // exits
				}
				race_export::xls($fname, $built['headers'], $built['rows'], $built['textCols']); // exits
			}
		}
	}
	// Unsupported/unresolved export scope: fall through to the normal HTML page.
}

// ---- HTML PATH ----------------------------------------------------------------
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

// r=overview: no site header/footer. e_IFRAME MUST be defined BEFORE HEADERF.
if ($raceSef === 'overview')
{
	define('e_IFRAME', true);
}

require_once(HEADERF);

if ($raceSef === '')
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}

if ($raceSef === 'overview')
{
	// ALL races, each rendered in turn.
	echo "<div class='container'><div class='row'>";
	foreach ($report->fetchAllRaces() as $race)
	{
		echo "<div class='col-md-12'>";
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. $tp->toHTML(LAN_RACEREPORTS_START_TITLE, false, 'TITLE') . "</h2>";
		$built = racereports_start_build($report, $clock, $race, null, $useColors);
		racereports_start_render_table($built, $useColors);
		echo "</div>";
	}
	echo "</div></div>";
}
else
{
	$race = $report->fetchRaceBySef($raceSef);
	if (empty($race))
	{
		echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
		require_once(FOOTERF);
		exit;
	}
	$raceId      = (int) $race['race_id'];
	$categorySef = isset($_GET['c']) ? $tp->toDB($_GET['c']) : '';

	if ($categorySef === 'komplet' || $categorySef === '')
	{
		// That race, all categories.
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. $tp->toHTML(LAN_RACEREPORTS_ALL_CATEGORIES, false, 'TITLE') . "</h2>";
		$built = racereports_start_build($report, $clock, $race, null, $useColors);
		racereports_start_render_table($built, $useColors);
	}
	else
	{
		// That race + that category.
		$categories = $report->fetchCategoriesBySef($raceId, $categorySef);
		foreach ($categories as $category)
		{
			echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
				. $tp->toHTML($category['race_category_name'], false, 'TITLE') . "</h2>";
			$built = racereports_start_build($report, $clock, $race, $category, $useColors);
			racereports_start_render_table($built, $useColors);
		}
	}
}

/**
 * Build the SINGLE-RACE export scope: all categories (c=komplet / c='') or one
 * category sef. A sef matching several category rows is concatenated in screen
 * order. Returns null when a named category sef resolves to nothing.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param string      $categorySef '' or 'komplet' = all categories
 * @param int         $useColors
 * @param bool        $forExport true = CSV/XLS: plain-text name cell (see racereports_start_build)
 * @return array|null neutral structure (headers/rows/meta/textCols) or null
 */
function racereports_start_build_scope($report, $clock, $race, $categorySef, $useColors, $forExport = false)
{
	if ($categorySef === '' || $categorySef === 'komplet')
	{
		return racereports_start_build($report, $clock, $race, null, $useColors, $forExport);
	}

	$raceId     = (int) $race['race_id'];
	$categories = $report->fetchCategoriesBySef($raceId, $categorySef);
	if (empty($categories))
	{
		return null;
	}

	$merged = null;
	foreach ($categories as $category)
	{
		$built = racereports_start_build($report, $clock, $race, $category, $useColors, $forExport);
		if ($merged === null)
		{
			$merged = $built;
		}
		else
		{
			$merged['rows'] = array_merge($merged['rows'], $built['rows']);
			$merged['meta'] = array_merge($merged['meta'], $built['meta']);
		}
	}

	return $merged;
}

/**
 * Build one race/category START table as a NEUTRAL structure that both the HTML
 * table and the CSV/XLS export render from: starters (see the file header), then
 * the not-started group with an empty time cell.
 *
 * Start-time cell = race_clock::formatTimeOfDay(timeOfDay(number, 'start'),
 * $resDec); formatTimeOfDay truncates the sub-second part like formatElapsed.
 *
 * Returns:
 *   headers  : ordered export column labels (rank, bib, name, gender, nationality,
 *              start time) - used ONLY for the CSV/XLS header line; the HTML start
 *              table has no on-screen header row.
 *   rows     : array of rows; each row = ordered display cells
 *              [rank, bib, name(html), gender, nationality, start-time]
 *   meta     : parallel per-row HTML-only decoration: ['ended'=>bool, 'color'=>'']
 *              ('ended' true = a not-started row)
 *   textCols : indices forced to TEXT on export (bib 1 + start time 5)
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param array|null  $category  null = all categories
 * @param int         $useColors 1 = colour starter rows per category, 0 = plain
 * @param bool        $forExport true = CSV/XLS: the name cell (index 2) is the PLAIN-text
 *                               name from racers_name::build(plain) - no HTML markup,
 *                               flag as a UTF-8 emoji; false (default) = the HTML name
 * @return array
 */
function racereports_start_build($report, $clock, $race, $category, $useColors, $forExport = false)
{
	$raceId     = (int) $race['race_id'];
	$categoryId = ($category !== null) ? (int) $category['race_category_id'] : null;

	// result_decimals (display-only), applied to the sub-second part of the time of day.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId, $categoryId); // keyed by bib string

	$starters   = array();
	$notStarted = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		// Export: plain-text name (escaped by race_export::xls). Page: HTML name.
		$racer['racer_name'] = $forExport
			? racers_name::build($racer, array('plain' => true))
			: $report->getRacerName($racer);

		// racer_active + a 'start' crossing -> STARTER (racer_active is a manual
		// flag, see racereports-NOTES.md, "Terminology: racer_active vs DNS").
		$active = !empty($racer['racer_active']);
		$epoch  = $active ? $clock->timeOfDay($number, 'start') : null;

		if ($epoch !== null)
		{
			// STARTERS: start time-of-day at result_decimals (display-only truncation).
			$racer['starttext'] = race_clock::formatTimeOfDay($epoch, $resDec);
			// Sort key from the raw surname + firstname (the rendered name may
			// carry markup).
			$surname            = isset($racer['racer_surname']) ? $racer['racer_surname'] : '';
			$firstname          = isset($racer['racer_firstname']) ? $racer['racer_firstname'] : '';
			$racer['_sortname'] = strtolower($surname . ' ' . $firstname);
			$starters[] = $racer;
		}
		else
		{
			$notStarted[] = $racer;
		}
	}

	// Starters BY NAME, not by time (see the file header).
	usort($starters, function ($a, $b) { return strcmp($a['_sortname'], $b['_sortname']); });

	$headers = array(
		LAN_RACEREPORTS_COL_RANK,
		LAN_RACEREPORTS_COL_BIB,
		LAN_RACEREPORTS_COL_NAME,
		LAN_RACEREPORTS_COL_GENDER,
		LAN_RACEREPORTS_COL_NATIONALITY,
		LAN_RACEREPORTS_COL_TIME,
	);

	$rows = array();
	$meta = array();

	// Rank counts up from 1 over the alphabetical starter list.
	$rank = 0;
	foreach ($starters as $racer)
	{
		$rank++;

		$number = (string) $racer['racer_number'];

		// The SINGLE formatted cell set, consumed verbatim by both HTML and export.
		$rows[] = array(
			$rank,                                                                  // 0 rank
			$number,                                                                // 1 bib (TEXT col)
			$racer['racer_name'],                                                   // 2 name (pre-built HTML)
			isset($racer['racer_gender']) ? $racer['racer_gender'] : '',            // 3 gender
			isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',  // 4 nationality
			$racer['starttext'],                                                    // 5 start time (TEXT col)
		);

		$meta[] = array(
			'ended' => false,
			// STARTER rows: per-category background, ONLY when colours are on.
			'color' => $useColors ? race_report_color($clock, $racer) : '',
		);
	}

	// Not-started group: no rank, no time, never coloured.
	foreach ($notStarted as $racer)
	{
		$rows[] = array(
			'',                                                                     // 0 rank (empty)
			(string) $racer['racer_number'],                                        // 1 bib (TEXT col)
			$racer['racer_name'],                                                   // 2 name (pre-built HTML)
			isset($racer['racer_gender']) ? $racer['racer_gender'] : '',            // 3 gender
			isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',  // 4 nationality
			'',                                                                     // 5 start time (empty)
		);
		$meta[] = array('ended' => true, 'color' => '');
	}

	return array(
		'headers'  => $headers,
		'rows'     => $rows,
		'meta'     => $meta,
		// Bib (1) and start time (5) as TEXT in the XLS.
		'textCols' => array(1, 5),
	);
}

/**
 * Render one START table from racereports_start_build(). The cells are the SAME
 * ones the export streams; this only adds the row colour. No on-screen header row.
 *
 * @param array $built     the racereports_start_build() structure
 * @param int   $useColors 1 = colour starter rows per category, 0 = plain
 */
function racereports_start_render_table($built, $useColors)
{
	$tp = e107::getParser();

	echo "<table class='table table-striped table-bordered table-hover'>";

	foreach ($built['rows'] as $i => $row)
	{
		$ended = !empty($built['meta'][$i]['ended']);

		if ($ended)
		{
			echo "<tr>"; // not-started rows are NEVER coloured
			echo "<td></td>";
			echo "<td>" . $tp->toHTML((string) $row[1], false, 'TITLE') . "</td>";
			echo "<td>" . $row[2] . "</td>";
			echo "<td>" . $tp->toHTML((string) $row[3], false, 'TITLE') . "</td>";
			echo "<td>" . $tp->toHTML((string) $row[4], false, 'TITLE') . "</td>";
			echo "<td></td>"; // no start time
			echo "</tr>";
			continue;
		}

		// STARTER rows: per-category background, ONLY when colours are on.
		if ($useColors)
		{
			$color = isset($built['meta'][$i]['color']) ? $built['meta'][$i]['color'] : '';
			echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
		}
		else
		{
			echo "<tr>";
		}

		echo "<td>" . (int) $row[0] . "</td>";
		echo "<td>" . $tp->toHTML((string) $row[1], false, 'TITLE') . "</td>";
		echo "<td>" . $row[2] . "</td>";
		echo "<td>" . $tp->toHTML((string) $row[3], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML((string) $row[4], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML((string) $row[5], false, 'TITLE') . "</td>";
		echo "</tr>";
	}

	echo "</table>";
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
