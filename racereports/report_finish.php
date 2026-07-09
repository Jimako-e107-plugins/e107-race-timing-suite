<?php
/*
 * e107 website system
 *
 * racereports plugin - FINISH report (clean equivalent of
 * timetracker/timetracker_finish.php).
 *
 * Post-race RESULTS LIST: finishers first, then the DNF, DSQ and DNS groups, in
 * that order. The single final time per finisher = elapsed start->finish, read
 * from the racetiming engine (race_clock::elapsedToPoint(number,'finish')); the
 * per-racer status (OK/DNF/DSQ/DNS) is the engine's pointStatus() at the finish
 * checkpoint; identity (name/bib/nationality) is the racer table. NO new engine
 * op is introduced - elapsedToPoint / pointStatus already exist.
 *
 * Modes (legacy query contract, ?r=<race_sef>&c=<race_category_sef>):
 *   r=overview           -> ALL tracks on one page, each rendered in turn; finishers
 *                           DESC. Defines e_IFRAME so HEADERF/FOOTERF skip the site
 *                           header/footer chrome (e_IFRAME in e107 = suppress the
 *                           chrome to save space, NOT an actual <iframe> - this is
 *                           exactly what the legacy page did for the all-tracks view).
 *                           (First param renamed from the legacy 'komplet' to
 *                           'overview' - the SECOND-param c=komplet is unchanged.)
 *   r=<race>, c=komplet  -> that race, all categories; finishers ASC.
 *   r=<race>, c=<cat>    -> that race + that category; finishers ASC.
 *
 * Finishers are sorted by elapsed start->finish: ASC normally, DESC for r=overview.
 * // NOTE: the DESC order for r=overview is intentional (historical reason) - do
 * // NOT "fix" it to ASC without checking. The rank counts down from the finisher
 * // count under DESC and up from 1 under ASC (mirrors the legacy $racer_order).
 *
 * EXPORT (?export=csv|xls): the displayed cells of one SINGLE-RACE scope are built
 * ONCE into a neutral structure ($headers + $rows of already-formatted cells +
 * $textCols) and the HTML table AND the CSV/XLS download both render from THAT same
 * structure - there is no second, divergent row set (mirrors report_stu). The export
 * streams and exits at the TOP of the page, before HEADERF / any output, exactly as
 * stu does. SCOPE: export is offered for a single race only (r=<race> with c=<cat>
 * or c=komplet -> one table -> one file); r=overview (ALL tracks, many tables) is NOT
 * exported (its admin "all tracks" link carries no export buttons and a manual
 * ?r=overview&export=... falls through to the normal HTML page). See NOTES.md.
 *
 * Coloring: per-category row background is applied to FINISHER rows only, and only
 * when the finish_colors pref is on (DEFAULT 1 - finish is coloured by default,
 * unlike SUT). ENDED rows (DNF/DSQ/DNS) are NEVER coloured (plain <tr>) - this is
 * an INTENTIONAL difference from the legacy page, which coloured the ended rows too
 * via a buggy `$cats['race_category_color']` lookup (a miss that resolved to no/an
 * empty colour anyway). See racereports/NOTES.md. The time-cell parity is
 * unaffected.
 *
 * SECURITY: the legacy raw-$_GET-into-SQL pattern (WHERE race_sef='{$race_sef}')
 * and its extract($racer) are NOT carried over. Every SQL value goes through
 * $tp->toDB() in the shared helper; race/category ids are (int)-cast; the bib/start
 * number stays a STRING (never (int)); the start-crossing probe is the engine's
 * parametrised $clock->hasStart($number) (NO string-concat SQL); output is escaped
 * via $tp->toHTML()/toAttribute(); racer fields are read by explicit array access,
 * never extract().
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

// Per-category row coloring toggle (admin SETTINGS page). DEFAULT 1 = ON is
// enforced HERE at the consumer (finish is coloured by default - unlike SUT, which
// defaults OFF). Read once and passed into the render so finisher rows get the
// per-category background via the shared race_report_color() path.
$useColors = (int) e107::getPlugConfig('racereports')->get('finish_colors', 1);

// Category-name column toggle (admin SETTINGS page). DEFAULT 0 = OFF is enforced
// HERE at the consumer (get('finish_categ', 0)): when off the finish list renders
// exactly as today (no category column). When on, ONE column with the racer's
// category NAME is added to BOTH the on-screen table AND the CSV/XLS export - built
// once into the same cells (see racereports_finish_build). This closes the last gap
// vs the legacy timetracker_export (NOTES.md).
$showCat = (int) e107::getPlugConfig('racereports')->get('finish_categ', 0);

$raceSef = isset($_GET['r']) ? $tp->toDB($_GET['r']) : '';
$export  = isset($_GET['export']) ? (string) $_GET['export'] : '';

// EXPORT BRANCH: stream and exit BEFORE HEADERF / any output. SINGLE-RACE scope
// only (r=<race> with c=<cat> or c=komplet). The cells are exactly what the HTML
// table shows (same start-number string, same time string), so the file is
// byte-identical to the page. The start number (leading zeros) and the time string
// are marked as text columns so spreadsheets do not mangle them. An overview (all
// tracks) or unresolved scope is NOT exported - it falls through to the HTML path.
if ($export === 'csv' || $export === 'xls')
{
	if ($raceSef !== '' && $raceSef !== 'overview')
	{
		$race = $report->fetchRaceBySef($raceSef);
		if (!empty($race))
		{
			$categorySef = isset($_GET['c']) ? $tp->toDB($_GET['c']) : '';
			$built = racereports_finish_build_scope($report, $clock, $race, $categorySef, $useColors, $showCat);

			if ($built !== null)
			{
				require_once(e_PLUGIN . 'racereports/includes/race_export.php');

				// Filename: finish_<race_sef>[_<cat_sef>]_<Y-m-d>. The sef parts are
				// sanitised to a safe slug so they can never inject into the
				// Content-Disposition header.
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

// PRE-HEADERF: r=overview suppresses the site header/footer chrome. e_IFRAME MUST
// be defined BEFORE HEADERF renders (HEADERF reads it to decide whether to draw the
// chrome). Only r=overview defines it - the single-race modes keep the normal page
// chrome, exactly as the legacy page did.
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
	// ALL races, each rendered in turn, finishers DESC. The chrome is already
	// suppressed (e_IFRAME defined pre-HEADERF above).
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
 * Build the SINGLE-RACE export scope: the neutral structure for one race, either
 * all categories (c=komplet / c='') or one category (c=<cat>). For a category sef
 * that resolves to more than one category row the rows are concatenated in the same
 * top-to-bottom order the HTML page shows them, so the export matches the screen.
 * Always built ASC (single-race scope is ASC on screen). Returns null when a named
 * category sef resolves to nothing.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param string      $categorySef '' or 'komplet' = all categories
 * @param int         $useColors
 * @param int         $showCat   1 = add the category-name column, 0 = no column
 * @return array|null neutral structure (headers/rows/meta/textCols/...) or null
 */
function racereports_finish_build_scope($report, $clock, $race, $categorySef, $useColors, $showCat)
{
	if ($categorySef === '' || $categorySef === 'komplet')
	{
		return racereports_finish_build($report, $clock, $race, null, 'ASC', $useColors, $showCat);
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
		$built = racereports_finish_build($report, $clock, $race, $category, 'ASC', $useColors, $showCat);
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
 * table and the CSV/XLS export render from: FINISHERS first (ranked, elapsed
 * ASC/DESC), then the DNF, DSQ and DNS groups, in that order. The four groups
 * reproduce the legacy display_finish_list() buckets, but the per-racer bucket is
 * decided by the engine's pointStatus() at the 'finish' checkpoint (clean status)
 * instead of the legacy racer-table-active-flag + DNF/DSQ set membership reads.
 *
 * Finisher time cell = race_format::formatElapsed(elapsedToPoint(number,'finish'),
 * $resDec), $resDec = the central result_decimals pref (default 2; see NOTES.md).
 * elapsedToPoint returns the ranking-safe full-precision elapsed (only when status
 * is OK), used as BOTH the sort key and (truncated to $resDec digits) the display
 * string. formatElapsed truncates via substr(HH:MM:SS.mmm, ...) - the SAME cut the
 * legacy finish page applied at tenths (timetracker_class.php:270 substr(
 * secondsToTime, 0, 10)); parity/parity_finish.php asserts that engine cut at 1
 * decimal is byte-equal to legacy. Display precision is now the pref; the data and
 * the sort key stay full-precision.
 *
 * Racers that are neither a finisher nor DNF/DSQ/DNS (engine NO_START / NO_CROSSING
 * - active, started-or-not, but no usable finish and not flagged) are NOT rendered,
 * matching the legacy page (its `racers3` bucket was collected but never echoed).
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
 * When $showCat is on, ONE column carrying the racer's category NAME
 * (race_category.race_category_name, raw in the cell - escaped at render/export, like
 * nationality) is inserted AFTER nationality, in BOTH the screen rows and the export
 * rows (same cells), so the screen table and the CSV/XLS file stay byte-aligned.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param array|null  $category  null = all categories
 * @param string      $order     'ASC' (default) or 'DESC' (r=overview)
 * @param int         $useColors 1 = colour finisher rows per category, 0 = plain
 * @param int         $showCat   1 = add the category-name column, 0 = no column
 * @return array
 */
function racereports_finish_build($report, $clock, $race, $category, $order, $useColors, $showCat)
{
	$raceId     = (int) $race['race_id'];
	$categoryId = ($category !== null) ? (int) $category['race_category_id'] : null;

	// Central RESULT-time precision (DISPLAY-ONLY, truncates). Default 2 enforced
	// here at the consumer; data stays at 3 (ms) and the sort key is the raw float.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId, $categoryId); // keyed by bib string

	$finishers = array();
	$dnf       = array();
	$dsq       = array();
	$dns       = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		$racer['racer_name'] = $report->getRacerName($racer);

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
		// NO_START / NO_CROSSING: not a finisher and not flagged - not rendered
		// (legacy `racers3`, collected but never echoed).
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

	// Rank: ASC counts up from 1; DESC counts down from the finisher count (mirror
	// the legacy $racer_order logic).
	$count = count($finishers);
	$rank  = ($order === 'DESC') ? ($count + 1) : 0;

	foreach ($finishers as $racer)
	{
		$rank = ($order === 'DESC') ? ($rank - 1) : ($rank + 1);

		$number = (string) $racer['racer_number'];

		// Status glyph: a finisher WITH a 'start' crossing -> checkmark, WITHOUT ->
		// flag. The start-crossing check is the engine's parametrised hasStart()
		// (NO legacy string-concat `LIKE '{$racer_number}'` SQL). A clean finish
		// implies a start crossing, so the flag branch is effectively unreached for
		// finishers - reproduced from legacy for fidelity.
		$glyph = $clock->hasStart($number) ? "\u{2705}" : "\u{1F3C1}";

		// The SINGLE formatted cell set, consumed verbatim by both HTML and export.
		// The bib stays a string; the name is the already-built getRacerName HTML
		// (echoed raw by the HTML render today, so it is the cell verbatim); the time
		// is the already-formatted difftext string. The category NAME cell (raw, like
		// nationality) is inserted after nationality ONLY when $showCat is on.
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
			// ADMIN-only edit link: the racer's FINISH crossing row (the one this
			// report displays). null when no stored crossing -> empty admin cell.
			// HTML-only decoration (mirrors report_point) - NOT an export column.
			'rtid'  => $clock->crossingId($number, 'finish'),
		);
	}

	// ENDED groups, in order: DNF, then DSQ, then DNS. Each AFTER the finishers,
	// NON-ranked (empty rank cell), with the cross glyph and the literal status text -
	// and NEVER coloured (intentional fix vs legacy).
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
			// ENDED rows usually have NO finish crossing (crossingId null -> empty admin
			// cell), but follow report_point's "icon only when crossingId non-null" rule
			// so a stored finish crossing still gets the edit icon and the column aligns.
			$meta[] = array(
				'ended' => true,
				'color' => '',
				'rtid'  => $clock->crossingId((string) $racer['racer_number'], 'finish'),
			);
		}
	}

	// Force the bib (1) and the time/status to TEXT on export so a spreadsheet keeps
	// "0042" and "12:17:55.4" verbatim instead of reinterpreting them. The category
	// column (when on) pushes the time/status cell from index 5 to 6.
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
 * Render one finish table from the neutral structure built by
 * racereports_finish_build(). The cells are the SAME ones the export streams; this
 * function only adds the HTML decoration (per-category finisher background, the
 * DESC table/cell styles). The visible table cells are byte-for-byte the
 * pre-refactor output: finisher rows keep the `<td $tdStyle>` form, ended rows the
 * plain `<td>` form, and the finish table has no on-screen header row.
 *
 * @param array $built     the racereports_finish_build() structure
 * @param int   $useColors 1 = colour finisher rows per category, 0 = plain
 */
function racereports_finish_render_table($built, $useColors)
{
	$tp = e107::getParser();

	$tdStyle = $built['tdStyle'];

	// Column layout: the category cell (when present) sits at index 4, pushing the
	// status glyph and the time/status cell to 5 and 6. The category cell is plain
	// text (raw in the cell), escaped here exactly like nationality.
	$showCat  = !empty($built['showCat']);
	$glyphIdx = $showCat ? 5 : 4;
	$timeIdx  = $showCat ? 6 : 5;

	echo "<table class='table table-striped table-bordered table-hover' " . $built['tableStyle'] . ">";

	foreach ($built['rows'] as $i => $row)
	{
		$ended = !empty($built['meta'][$i]['ended']);

		if ($ended)
		{
			echo "<tr>"; // ENDED rows are NEVER coloured (intentional fix vs legacy)
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
 * ADMIN-only trailing cell: an edit icon linking the racetiming race_time admin
 * (NEW window / target=_blank) on this racer's FINISH crossing row, when one is
 * stored. Rows with no stored finish crossing (crossingId null) get an empty cell -
 * nothing to edit. The whole column is gated on ADMIN, so non-admins never see it
 * (header + cells absent). The admin cell is HTML-ONLY decoration and is NOT part of
 * the neutral export structure, so the CSV/XLS file is unchanged. Mirrors
 * report_point.php's point_admin_edit_cell().
 *
 * The id is the only value placed in the URL and is (int)-cast there.
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
 * Per-category row background colour (race_category.race_category_color), looked up
 * the same way the legacy pages did. Static cache for the request. Identical to the
 * helper in report_online.php / report_point.php / report_stu.php.
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
 * Returns the RAW name string - escaping happens at the consumer (toHTML on screen,
 * htmlspecialchars on XLS export), exactly like the nationality cell. Used only when
 * the finish_categ pref adds the category column.
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
