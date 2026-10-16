<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_number.php
 *
*/

/*
 * Racer progression (docs/plugins/racereports.md, "Racer progression").
 * Query: ?n=<bib>; the racer's track (racer_race_id) and therefore its checkpoints
 * follow from the bib. All time math comes from the racetiming engine (timeOfDay /
 * elapsedRaw / elapsedBetween).
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
if (!defined('LAN_RACEREPORTS_NUM_UNKNOWN_BIB'))    define('LAN_RACEREPORTS_NUM_UNKNOWN_BIB', 'Unknown bib number.');
if (!defined('LAN_RACEREPORTS_NUM_COL_POINT'))      define('LAN_RACEREPORTS_NUM_COL_POINT', 'Checkpoint');
if (!defined('LAN_RACEREPORTS_NUM_COL_TIMEOFDAY'))  define('LAN_RACEREPORTS_NUM_COL_TIMEOFDAY', 'Time of day');
if (!defined('LAN_RACEREPORTS_NUM_COL_SPLIT'))      define('LAN_RACEREPORTS_NUM_COL_SPLIT', 'Split');
if (!defined('LAN_RACEREPORTS_NUM_COL_SEGMENT'))    define('LAN_RACEREPORTS_NUM_COL_SEGMENT', 'Segment');
if (!defined('LAN_RACEREPORTS_NUM_DNF'))            define('LAN_RACEREPORTS_NUM_DNF', 'DNF');
if (!defined('LAN_RACEREPORTS_NUM_DSQ'))            define('LAN_RACEREPORTS_NUM_DSQ', 'DSQ');

// Engine (racetiming) + shared report helper.
require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// Bib stays a STRING (never (int)-cast - leading zeros are data).
$bib = isset($_GET['n']) ? $tp->toDB($_GET['n']) : '';

if ($bib === '')
{
	echo "<div class='container'><p>" . $tp->toHTML(LAN_RACEREPORTS_NUM_UNKNOWN_BIB, false) . "</p></div>";
	require_once(FOOTERF);
	exit;
}

// #racer lookup by bib: toDB()-escaped string, quoted - never cast.
$racerRow = e107::getDb()->retrieve(
	"SELECT * FROM " . MPREFIX . "racer WHERE racer_number = '" . $bib . "'"
);

if (empty($racerRow))
{
	echo "<div class='container'><p>" . $tp->toHTML(LAN_RACEREPORTS_NUM_UNKNOWN_BIB, false) . "</p></div>";
	require_once(FOOTERF);
	exit;
}

// Heading "<bib> — <name>": getRacerName() returns safe HTML; the bib is escaped
// here.
$heading = "<strong>" . $tp->toHTML($bib, false) . "</strong> — " . $report->getRacerName($racerRow);
echo "<h2>" . $heading . "</h2>";

$raceId = (int) $racerRow['racer_race_id'];

// fetchCheckpoints() is DESC; walk the course forwards, so re-sort ASC.
$points = $report->fetchCheckpoints($raceId);
usort($points, function ($a, $b)
{
	return (int) $a['race_point_order'] <=> (int) $b['race_point_order'];
});

// result_decimals (display-only, truncates).
$resDec = (int) e107::getPlugConfig('racereports')->get('result_decimals', 2);

$blank = '—';

echo '<div class="table-responsive">';
echo "<table class='table table-striped table-bordered table-hover'>";

echo "<thead><tr>";
echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_NUM_COL_POINT, false) . "</th>";
echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_NUM_COL_TIMEOFDAY, false) . "</th>";
echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_NUM_COL_SPLIT, false) . "</th>";
echo "<th>" . $tp->toHTML(LAN_RACEREPORTS_NUM_COL_SEGMENT, false) . "</th>";
echo "</tr></thead><tbody>";

// Segment column: measured from the last point the racer was actually SEEN at,
// so a missed checkpoint does not blank every following segment.
$prevSeen = null;
$first    = true;

foreach ($points as $point)
{
	$code = (string) $point['race_point_code'];
	$name = $tp->toHTML($point['race_point_name'], false, 'TITLE');

	// Time of day: blank if no usable crossing here.
	$tod     = $clock->timeOfDay($bib, $code);
	$todText = ($tod !== null) ? race_clock::formatTimeOfDay($tod, $resDec) : '';

	// Split: elapsed start -> this point; blank for the start row and with no
	// crossing. elapsedRaw() is a pure subtraction, so it still shows points
	// reached before a later DNF/DSQ.
	$splitText = '';
	if ($code !== 'start')
	{
		$split     = $clock->elapsedRaw($bib, $code);
		$splitText = ($split !== null) ? race_format::formatElapsed($split, $resDec) : '';
	}

	// Segment: from the last SEEN point; blank for the first point and when
	// elapsedBetween() returns null.
	$segText = '';
	if (!$first && $prevSeen !== null)
	{
		$seg     = $clock->elapsedBetween($bib, $prevSeen, $code);
		$segText = ($seg !== null) ? race_format::formatElapsed($seg, $resDec) : '';
	}

	echo "<tr>";
	echo "<td>" . $name . "</td>";
	echo "<td>" . ($todText !== '' ? $tp->toHTML($todText, false) : $blank) . "</td>";
	echo "<td>" . ($splitText !== '' ? $tp->toHTML($splitText, false) : $blank) . "</td>";
	echo "<td>" . ($segText !== '' ? $tp->toHTML($segText, false) : $blank) . "</td>";
	echo "</tr>";

	// Advance the "last seen" marker only when the racer actually crossed here, so
	// the NEXT segment is measured from the last point he was seen at.
	if ($clock->hasCrossingAt($bib, $code))
	{
		$prevSeen = $code;
	}
	$first = false;
}

echo "</tbody></table>";
echo "</div>";

// DNF/DSQ status note (with the marker point when known); DSQ takes precedence.
$statusLabel = '';
$statusFlag  = '';
if ($clock->isDsq($bib))
{
	$statusLabel = LAN_RACEREPORTS_NUM_DSQ;
	$statusFlag  = 'DSQ';
}
elseif ($clock->isDnf($bib))
{
	$statusLabel = LAN_RACEREPORTS_NUM_DNF;
	$statusFlag  = 'DNF';
}

if ($statusLabel !== '')
{
	$note     = $tp->toHTML($statusLabel, false);
	$endPoint = $clock->endedPoint($bib, $statusFlag);
	if ($endPoint !== null)
	{
		// Resolve the marker point's display name from the points we already loaded.
		foreach ($points as $point)
		{
			if ((string) $point['race_point_code'] === (string) $endPoint)
			{
				$note .= " (" . $tp->toHTML($point['race_point_name'], false, 'TITLE') . ")";
				break;
			}
		}
	}

	echo "<p><strong>" . $note . "</strong></p>";
}

require_once(FOOTERF);
