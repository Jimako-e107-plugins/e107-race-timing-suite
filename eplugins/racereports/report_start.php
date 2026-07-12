<?php
/*
 * e107 website system
 *
 * racereports plugin - START report (start-point standings / štartová listina).
 *
 * A clone of report_finish.php, but the single per-racer time cell shows the
 * START TIME-OF-DAY (wall clock at the 'start' checkpoint), NOT an elapsed/diff.
 * A racer is a STARTER when the racetiming engine returns a 'start' crossing for
 * them (race_clock::timeOfDay(number,'start') !== null) AND racer_active is set
 * (the manual "bib collected" flag, NOT derived from the start time); everyone
 * else (no start crossing, or racer_active=false) falls into the not-started
 * group shown after the starters, with no time. An un-marked racer
 * (racer_active=false) is bucketed here as DNS - a report-level reading of the
 * bib-collection flag, NOT a claim that racer_active means DNS. The start time is the
 * SAME engine read the racers/startlist.php start list uses
 * (race_clock::formatTimeOfDay($clock->timeOfDay(number,'start'), result_decimals))
 * - NO new or changed engine method is introduced; the point is FIXED to 'start'.
 *
 * Modes (legacy query contract, ?r=<race_sef>&c=<race_category_sef>), mirroring
 * report_finish.php:
 *   r=overview           -> ALL tracks on one page, each rendered in turn; define
 *                           e_IFRAME so HEADERF/FOOTERF skip the site header/footer
 *                           chrome (e_IFRAME in e107 = suppress the chrome to save
 *                           space, NOT an actual <iframe>), exactly as finish does.
 *                           (First param renamed from the legacy 'komplet' to
 *                           'overview' - the SECOND-param c=komplet is unchanged.)
 *   r=<race>, c=komplet  -> that race, all categories.
 *   r=<race>, c=<cat>    -> that race + that category.
 *
 * EXPORT (?export=csv|xls): the displayed cells of one SINGLE-RACE scope are built
 * ONCE into a neutral structure ($headers + $rows of already-formatted cells +
 * $textCols) and the HTML table AND the CSV/XLS download both render from THAT same
 * structure - there is no second, divergent row set (mirrors report_stu / finish).
 * The export streams and exits at the TOP of the page, before HEADERF / any output.
 * SCOPE: export is offered for a single race only (r=<race> with c=<cat> or
 * c=komplet -> one table -> one file); r=overview (ALL tracks, many tables) is NOT
 * exported (its admin "all tracks" link carries no export buttons and a manual
 * ?r=overview&export=... falls through to the normal HTML page). See NOTES.md. The
 * exported rows include BOTH the starters AND the not-started rows (with an empty
 * time cell), matching exactly what the screen shows.
 *
 * SORT: starters are sorted ALPHABETICALLY BY NAME (ascending), NOT by time. This
 * is an INTENTIONAL difference from report_point / report_finish (which sort by
 * the time value): mass starts share the same start time-of-day, so a time sort
 * would be meaningless here. See racereports/NOTES.md.
 *
 * Coloring: per-category row background is applied to STARTER rows only, and only
 * when the start_colors pref is on (DEFAULT 1 - start is coloured by default, its
 * own per-report pref like finish_colors/stu_colors). The not-started rows are
 * NEVER coloured (plain <tr>), same rule as online/point/finish.
 *
 * SECURITY: the legacy raw-$_GET-into-SQL pattern and its extract($racer) are NOT
 * carried over. Every SQL value goes through $tp->toDB() in the shared helper;
 * race/category ids are (int)-cast; the bib/start number stays a STRING (never
 * (int)); the start-crossing read is the engine's $clock->timeOfDay($number,
 * 'start') (NO string-concat SQL); output is escaped via $tp->toHTML()/
 * toAttribute(); racer fields are read by explicit array access, never extract().
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

// One read of race_time -> split map (same pattern as racers/startlist.php). build()
// returns $this, so the clock is ready to answer timeOfDay() for every racer below.
$clock = (new race_clock())->build();

// Per-category row coloring toggle (admin SETTINGS page). DEFAULT 1 = ON is enforced
// HERE at the consumer (start is coloured by default - its OWN pref, same as finish).
// Read once and passed into the render so starter rows get the per-category
// background via the shared race_report_color() path.
$useColors = (int) e107::getPlugConfig('racereports')->get('start_colors', 1);

$raceSef = isset($_GET['r']) ? $tp->toDB($_GET['r']) : '';
$export  = isset($_GET['export']) ? (string) $_GET['export'] : '';

// EXPORT BRANCH: stream and exit BEFORE HEADERF / any output. SINGLE-RACE scope
// only (r=<race> with c=<cat> or c=komplet). The cells are exactly what the HTML
// table shows (same start-number string, same start-time string), so the file is
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
			$built = racereports_start_build_scope($report, $clock, $race, $categorySef, $useColors);

			if ($built !== null)
			{
				require_once(e_PLUGIN . 'racereports/includes/race_export.php');

				// Filename: start_<race_sef>[_<cat_sef>]_<Y-m-d>. The sef parts are
				// sanitised to a safe slug so they can never inject into the
				// Content-Disposition header.
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

// PRE-HEADERF: r=overview suppresses the site header/footer chrome. e_IFRAME MUST
// be defined BEFORE HEADERF renders (HEADERF reads it to decide whether to draw the
// chrome). Only r=overview defines it - the single-race modes keep the normal page
// chrome, exactly as report_finish.php does.
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
	// ALL races, each rendered in turn. The chrome is already suppressed (e_IFRAME
	// defined pre-HEADERF above).
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
 * Build the SINGLE-RACE export scope: the neutral structure for one race, either
 * all categories (c=komplet / c='') or one category (c=<cat>). For a category sef
 * that resolves to more than one category row the rows are concatenated in the same
 * top-to-bottom order the HTML page shows them, so the export matches the screen.
 * Returns null when a named category sef resolves to nothing.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param string      $categorySef '' or 'komplet' = all categories
 * @param int         $useColors
 * @return array|null neutral structure (headers/rows/meta/textCols) or null
 */
function racereports_start_build_scope($report, $clock, $race, $categorySef, $useColors)
{
	if ($categorySef === '' || $categorySef === 'komplet')
	{
		return racereports_start_build($report, $clock, $race, null, $useColors);
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
		$built = racereports_start_build($report, $clock, $race, $category, $useColors);
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
 * table and the CSV/XLS export render from: STARTERS first (alphabetical by name,
 * ranked, each showing their start time-of-day), then the not-started group (after
 * the starters, no time).
 *
 * A STARTER is a racer who is active AND has a 'start' crossing in the timing
 * layer (race_clock::timeOfDay(number,'start') !== null). The start-time cell =
 * race_clock::formatTimeOfDay($clock->timeOfDay($number,'start'), $resDec) - the
 * SAME engine read racers/startlist.php uses, at the central result_decimals pref
 * (default 2; like finish's formatElapsed). formatTimeOfDay truncates the sub-second
 * part via the same substr cut as formatElapsed - see NOTES.md. The point is FIXED
 * to 'start' (no point param).
 *
 * Racers with no start crossing, or un-marked (racer_active=false, which this
 * report buckets as DNS, as in finish - a report-level reading of the flag, not its
 * definition), are NOT starters and fall into the not-started group, rendered after
 * the starters with an empty time cell. The racer_active+crossing guard mirrors
 * startlist.php (which only reads a start time when $racer['racer_active']).
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
 * @return array
 */
function racereports_start_build($report, $clock, $race, $category, $useColors)
{
	$raceId     = (int) $race['race_id'];
	$categoryId = ($category !== null) ? (int) $category['race_category_id'] : null;

	// Central RESULT-time precision (DISPLAY-ONLY). start shows time-of-day, so the
	// decimals apply to the sub-second part; formatTimeOfDay TRUNCATES that part via
	// the same substr cut as formatElapsed (the data's .mmm is unchanged). Default 2.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId, $categoryId); // keyed by bib string

	$starters   = array();
	$notStarted = array();

	foreach ($racers as $number => $racer)
	{
		$number = (string) $number;
		$racer['racer_name'] = $report->getRacerName($racer);

		// racer_active (bib marked collected) + a 'start' crossing -> STARTER. The
		// guard mirrors startlist.php (which only reads a start time when
		// racer_active). An un-marked racer is bucketed by this report as DNS - a
		// report-level reading of the bib-collection flag, not its definition. The
		// crossing read is the engine's timeOfDay() (NO string-concat SQL).
		$active = !empty($racer['racer_active']);
		$epoch  = $active ? $clock->timeOfDay($number, 'start') : null;

		if ($epoch !== null)
		{
			// STARTERS: start time-of-day at result_decimals (display-only truncation).
			$racer['starttext'] = race_clock::formatTimeOfDay($epoch, $resDec);
			// Alphabetical sort key from the raw surname+firstname (NOT the rendered
			// HTML name, which may carry markup). Sort is BY NAME on purpose - see
			// the file header.
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

	// Sort starters ALPHABETICALLY BY NAME (ascending) - NOT by time. Mass starts
	// share the same start time-of-day, so a time sort would be meaningless; this is
	// the INTENTIONAL difference from point/finish (see file header / NOTES.md).
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

	// Not-started group: rendered AFTER the starters, NON-ranked (empty rank cell),
	// with an empty time cell, and NEVER coloured (plain <tr>) - same rule as the
	// finish ended block.
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
		// Force the bib (1) and the start-time (5) to TEXT on export so a spreadsheet
		// keeps "0042" and the time string verbatim instead of reinterpreting them.
		'textCols' => array(1, 5),
	);
}

/**
 * Render one START table from the neutral structure built by
 * racereports_start_build(). The cells are the SAME ones the export streams; this
 * function only adds the HTML decoration (per-category starter background). The
 * visible table cells are byte-for-byte the pre-refactor output: starter rows are
 * ranked + optionally coloured, not-started rows keep the empty rank/time cells and
 * are never coloured, and the start table has no on-screen header row.
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
 * Per-category row background colour (race_category.race_category_color), looked up
 * the same way the legacy pages did. Static cache for the request. Identical to the
 * helper in report_finish.php / report_online.php / report_point.php / report_stu.php.
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
