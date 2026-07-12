<?php
/*
 * e107 website system
 *
 * racereports plugin - POINT report (clean equivalent of timetracker_point.php).
 *
 * Per-checkpoint standings: all racers of a race timed to ONE chosen checkpoint,
 * sorted by elapsed start->that checkpoint ASCENDING. p=komplet renders every
 * checkpoint of the race except start/finish.
 *
 * All time math comes from the racetiming engine (race_clock + race_format);
 * this page does ONLY presentation: fetch racers, read the checkpoint, bucket
 * into THREE visibility groups and render. Output is escaped.
 *
 *   1. PASSED  - a usable crossing at the point -> ranked (elapsed ASC), coloured,
 *                shown to EVERYONE.
 *   2. ENDED   - DNF/DSQ/DNS (a real ended marker via the engine) -> shown to
 *                EVERYONE after the ranked block, NOT coloured, no rank.
 *   3. WAITING - no crossing here AND not ended ("still waited for") -> shown ONLY
 *                to ADMIN; non-admin visitors do not see waiting racers at all.
 *
 * Public columns: rank-at-point, bib (string), name, time. For ADMIN ONLY an extra
 * trailing column carries, on every row that HAS a stored crossing (race_time_id),
 * an edit icon that opens the racetiming race_time admin on that exact row in a NEW
 * window. Rows with no stored crossing (crossingId null) get no icon. The whole
 * admin column is gated on ADMIN. See racereports/NOTES.md.
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
	// The POINT report is scoped to a single race; all-races is the online view's
	// job. Keep the live read simple and explicit rather than reproducing the
	// legacy broken r=komplet branch (see NOTES.md).
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
 * Render one per-checkpoint table in THREE visibility groups:
 *   PASSED (ranked, elapsed ASC, coloured, everyone) -> ENDED (everyone, no rank,
 *   not coloured) -> WAITING (ADMIN only). For ADMIN an extra trailing column
 *   carries an edit icon on every row that has a stored crossing (race_time_id).
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

	// Central RESULT-time precision (DISPLAY-ONLY, truncates). Default 2 enforced
	// here at the consumer; passed explicitly to diffOnPoint (helper default left 1).
	$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

	$racers = $report->fetchRacers($raceId);

	$passed  = array(); // usable crossing here -> ranked
	$ended   = array(); // DNF/DSQ/DNS marker -> shown to everyone, no rank
	$waiting = array(); // no crossing here AND not ended -> ADMIN only

	foreach ($racers as $number => $racer)
	{
		$racer['racer_name'] = $report->getRacerName($racer);
		// Crossing identity for the admin edit link (null when no row here).
		$racer['race_time_id'] = $clock->crossingId((string) $number, $pointId);

		$diff = $report->diffOnPoint($clock, $number, $pointId, $resDec);
		// Legacy point page time cell = text . " " . ended (trailing space when no
		// flag) - reproduced for parity.
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
			// ENDED: a real DNF/DSQ/DNS marker (at this point or racer-wide). The
			// engine separates this from the bare no-crossing case (diffOnPoint);
			// no re-query.
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

	// ENDED (DNF/DSQ/DNS) rendered AFTER the ranked block, non-ranked, shown to
	// EVERYONE. NOT coloured - only ranked racers get the per-category background,
	// matching the online report (report_online.php).
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

	// WAITING (no crossing here, not ended - "still waited for") rendered ONLY for
	// ADMIN. Non-admin visitors do not see these rows at all.
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
 * ADMIN-only status cell sitting BETWEEN the name and time columns: a plain glyph
 * telling, at a glance, whether this racer has a stored crossing at THIS point.
 *
 *   ✅  the racer HAS a crossing here (race_time_id is non-null)
 *   ❌  the racer has NO crossing here (race_time_id is null)
 *
 * This restores the admin-only ✅/❌ column the legacy timetracker_point.php showed.
 * It is driven PURELY off race_time_id (= crossingId(number, point), already on the
 * row from render_point_list) - no extra query. The emoji are literal UTF-8 in this
 * file; they are deliberately NOT routed through toGlyph/FontAwesome because the
 * frontend theme may render FA differently or not at all. Non-admins get NO cell, so
 * for them the column does not exist - exactly like legacy.
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
 * ADMIN-only trailing cell: an edit icon linking the racetiming race_time admin
 * (NEW window) on this racer's crossing row, when one is stored. Rows with no
 * stored crossing (crossingId null) get an empty cell - nothing to edit. The whole
 * column is gated on ADMIN, so non-admins never see it.
 *
 * The id is the only value placed in the URL and is (int)-cast there; the bib /
 * name / time cells stay escaped in the public columns.
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
 * Per-category row background colour, looked up the same way the legacy pages
 * did (timetracker::$cats[category_id]['race_category_color']). Read once here to
 * keep the engine free of presentation concerns.
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
