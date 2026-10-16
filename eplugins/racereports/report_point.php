<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_point.php
 *
*/

/*
 * Checkpoint times (docs/plugins/racereports.md, "Checkpoint times"). All time math
 * comes from the racetiming engine; this page only groups and renders. The admin edit
 * link carries only the (int) race_time_id.
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
// Core e107 LAN (loaded by the front bootstrap); defensive fallback so the admin
// edit-icon title can never fatal on an undefined constant under PHP 8.
if (!defined('LAN_EDIT')) define('LAN_EDIT', 'Edit');

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
	// Not supported: the checkpoint report is per track.
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

if ($pointId === 'komplet')
{
	// Every checkpoint of the race except start/finish.
	$checkpoints = $report->fetchCheckpoints($raceId);
	foreach ($checkpoints as $point)
	{
		if ($point['race_point_code'] === 'start') continue;
		if ($point['race_point_code'] === 'finish') continue;

		echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
			. " - " . $tp->toHTML($point['race_point_name'], false, 'TITLE') . "</h2>";

		render_point_list($report, $clock, $raceId, $point);
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

	render_point_list($report, $clock, $raceId, $point);
}

/**
 * Render one per-checkpoint table: PASSED (ranked) -> ENDED (DNF/DSQ) -> WAITING
 * (ADMIN only), plus the admin status / edit cells.
 *
 * @param race_report $report
 * @param race_clock  $clock
 * @param int         $raceId
 * @param array       $point  race_point row (uses race_point_code)
 */
function render_point_list($report, $clock, $raceId, $point)
{
	$tp      = e107::getParser();
	$pointId = (string) $point['race_point_code'];

	// result_decimals (display-only, truncates), passed explicitly to diffOnPoint.
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId);

	$passed  = array(); // usable crossing here -> ranked
	$ended   = array(); // DNF/DSQ marker -> shown to everyone, no rank
	$waiting = array(); // no crossing here AND not ended -> ADMIN only

	foreach ($racers as $number => $racer)
	{
		$racer['racer_name'] = $report->getRacerName($racer);
		// Crossing identity for the admin edit link (null when no row here).
		$racer['race_time_id'] = $clock->crossingId((string) $number, $pointId);

		$diff = $report->diffOnPoint($clock, $number, $pointId, $resDec);
		// Time cell = text . " " . ended marker (the trailing space when there is
		// no marker is kept from the old timetracker output).
		$racer['difftext'] = $diff['text'] . " " . $diff['ended'];

		if ($report->isRanked($diff))
		{
			// PASSED: a usable crossing at the point.
			$racer['difftime'] = $diff['time'];
			$passed[] = $racer;
		}
		elseif ($clock->endedAt((string) $number, $pointId) !== ''
			|| $clock->isDnf((string) $number) || $clock->isDsq((string) $number))
		{
			// ENDED: a DNF/DSQ marker (at this point or racer-wide).
			$racer['difftime'] = '';
			$ended[] = $racer;
		}
		else
		{
			// WAITING: no crossing here and no ended marker - "still waited for".
			$racer['difftime'] = '';
			$waiting[] = $racer;
		}
	}

	// Sort PASSED by elapsed ascending (full-precision float, display-independent).
	usort($passed, function ($a, $b)
	{
		return $a['difftime'] <=> $b['difftime'];
	});

	echo '<div class="table-responsive">';
	echo "<table class='table table-striped table-bordered table-hover'>";

	$rank = 0;
	foreach ($passed as $racer)
	{
		$rank++;
		$color = race_report_color($clock, $racer);
		echo "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
		echo "<td>" . (int) $rank . "</td>";
		echo "<td>" . $tp->toHTML($racer['racer_number'], false, 'TITLE') . "</td>";
		echo "<td>" . $racer['racer_name'] . "</td>";
		point_admin_status_cell($racer);
		echo "<td>" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
		point_admin_edit_cell($tp, $racer);
		echo "</tr>";
	}

	// ENDED (DNF/DSQ): after the ranked block, no rank, not coloured.
	foreach ($ended as $racer)
	{
		echo "<tr>";
		echo "<td></td>";
		echo "<td>" . $tp->toHTML($racer['racer_number'], false, 'TITLE') . "</td>";
		echo "<td>" . $racer['racer_name'] . "</td>";
		point_admin_status_cell($racer);
		echo "<td>" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
		point_admin_edit_cell($tp, $racer);
		echo "</tr>";
	}

	// WAITING: ADMIN only.
	if (ADMIN)
	{
		foreach ($waiting as $racer)
		{
			echo "<tr>";
			echo "<td></td>";
			echo "<td>" . $tp->toHTML($racer['racer_number'], false, 'TITLE') . "</td>";
			echo "<td>" . $racer['racer_name'] . "</td>";
			point_admin_status_cell($racer);
			echo "<td>" . $tp->toHTML($racer['difftext'], false, 'TITLE') . "</td>";
			point_admin_edit_cell($tp, $racer);
			echo "</tr>";
		}
	}

	echo "</table>";
	echo "</div>";
}

/**
 * ADMIN-only status cell between the name and time columns: ✅ when a crossing is
 * stored at this point (race_time_id non-null), ❌ otherwise. No extra query. The
 * emoji are literal UTF-8, NOT toGlyph/FontAwesome, because the front theme may
 * not render FA. Non-admins get no cell at all.
 *
 * @param array $racer  carries 'race_time_id' (?int) from crossingId()
 */
function point_admin_status_cell($racer)
{
	if (!ADMIN)
	{
		return;
	}

	$raceTimeId = isset($racer['race_time_id']) ? $racer['race_time_id'] : null;

	echo ($raceTimeId !== null) ? "<td>✅</td>" : "<td>❌</td>";
}

/**
 * ADMIN-only trailing cell: an edit icon to the racetiming race_time admin (new
 * window) for this racer's crossing, or an empty cell when none is stored. The id
 * is the only value placed in the URL and is (int)-cast there.
 *
 * @param e_parse $tp
 * @param array   $racer  carries 'race_time_id' (?int) from crossingId()
 */
function point_admin_edit_cell($tp, $racer)
{
	if (!ADMIN)
	{
		return;
	}

	$raceTimeId = isset($racer['race_time_id']) ? $racer['race_time_id'] : null;
	if ($raceTimeId === null)
	{
		echo "<td></td>";
		return;
	}

	$url  = e_PLUGIN_ABS . "racetiming/admin/admin_config.php?action=edit&id=" . (int) $raceTimeId;
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

require_once(FOOTERF);
