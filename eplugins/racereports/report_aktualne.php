<?php
/*
 * e107 website system
 *
 * racereports plugin - AKTUALNE report PAGE (the FULL per-race results matrix;
 * clean equivalent of timetracker/aktualne.php + timetrackerArchive_class.php).
 *
 * ONE race, EVERY racer x EVERY checkpoint in one wide table. Columns:
 *   Por. | Meno | Kat. | Cas | <one column per checkpoint except 'start', in
 *   race_point_order ASC (the last of which is the 'finish' checkpoint)> |
 *   Rank v kategorii
 * Three row-blocks in ONE table, rendered in order (legacy printrows 0/1/2):
 *   NORMAL, then DNF, then DSQ.
 *
 * Legacy contract mirrored: ?p selects a RACE by race_id (timetracker/aktualne.php:35
 * `WHERE race_id=$_GET['p']`), NOT a sef - identical to report_stu.php.
 *
 * ARCHITECTURE (Phase-2 ready): this PAGE only bootstraps + echoes. All the table
 * building lives in the PURE include includes/aktualne_build.php, whose
 * racereports_aktualne_build($raceId, $clock, $report) RETURNS
 * ['html' => <table html string>, 'data' => <structured rows>]. The page echoes
 * ['html']; Phase 2 (archive writing) will capture the SAME string + persist
 * ['data'] (mirrors raceevent_event_overview()'s return-the-HTML shape, and legacy
 * aktualne.php which captured $text into race_archive_html). The same include is
 * reused by parity/parity_aktualne.php. NO archive write happens here - Phase 1 is
 * the on-screen report ONLY.
 *
 * TIME MATH comes entirely from the racetiming engine (race_clock + race_format);
 * this page/include only fetches / partitions / orders / formats. Two DISTINCT
 * legacy truncations are reproduced EXACTLY (see the include / NOTES.md): every
 * per-checkpoint cell (incl. the 'finish' checkpoint column) = HH:MM (legacy
 * substr 0,5); the dedicated "Cas" column = HH:MM:SS (legacy substr 0,8).
 *
 * SECURITY: ?p is (int)-cast (it is a race_id); the bib/start number stays a STRING;
 * every SQL value goes through $tp->toDB(); output is escaped via toHTML/toAttribute.
 * The legacy extract() and raw-$_GET-into-SQL are NOT carried over. The engine reads
 * race_time only; no writes here.
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

// Suppress site chrome (legacy aktualne.php does this BEFORE HEADERF).
if (!defined('e_IFRAME')) define('e_IFRAME', true);

$tp     = e107::getParser();
$report = new race_report();

$clock = new race_clock();
$clock->build(); // one read of race_time -> split map + DNF/DSQ sets

// ?p selects a RACE by race_id (legacy parity, identical to report_stu.php).
$raceId = isset($_GET['p']) ? (int) $_GET['p'] : 0;
$race   = $raceId > 0 ? $report->fetchRaceById($raceId) : false;

// Compact cells like the legacy archive table.
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);

// Sortable + basic Search box via the racereports CENTRAL DataTables loader (the
// bs5 build owned by racereports). The #report_aktualne init (searching:true,
// paging:false, info:false, order col0 asc) lives in assets/datatables/init.js.
race_report_load_datatables();

if (empty($race))
{
	// Mirror legacy "Neurceny pretek" (no ?p) / "Neznamy pretek" (unknown id).
	$msg = ($raceId > 0) ? LAN_RACEREPORTS_AKT_UNKNOWN_RACE : LAN_RACEREPORTS_NO_RACE;
	echo "<div class='container'><div class='alert alert-danger'>"
		. $tp->toHTML($msg, false) . "</div></div>";
	require_once(FOOTERF);
	exit;
}

echo "<h2>" . $tp->toHTML($race['race_name'], false, 'TITLE')
	. " &mdash; " . $tp->toHTML(LAN_RACEREPORTS_AKT_TITLE, false, 'TITLE') . "</h2>";

// Build the FULL matrix (returns html + data); Phase 2 will reuse the SAME return.
$built = racereports_aktualne_build($raceId, $clock, $report);

echo $built['html'];

require_once(FOOTERF);
