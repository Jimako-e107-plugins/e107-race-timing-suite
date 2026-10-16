<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_finish.php
 *
*/

/*
 * Results - finish (docs/plugins/racereports.md, "Results — finish").
 * Query: ?r=<race_sef>&c=<race_category_sef>, r=overview = all tracks.
 *
 * The finisher time is race_clock::elapsedToPoint(number, 'finish'); the group
 * (OK/DNF/DSQ/DNS) is race_clock::pointStatus() at 'finish'. The page and the
 * ?export=csv|xls download render from ONE row structure
 * (racereports_finish_build()); export covers a single track only (r=overview falls
 * through to the HTML page).
 *
 * r=overview lists finishers slowest first (DESC) on purpose, as the old timetracker
 * page did - do not "fix" it to ASC without checking.
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
if (!defined('LAN_RACEREPORTS_FINISH_TITLE'))    define('LAN_RACEREPORTS_FINISH_TITLE', 'Results');
if (!defined('LAN_RACEREPORTS_COL_RANK'))        define('LAN_RACEREPORTS_COL_RANK', 'Ranking');
if (!defined('LAN_RACEREPORTS_COL_BIB'))         define('LAN_RACEREPORTS_COL_BIB', 'Bibnumber');
if (!defined('LAN_RACEREPORTS_COL_NAME'))        define('LAN_RACEREPORTS_COL_NAME', 'Name');
if (!defined('LAN_RACEREPORTS_COL_NATIONALITY')) define('LAN_RACEREPORTS_COL_NATIONALITY', 'Nationality');
if (!defined('LAN_RACEREPORTS_COL_CATEGORY'))    define('LAN_RACEREPORTS_COL_CATEGORY', 'Category');
if (!defined('LAN_RACEREPORTS_COL_STATUS'))      define('LAN_RACEREPORTS_COL_STATUS', 'Status');
if (!defined('LAN_RACEREPORTS_COL_TIME'))        define('LAN_RACEREPORTS_COL_TIME', 'Time');
// Core e107 LAN (loaded by the front bootstrap); defensive fallback so the admin
// edit-icon title can never fatal on an undefined constant under PHP 8 (mirrors
// report_point.php).
if (!defined('LAN_EDIT'))                        define('LAN_EDIT', 'Edit');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// finish_colors, default 1 (on) applied here at the consumer.
$useColors = (int) e107::getPlugConfig('racereports')->get('finish_colors', 1);

// finish_categ, default 0 (off) applied here; adds the category column to both the
// page and the export (same cells, see racereports_finish_build()).
$showCat = (int) e107::getPlugConfig('racereports')->get('finish_categ', 0);

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
			$built = racereports_finish_build_scope($report, $clock, $race, $categorySef, $useColors, $showCat, true);

			if ($built !== null)
			{
				require_once(e_PLUGIN . 'racereports/includes/race_export.php');

				// The sef parts are reduced to a safe slug so they cannot inject
				// into the Content-Disposition header.
				$slug    = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $raceSef);
				$catSlug = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $categorySef);
				$fname   = 'finish_' . $slug
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
	// ALL races, each rendered in turn, finishers DESC (see the file header).
	echo "<div class='container'><div class='row'>";
	foreach ($report->fetchAllRaces() as $race)
	{
		echo "<div class='col-md-12'>";
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. $tp->toHTML(LAN_RACEREPORTS_FINISH_TITLE, false, 'TITLE') . "</h2>";
		$built = racereports_finish_build($report, $clock, $race, null, 'DESC', $useColors, $showCat);
		racereports_finish_render_table($built, $useColors);
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
		// That race, all categories, finishers ASC.
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. $tp->toHTML(LAN_RACEREPORTS_ALL_CATEGORIES, false, 'TITLE') . "</h2>";
		$built = racereports_finish_build($report, $clock, $race, null, 'ASC', $useColors, $showCat);
		racereports_finish_render_table($built, $useColors);
	}
	else
	{
		// That race + that category, finishers ASC.
		$categories = $report->fetchCategoriesBySef($raceId, $categorySef);
		foreach ($categories as $category)
		{
			echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
				. $tp->toHTML($category['race_category_name'], false, 'TITLE') . "</h2>";
			$built = racereports_finish_build($report, $clock, $race, $category, 'ASC', $useColors, $showCat);
			racereports_finish_render_table($built, $useColors);
		}
	}
}

/**
 * Build the SINGLE-RACE export scope: all categories (c=komplet / c='') or one
 * category sef. A sef matching several category rows is concatenated in screen
 * order. Always ASC. Returns null when a named category sef resolves to nothing.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param string      $categorySef '' or 'komplet' = all categories
 * @param int         $useColors
 * @param int         $showCat   1 = add the category-name column, 0 = no column
 * @param bool        $forExport true = CSV/XLS: plain-text name cell (see racereports_finish_build)
 * @return array|null neutral structure (headers/rows/meta/textCols/...) or null
 */
function racereports_finish_build_scope($report, $clock, $race, $categorySef, $useColors, $showCat, $forExport = false)
{
	if ($categorySef === '' || $categorySef === 'komplet')
	{
		return racereports_finish_build($report, $clock, $race, null, 'ASC', $useColors, $showCat, $forExport);
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
		$built = racereports_finish_build($report, $clock, $race, $category, 'ASC', $useColors, $showCat, $forExport);
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
 * Build one race/category finish table as a NEUTRAL structure that both the HTML
 * table and the CSV/XLS export render from (groups: docs, "Results — finish"). The
 * group is the engine's pointStatus() at 'finish'; NO_START / NO_CROSSING racers
 * are not rendered.
 *
 * Finisher time cell = formatElapsed(elapsedToPoint(number, 'finish'), $resDec);
 * the same full-precision elapsed is the sort key.
 *
 * Returns:
 *   headers    : ordered export column labels (rank, bib, name, nationality,
 *                [category when $showCat], status glyph, time) - used ONLY for the
 *                CSV/XLS header line; the HTML finish table has no on-screen header row.
 *   rows       : array of rows; each row = ordered display cells
 *                [rank, bib, name(html), nationality, [category], glyph, time-or-status]
 *   meta       : parallel per-row HTML-only decoration: ['ended'=>bool, 'color'=>'']
 *   textCols   : indices forced to TEXT on export (bib 1 + time 5, or 6 when $showCat)
 *   showCat    : 1 = the category-name column is present (drives the render), 0 = not
 *   tableStyle : table style attribute string ('' or font-size for DESC)
 *   tdStyle    : per-finisher-cell style attribute string ('' or padding for DESC)
 *
 * When $showCat is on, the category NAME (raw in the cell, escaped at
 * render/export like nationality) is inserted AFTER nationality.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param array|null  $category  null = all categories
 * @param string      $order     'ASC' (default) or 'DESC' (r=overview)
 * @param int         $useColors 1 = colour finisher rows per category, 0 = plain
 * @param int         $showCat   1 = add the category-name column, 0 = no column
 * @param bool        $forExport true = CSV/XLS: the name cell (index 2) is the PLAIN-text
 *                               name from racers_name::build(plain) - no HTML markup,
 *                               flag as a UTF-8 emoji; false (default) = the HTML name
 * @return array
 */
function racereports_finish_build($report, $clock, $race, $category, $order, $useColors, $showCat, $forExport = false)
{
	$raceId     = (int) $race['race_id'];
	$categoryId = ($category !== null) ? (int) $category['race_category_id'] : null;

	// result_decimals (display-only, truncates); the sort key is the raw float.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId, $categoryId); // keyed by bib string

	$finishers = array();
	$dnf       = array();
	$dsq       = array();
	$dns       = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		// Export: plain-text name (escaped by race_export::xls). Page: HTML name.
		$racer['racer_name'] = $forExport
			? racers_name::build($racer, array('plain' => true))
			: $report->getRacerName($racer);

		$active = !empty($racer['racer_active']);
		$status = $clock->pointStatus($number, 'finish', $active);

		if ($status === race_clock::STATUS_OK)
		{
			// FINISHERS ONLY: the ranking-safe elapsed start->finish. > 0 by
			// construction when status is OK; the null/<=0 guard is defensive.
			$elapsed = $clock->elapsedToPoint($number, 'finish');
			if ($elapsed === null || $elapsed <= 0)
			{
				continue;
			}

			$racer['difftime'] = $elapsed;
			$racer['difftext'] = race_format::formatElapsed($elapsed, $resDec); // result_decimals (display-only)
			$finishers[] = $racer;
		}
		elseif ($status === race_clock::STATUS_DNF)
		{
			$dnf[] = $racer;
		}
		elseif ($status === race_clock::STATUS_DSQ)
		{
			$dsq[] = $racer;
		}
		elseif ($status === race_clock::STATUS_DNS)
		{
			$dns[] = $racer;
		}
		// NO_START / NO_CROSSING: not rendered.
	}

	// Sort finishers by elapsed (full-precision float, display-independent).
	if ($order === 'DESC')
	{
		usort($finishers, function ($a, $b) { return $b['difftime'] <=> $a['difftime']; });
	}
	else
	{
		usort($finishers, function ($a, $b) { return $a['difftime'] <=> $b['difftime']; });
	}

	// Header order mirrors the cell order built below: the category label is inserted
	// after nationality ONLY when $showCat is on, so header and cells stay aligned.
	$headers = array(
		LAN_RACEREPORTS_COL_RANK,
		LAN_RACEREPORTS_COL_BIB,
		LAN_RACEREPORTS_COL_NAME,
		LAN_RACEREPORTS_COL_NATIONALITY,
	);
	if ($showCat)
	{
		$headers[] = LAN_RACEREPORTS_COL_CATEGORY;
	}
	$headers[] = LAN_RACEREPORTS_COL_STATUS;
	$headers[] = LAN_RACEREPORTS_COL_TIME;

	$rows = array();
	$meta = array();

	// Rank: ASC counts up from 1; DESC counts down from the finisher count.
	$count = count($finishers);
	$rank  = ($order === 'DESC') ? ($count + 1) : 0;

	foreach ($finishers as $racer)
	{
		$rank = ($order === 'DESC') ? ($rank - 1) : ($rank + 1);

		$number = (string) $racer['racer_number'];

		// Status glyph: checkmark with a 'start' crossing, chequered flag without.
		// A clean finish implies a start crossing, so the flag branch is effectively
		// unreachable (kept from the old timetracker page).
		$glyph = $clock->hasStart($number) ? "\u{2705}" : "\u{1F3C1}";

		// The SINGLE formatted cell set, consumed verbatim by both HTML and export.
		// The bib stays a string; the name is already HTML-safe (or plain text for
		// the export).
		$row = array(
			$rank,                                                                  // 0 rank
			$number,                                                                // 1 bib (TEXT col)
			$racer['racer_name'],                                                   // 2 name (pre-built HTML)
			isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',  // 3 nationality
		);
		if ($showCat)
		{
			$row[] = race_report_category_name($racer);                             // 4 category name (raw)
		}
		$row[] = $glyph;                                                            // status glyph
		$row[] = $racer['difftext'];                                               // time (TEXT col)
		$rows[] = $row;

		$meta[] = array(
			'ended' => false,
			// FINISHER rows: per-category background, ONLY when colours are on.
			'color' => $useColors ? race_report_color($clock, $racer) : '',
			// ADMIN-only edit link to the FINISH crossing (null = none stored).
			// HTML-only, not an export column.
			'rtid'  => $clock->crossingId($number, 'finish'),
		);
	}

	// ENDED groups: DNF, DSQ, DNS - no rank, cross glyph, status text, never
	// coloured (the old timetracker page coloured them via a buggy lookup).
	$endedGroups = array(array($dnf, 'DNF'), array($dsq, 'DSQ'), array($dns, 'DNS'));
	foreach ($endedGroups as $grp)
	{
		foreach ($grp[0] as $racer)
		{
			$row = array(
				'',                                                                     // 0 rank (empty)
				(string) $racer['racer_number'],                                        // 1 bib (TEXT col)
				$racer['racer_name'],                                                   // 2 name (pre-built HTML)
				isset($racer['racer_nacionality']) ? $racer['racer_nacionality'] : '',  // 3 nationality
			);
			if ($showCat)
			{
				$row[] = race_report_category_name($racer);                             // 4 category name (raw)
			}
			$row[] = "\u{274C}";                                                        // status glyph (cross)
			$row[] = $grp[1];                                                           // status text (TEXT col)
			$rows[] = $row;
			// Usually no finish crossing (empty admin cell); a stored one still
			// gets the edit icon.
			$meta[] = array(
				'ended' => true,
				'color' => '',
				'rtid'  => $clock->crossingId((string) $racer['racer_number'], 'finish'),
			);
		}
	}

	// Bib (1) and time/status as TEXT in the XLS; the category column (when on)
	// pushes the time/status cell from index 5 to 6.
	$timeCol = $showCat ? 6 : 5;

	return array(
		'headers'    => $headers,
		'rows'       => $rows,
		'meta'       => $meta,
		'textCols'   => array(1, $timeCol),
		'showCat'    => (int) $showCat,
		'tableStyle' => ($order === 'DESC') ? "style='font-size: 12px;'" : '',
		'tdStyle'    => ($order === 'DESC') ? "style='padding: 0;'" : '',
	);
}

/**
 * Render one finish table from racereports_finish_build(). The cells are the SAME
 * ones the export streams; this only adds the HTML decoration (row colour, the
 * DESC styles, the admin edit cell). No on-screen header row.
 *
 * @param array $built     the racereports_finish_build() structure
 * @param int   $useColors 1 = colour finisher rows per category, 0 = plain
 */
function racereports_finish_render_table($built, $useColors)
{
	$tp = e107::getParser();

	$tdStyle = $built['tdStyle'];

	// The category cell (when present) sits at index 4, pushing the glyph and the
	// time/status cell to 5 and 6.
	$showCat  = !empty($built['showCat']);
	$glyphIdx = $showCat ? 5 : 4;
	$timeIdx  = $showCat ? 6 : 5;

	echo "<table class='table table-striped table-bordered table-hover' " . $built['tableStyle'] . ">";

	foreach ($built['rows'] as $i => $row)
	{
		$ended = !empty($built['meta'][$i]['ended']);

		if ($ended)
		{
			echo "<tr>"; // ENDED rows are never coloured
			echo "<td></td>";
			echo "<td>" . $tp->toHTML((string) $row[1], false, 'TITLE') . "</td>";
			echo "<td>" . $row[2] . "</td>";
			echo "<td>" . $tp->toHTML((string) $row[3], false, 'TITLE') . "</td>";
			if ($showCat)
			{
				echo "<td>" . $tp->toHTML((string) $row[4], false, 'TITLE') . "</td>";
			}
			echo "<td>" . $row[$glyphIdx] . "</td>";
			echo "<td>" . $tp->toHTML((string) $row[$timeIdx], false, 'TITLE') . "</td>";
			finish_admin_edit_cell($tp, isset($built['meta'][$i]['rtid']) ? $built['meta'][$i]['rtid'] : null);
			echo "</tr>";
			continue;
		}

		// FINISHER rows: per-category background, ONLY when colours are on.
		if ($useColors)
		{
			$color = isset($built['meta'][$i]['color']) ? $built['meta'][$i]['color'] : '';
			echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
		}
		else
		{
			echo "<tr>";
		}

		echo "<td " . $tdStyle . ">" . (int) $row[0] . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML((string) $row[1], false, 'TITLE') . "</td>";
		echo "<td " . $tdStyle . ">" . $row[2] . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML((string) $row[3], false, 'TITLE') . "</td>";
		if ($showCat)
		{
			echo "<td " . $tdStyle . ">" . $tp->toHTML((string) $row[4], false, 'TITLE') . "</td>";
		}
		echo "<td " . $tdStyle . ">" . $row[$glyphIdx] . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML((string) $row[$timeIdx], false, 'TITLE') . "</td>";
		finish_admin_edit_cell($tp, isset($built['meta'][$i]['rtid']) ? $built['meta'][$i]['rtid'] : null);
		echo "</tr>";
	}

	echo "</table>";
}

/**
 * ADMIN-only trailing cell: an edit icon to the racetiming race_time admin (new
 * window) for the FINISH crossing, or an empty cell when none is stored. HTML-only,
 * not part of the export. The id is the only value placed in the URL and is
 * (int)-cast there. Same as report_point.php's point_admin_edit_cell().
 *
 * @param e_parse  $tp
 * @param int|null $rtid race_time_id from crossingId(number,'finish'); null = no row
 */
function finish_admin_edit_cell($tp, $rtid)
{
	if (!ADMIN)
	{
		return;
	}

	if ($rtid === null)
	{
		echo "<td></td>";
		return;
	}

	$url  = e_PLUGIN_ABS . "racetiming/admin/admin_config.php?action=edit&id=" . (int) $rtid;
	$attr = $tp->toAttributes(array(
		'href'   => $url,
		'target' => '_blank',
		'title'  => LAN_EDIT,
	));

	echo "<td><a" . $attr . ">" . $tp->toGlyph('fa-edit') . "</a></td>";
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

/**
 * Per-racer category NAME (race_category.race_category_name), keyed by the racer's
 * racer_category_id. Same static-cached race_category lookup pattern as
 * race_report_color() (the row is fetched with '*', so the name is already in cache).
 * Returns the RAW name - escaped at the consumer (toHTML on screen, htmlspecialchars
 * in the XLS), like the nationality cell.
 */
function race_report_category_name($racer)
{
	static $cats = null;
	if ($cats === null)
	{
		$cats = e107::getDb()->retrieve('race_category', '*', true, true, 'race_category_id');
		if (!is_array($cats)) $cats = array();
	}
	$cid = isset($racer['racer_category_id']) ? (int) $racer['racer_category_id'] : 0;

	return isset($cats[$cid]['race_category_name']) ? (string) $cats[$cid]['race_category_name'] : '';
}

require_once(FOOTERF);
