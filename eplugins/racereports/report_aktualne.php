<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - report_aktualne.php
 *
*/

/*
 * Full results page (docs/plugins/racereports.md, "Full results (aktuálne)"). ?p selects
 * the race by race_id. The table is built by includes/aktualne_build.php, which the
 * racetrack archive uses as well; this page only bootstraps and echoes it.
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// LAN + engine/report/build loaded FIRST so the build can run before HEADERF.
e107::lan('racereports', '', true); // front strings: languages/English/English_front.php

require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
require_once(e_PLUGIN . 'racetiming/includes/race_format.php');
require_once(e_PLUGIN . 'racereports/includes/race_report.php');
require_once(e_PLUGIN . 'racereports/includes/aktualne_build.php');

// Defensive fallbacks so a missing/odd LAN load path can never fatal a front page
// on an undefined constant (PHP 8). The loaded front file is the source of truth.
if (!defined('LAN_RACEREPORTS_NO_RACE'))          define('LAN_RACEREPORTS_NO_RACE', 'No race selected.');
if (!defined('LAN_RACEREPORTS_AKT_UNKNOWN_RACE')) define('LAN_RACEREPORTS_AKT_UNKNOWN_RACE', 'Unknown race.');
if (!defined('LAN_RACEREPORTS_AKT_TITLE'))        define('LAN_RACEREPORTS_AKT_TITLE', 'Full results');
if (!defined('LAN_RACEREPORTS_AKT_EMPTY'))        define('LAN_RACEREPORTS_AKT_EMPTY', 'No racers.');
if (!defined('LAN_RACEREPORTS_AKT_COL_POR'))      define('LAN_RACEREPORTS_AKT_COL_POR', 'Pos.');
if (!defined('LAN_RACEREPORTS_AKT_COL_NAME'))     define('LAN_RACEREPORTS_AKT_COL_NAME', 'Name');
if (!defined('LAN_RACEREPORTS_AKT_COL_CAT'))      define('LAN_RACEREPORTS_AKT_COL_CAT', 'Cat.');
if (!defined('LAN_RACEREPORTS_AKT_COL_TIME'))     define('LAN_RACEREPORTS_AKT_COL_TIME', 'Time');
if (!defined('LAN_RACEREPORTS_AKT_COL_CATRANK'))  define('LAN_RACEREPORTS_AKT_COL_CATRANK', 'Rank in category');

// No site header/footer; must be defined before HEADERF.
if (!defined('e_IFRAME')) define('e_IFRAME', true);

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// ?p selects a RACE by race_id (as in report_stu.php).
$raceId = isset($_GET['p']) ? (int) $_GET['p'] : 0;
$race   = $raceId > 0 ? $report->fetchRaceById($raceId) : false;

// Compact cells.
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);

// Sortable + Search box (the #report_aktualne init is in assets/datatables/init.js).
race_report_load_datatables();

if (empty($race))
{
	// No ?p -> "No race selected."; unknown id -> "Unknown race.".
	$msg = ($raceId > 0) ? LAN_RACEREPORTS_AKT_UNKNOWN_RACE : LAN_RACEREPORTS_NO_RACE;
	echo "<div class='container'><div class='alert alert-danger'>"
		. $tp->toHTML($msg, false) . "</div></div>";
	require_once(FOOTERF);
	exit;
}

echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
	. " &mdash; " . $tp->toHTML(LAN_RACEREPORTS_AKT_TITLE, false, 'TITLE') . "</h2>";

// Build the matrix (returns html + data; racetrack's archive stores the same).
$built = racereports_aktualne_build($raceId, $clock, $report);

echo $built['html'];

require_once(FOOTERF);
