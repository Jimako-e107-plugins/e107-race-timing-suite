<?php
/*
 * e107 website system
 *
 * racereports plugin - ONLINE report (clean equivalent of
 * timetracker_online.php).
 *
 * Online overall standings. Racers are bucketed by the FURTHEST checkpoint they
 * have reached (checkpoints walked finish-first = race_point_order DESC), each
 * racer consumed at that furthest point, then sorted by elapsed start->that
 * checkpoint ASCENDING within the bucket. Rank is a cumulative counter across
 * buckets, except the finish bucket which shows its local 1..N.
 *
 * All time math comes from the racetiming engine; this page does ONLY the
 * fetch / bucket / rank / render. Output is escaped.
 *
 *   Columns: rank, bib (string), name+club, nationality, time, checkpoint-code.
 *   r=komplet -> all races, DESC order.  c=komplet -> all categories, ASC.
 *   Auto-refresh: ?refresh=<int> in the URL wins (even 0 = off); otherwise the
 *   plugin's own online_refresh_interval pref (default 0 = no auto-refresh).
 *
 * SCOPE: this is a pure live READ. The legacy online page could also fire the
 * result-freeze / terminovka send when genresults=1; that already works and is
 * OUT OF SCOPE - it is NOT replicated or moved here.
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

// Auto-refresh resolution: the ?refresh= URL param ALWAYS wins over the pref,
// even ?refresh=0 (which means "disable on THIS window", NOT "fall back to the
// pref") - hence the isset() distinction, not ?:. When no ?refresh is present we
// fall back to the plugin's OWN online_refresh_interval pref (racereports' own
// pref - NOT timetracker's refresh_interval). Default 0 = no auto-refresh, so an
// unset pref + no URL param reproduces today's behaviour exactly.
//   ?refresh=10 (any pref)  -> 10s   (pref ignored)
//   ?refresh=0  (any pref)  -> none  (pref ignored)
//   no ?refresh, pref=30    -> 30s
//   no ?refresh, pref=0/unset -> none
// e107::meta() MUST run BEFORE the header (require_once HEADERF) renders, or the
// <meta http-equiv="refresh"> never lands in <head> - keep it in this pre-HEADERF
// spot. (This is exactly why a later e107::meta() call would silently no-op.)
$refresh = isset($_GET['refresh'])
	? (int) e107::getParser()->filter($_GET['refresh'], 'int')                    // URL present -> wins, even 0
	: (int) e107::getPlugConfig('racereports')->get('online_refresh_interval', 0); // else pref
if ($refresh > 0)
{
	e107::meta(null, $refresh, array('http-equiv' => 'refresh'));
}

require_once(HEADERF);
e107::lan('racereports', '', true); // front strings: languages/English/English_front.php

// Defensive fallbacks so a missing/odd LAN load path can never fatal a front page
// on an undefined constant (PHP 8). The loaded front file is the source of truth.
if (!defined('LAN_RACEREPORTS_NO_RACE'))        define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_LIVE_STATE'))     define('LAN_RACEREPORTS_LIVE_STATE', 'current standings');
if (!defined('LAN_RACEREPORTS_ALL_CATEGORIES')) define('LAN_RACEREPORTS_ALL_CATEGORIES', 'all categories');
if (!defined('LAN_RACEREPORTS_HELP_REFRESH_TITLE')) define('LAN_RACEREPORTS_HELP_REFRESH_TITLE', 'Online results — automatic refresh');
if (!defined('LAN_RACEREPORTS_HELP_REFRESH_BODY'))  define('LAN_RACEREPORTS_HELP_REFRESH_BODY', '');

// Page help: a short collapsible note at the top explaining the auto-refresh
// behaviour (default pref + ?refresh override). No JS needed - native <details>.
// The BODY is a trusted, static LAN string (HTML markup), so it is echoed RAW;
// the TITLE goes through toHTML(). Skipped if the body LAN failed to load.
// ADMIN-ONLY: this is operator help, not visitor content - regular visitors see
// the live board with no help text. The refresh BEHAVIOUR above is unaffected.
if (ADMIN && LAN_RACEREPORTS_HELP_REFRESH_BODY !== '')
{
	echo "<div class='container'><details class='racereports-help' style='margin:8px 0;'>"
		. "<summary>" . e107::getParser()->toHTML(LAN_RACEREPORTS_HELP_REFRESH_TITLE, false, 'TITLE') . "</summary>"
		. "<div class='racereports-help-body' style='margin-top:6px;'>" . LAN_RACEREPORTS_HELP_REFRESH_BODY . "</div>"
		. "</details></div>";
}

require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build();

$raceSef = isset($_GET['r']) ? $tp->toDB($_GET['r']) : '';

if ($raceSef === '')
{
	echo "<div class='container'><p>" . LAN_RACEREPORTS_NO_RACE . "</p></div>";
	require_once(FOOTERF);
	exit;
}

if ($raceSef === 'komplet')
{
	// All races, all categories, DESC order.
	echo "<div class='container'><div class='row'>";
	foreach ($report->fetchAllRaces() as $race)
	{
		echo "<div class='col-md-12'>";
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. LAN_RACEREPORTS_LIVE_STATE . "</h2>";
		racereports_finish_list($report, $clock, $race, null, 'DESC');
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
	$raceId        = (int) $race['race_id'];
	$categorySef   = isset($_GET['c']) ? $tp->toDB($_GET['c']) : '';

	if ($categorySef === 'komplet' || $categorySef === '')
	{
		// All categories of the race, ASC.
		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
			. LAN_RACEREPORTS_ALL_CATEGORIES . "</h2>";
		racereports_finish_list($report, $clock, $race, null, 'ASC');
	}
	else
	{
		$categories = $report->fetchCategoriesBySef($raceId, $categorySef);
		foreach ($categories as $category)
		{
			echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE') . " - "
				. $tp->toHTML($category['race_category_name'], false, 'TITLE') . "</h2>";
			racereports_finish_list($report, $clock, $race, $category, 'ASC');
		}
	}
}

/**
 * Render one race/category standings table: walk checkpoints finish-first,
 * bucket each racer at their furthest reached checkpoint, rank within the bucket
 * and emit rows. Cumulative rank carries across buckets (the finish bucket shows
 * its local rank).
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param array       $race
 * @param array|null  $category null = all categories
 * @param string      $order    'ASC' (default) or 'DESC' (r=komplet)
 */
function racereports_finish_list($report, $clock, $race, $category = null, $order = 'ASC')
{
	$raceId     = (int) $race['race_id'];
	$categoryId = ($category !== null) ? (int) $category['race_category_id'] : null;

	$racers = $report->fetchRacers($raceId, $categoryId); // keyed by bib string

	$checkpoints = $report->fetchCheckpoints($raceId);    // race_point_order DESC

	// Working pool of racer numbers that have any crossing row, consumed as each
	// is bucketed at its furthest point (mirrors legacy array_diff_key on $points).
	$pool = array();
	foreach ($clock->racerNumbers() as $num)
	{
		$pool[$num] = true;
	}

	$tableStyle = ($order === 'DESC') ? "style='font-size: 12px;'" : '';
	echo "<table class='table table-striped table-bordered table-hover' " . $tableStyle . ">";

	$globalOrder = 0;
	foreach ($checkpoints as $checkpoint)
	{
		$code = (string) $checkpoint['race_point_code'];

		$bucket = array();
		foreach (array_keys($pool) as $number)
		{
			if (!$clock->hasRowAt($number, $code))
			{
				continue;
			}
			unset($pool[$number]); // consume: furthest point only

			if (isset($racers[$number]))
			{
				$bucket[$number] = array(
					'point' => $checkpoint,
					'data'  => $racers[$number],
				);
			}
		}

		$globalOrder = racereports_vypluj_point($report, $clock, $bucket, $globalOrder, $order);
	}

	echo "</table>";
}

/**
 * Render one furthest-point bucket: split NORMAL (ranked, elapsed ASC/DESC) and
 * ENDED (DNF/DSQ/DNS/no-time, rendered after, non-ranked), assigning the
 * cumulative/local rank exactly as the legacy vypluj_point() (online.php:210).
 *
 * @return int the updated cumulative global rank
 */
function racereports_vypluj_point($report, $clock, $bucket, $globalOrder, $order = 'ASC')
{
	$tp = e107::getParser();

	// Central RESULT-time precision (DISPLAY-ONLY, truncates). Default 2 enforced
	// here at the consumer; passed explicitly to diffOnPoint so the engine helper
	// stays pref-agnostic (its own default is left at 1).
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$normal = array();
	$ended  = array();

	foreach ($bucket as $number => $racerData)
	{
		$racer  = $racerData['data'];
		$code   = (string) $racerData['point']['race_point_code'];
		$number = (string) $number;

		$racer['racer_name']  = $report->getRacerName($racer);
		$racer['point_code']  = $code;

		// DNS bucket: this report treats an un-marked racer (racer_active = false,
		// the manual bib-collection flag, source = racer table) as DNS. That is a
		// report-level reading of the flag, NOT a claim that racer_active == DNS;
		// the two are different concepts (see racereports/NOTES.md terminology note).
		if (empty($racer['racer_active']))
		{
			$racer['difftext'] = 'DNS';
			$racer['difftime'] = '';
			$ended[] = $racer;
			continue;
		}

		$diff = $report->diffOnPoint($clock, $number, $code, $resDec);

		if ($report->isRanked($diff))
		{
			$racer['difftime'] = $diff['time'];
			$racer['difftext'] = $diff['text']; // online ranked: time only, no flag
			$normal[] = $racer;
		}
		else
		{
			// Online ENDED difftext rules (online.php:254-265).
			if ($diff['time'] == -1)
			{
				$racer['difftext'] = $diff['text'];
			}
			else
			{
				$racer['difftext'] = '';
			}
			if ($diff['ended'] !== '')
			{
				$racer['difftext'] = (string) $diff['text'] . " " . $diff['ended'];
			}
			$racer['difftime'] = '';
			$ended[] = $racer;
		}
	}

	// Sort NORMAL by elapsed (full-precision float).
	if ($order === 'ASC')
	{
		usort($normal, function ($a, $b) { return $a['difftime'] <=> $b['difftime']; });
	}
	else
	{
		usort($normal, function ($a, $b) { return $b['difftime'] <=> $a['difftime']; });
	}

	$count      = count($normal);
	$localOrder = ($order === 'ASC') ? 0 : ($count + 1);
	$tdStyle    = ($order === 'DESC') ? "style='padding: 0;'" : '';

	foreach ($normal as $racer)
	{
		if ($order === 'ASC')
		{
			$localOrder  += 1;
			$globalOrder += 1;
		}
		else
		{
			$localOrder  -= 1;
			$globalOrder -= 1;
		}

		// Rank: cumulative across buckets, except the finish bucket shows local.
		$rankShown = ($racer['point_code'] !== 'finish') ? $globalOrder : $localOrder;

		$color = race_report_color($clock, $racer);

		echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
		echo "<td " . $tdStyle . ">" . (int) $rankShown . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML($racer['racer_number'], false, 'TITLE') . "</td>";
		echo "<td " . $tdStyle . ">" . $racer['racer_name'] . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML($racer['racer_nacionality'], false, 'TITLE') . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
		echo "<td " . $tdStyle . ">" . $tp->toHTML($racer['point_code'], false, 'TITLE') . "</td>";
		echo "</tr>";
	}

	foreach ($ended as $racer)
	{
		$pointCode = isset($racer['point_code']) ? $racer['point_code'] : '';
		// ENDED racers (DNF/DSQ/DNS) are NOT colored - only ranked/normal racers
		// get the per-category background, matching legacy timetracker online.
		echo "<tr>";
		echo "<td></td>";
		echo "<td>" . $tp->toHTML($racer['racer_number'], false, 'TITLE') . "</td>";
		echo "<td>" . $racer['racer_name'] . "</td>";
		echo "<td></td>";
		echo "<td>" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
		echo "<td>" . $tp->toHTML($pointCode, false, 'TITLE') . "</td>";
		echo "</tr>";
	}

	return $globalOrder;
}

/**
 * Per-category row background colour (race_category.race_category_color), looked
 * up the same way the legacy page did. Static cache for the request.
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
