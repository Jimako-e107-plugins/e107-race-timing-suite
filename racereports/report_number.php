<?php
/*
 * e107 website system
 *
 * racereports plugin - NUMBER (single-racer progression) report.
 *
 * How ONE racer passed the WHOLE course: start -> checkpoints -> finish, ONE row
 * per point in race_point ORDER (start/finish INCLUDED - this report WANTS them,
 * unlike the point/dobeh boards). Input is a single bib (?n=<bib>); the racer's
 * ONE track (racer.racer_race_id) - and therefore its checkpoint set - follows
 * from the bib alone, so there is NO race param, NO komplet, NO track param.
 *
 * Columns (per point):
 *   Kontrola  - the checkpoint name (race_point_name).
 *   Čas dňa   - arrival WALL-CLOCK = race_clock::timeOfDay(bib, code) formatted by
 *               race_clock::formatTimeOfDay(epoch, $resDec); blank if no crossing.
 *   Medzičas  - elapsed START -> this point = race_clock::elapsedRaw(bib, code)
 *               formatted by race_format::formatElapsed(., $resDec); blank for the
 *               start row and where there is no usable crossing.
 *   Úsek      - segment from the PREVIOUS point the racer was actually SEEN at (the
 *               last point IN ORDER with a crossing, so a missed checkpoint does not
 *               blank every following segment) = race_clock::elapsedBetween(bib,
 *               prevSeen, thisCode) formatted by formatElapsed(., $resDec); blank for
 *               the first point and where elapsedBetween returns null.
 *
 * All time math comes from the racetiming engine (race_clock + race_format); this
 * page does ONLY presentation. NO DataTables, NO export, NO komplet, NO links to
 * other reports. DNF/DSQ: the points the racer reached are rendered as-is; if the
 * engine flags him ended, a status note (DNF/DSQ, with the marker point when known)
 * is shown - no times are fabricated for points he never reached. A missing crossing
 * at a point -> blank cells (—), never zero, never an error.
 *
 * SECURITY: ?n goes through $tp->toDB(); the bib/start number stays a STRING (never
 * (int)-cast - leading zeros are data) and the #racer lookup is parametrised; race
 * id is (int)-cast; every output cell is escaped (toHTML/toAttribute). No extract().
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

// Engine (racetiming) + shared report helper. Loaded by path so no <dependency>
// on racetiming is required (the skeleton deps are left unchanged).
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

// Parametrised #racer lookup by bib (string, quoted - never cast).
$racerRow = e107::getDb()->retrieve(
	"SELECT * FROM " . MPREFIX . "racer WHERE racer_number = '" . $bib . "'"
);

if (empty($racerRow))
{
	echo "<div class='container'><p>" . $tp->toHTML(LAN_RACEREPORTS_NUM_UNKNOWN_BIB, false) . "</p></div>";
	require_once(FOOTERF);
	exit;
}

// HEADING: "<bib> — <name>", the name via the SHARED getRacerName (the one place,
// so team/local prefs render identically to every other report). getRacerName
// returns already-safe HTML; the bib is escaped here.
$heading = "<strong>" . $tp->toHTML($bib, false) . "</strong> — " . $report->getRacerName($racerRow);
echo "<h2>" . $heading . "</h2>";

$raceId = (int) $racerRow['racer_race_id'];

// Checkpoints in race_point ORDER (start -> finish). fetchCheckpoints() returns
// race_point_order DESC (finish-first, the online bucketing order); this report
// walks the course forwards, so re-sort ASCENDING by race_point_order.
$points = $report->fetchCheckpoints($raceId);
usort($points, function ($a, $b)
{
	return (int) $a['race_point_order'] <=> (int) $b['race_point_order'];
});

// result_decimals: these ARE result times (truncated, display-only). Central pref,
// default 2 (the same as the other result reports).
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

// "Previous point" for the segment column = the last point IN ORDER the racer was
// actually SEEN at (has a usable crossing), so a missed checkpoint does not blank
// every following segment - the segment is from the last point he WAS at.
$prevSeen = null;
$first    = true;

foreach ($points as $point)
{
	$code = (string) $point['race_point_code'];
	$name = $tp->toHTML($point['race_point_name'], false, 'TITLE');

	// Col 2 - Čas dňa: arrival wall-clock; blank if no usable crossing here.
	$tod     = $clock->timeOfDay($bib, $code);
	$todText = ($tod !== null) ? race_clock::formatTimeOfDay($tod, $resDec) : '';

	// Col 3 - Medzičas: elapsed start->this point. Blank for the start row (elapsed
	// 0 / not meaningful) and where there is no usable crossing. elapsedRaw() is the
	// pure subtraction (it shows the time at points the racer reached even if he is
	// flagged ended elsewhere later).
	$splitText = '';
	if ($code !== 'start')
	{
		$split     = $clock->elapsedRaw($bib, $code);
		$splitText = ($split !== null) ? race_format::formatElapsed($split, $resDec) : '';
	}

	// Col 4 - Úsek: segment from the last SEEN point to this one. Blank for the first
	// point and where either crossing is missing (elapsedBetween returns null).
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

// DNF/DSQ: if the engine flags the racer ended, show a status note (with the marker
// point when the engine can resolve it). DSQ takes precedence over DNF. No times are
// fabricated for points he never reached - the table above already shows blanks (—)
// where he has no crossing.
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
