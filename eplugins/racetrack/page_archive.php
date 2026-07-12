<?php
/*
 * e107 website system
 *
 * racetrack plugin - ARCHIVE front view (read-only).
 *
 * Reached via e107::url('racetrack', 'archiv', array('race_archive_sef' => ...))
 * -> the 'archiv' route in racetrack/e_url.php (?a=<race_archive_sef>). It shows a
 * FROZEN archive snapshot: the race_archive_html that was captured at generate
 * time, echoed VERBATIM. It NEVER recomputes - this is the fix for the legacy
 * Quick-View bug where opening the archive secretly re-ran the generator
 * (&generuj=1). Generation happens ONLY from the admin triggers (the "Archivovat"
 * race-row button and the "Pregenerovat" archive button).
 *
 * SECURITY: ?a is user input -> $tp->toDB() before it touches SQL, LIMIT 1. The
 * stored HTML is rendered through $tp->toHTML(..., true, 'DESCRIPTION') (the same
 * filter the legacy front archive used) and the caption through toHTML/TITLE.
 */
if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// DataTables assets for the archive table. racereports OWNS the bs5 build + init
// (assets/datatables/), so we reuse that single copy instead of depending on the
// decommissioned timetracker one. Runtime-guarded read via isInstalled() - NOT a
// hard <dependencies>; if racereports is absent the archive degrades to a plain
// static table. MUST be registered BEFORE HEADERF so the CSS/JS land in <head>.
// NOTE: racereports/assets/datatables/init.js binds to #report_stu / #report_aktualne
// by ID - the stored snapshot's <table> must carry one of those ids for DataTables
// to attach (new generator emits #report_aktualne; verify legacy snapshots).
if (e107::isInstalled('racereports'))
{
	e107::css('racereports', 'assets/datatables/css/datatables-bs5.min.css');
	e107::js('racereports', 'assets/datatables/js/datatables-bs5.min.js', 'jquery');
	e107::js('racetrack', 'init.js', 'jquery');
}

define('THEME_LAYOUT', 'splash');

require_once(HEADERF);

$tp  = e107::getParser();
$sql = e107::getDb();

e107::lan('racetrack', '', true); // front strings (LAN_RT_ARCHIVE_*)

$sef = isset($_GET['a']) ? $tp->toDB($_GET['a']) : '';

$caption = defined('LAN_RT_ARCHIVE_TITLE') ? LAN_RT_ARCHIVE_TITLE : 'Archive';
$body    = '';

// toDB()'d sef, LIMIT 1; retrieve() returns the single matching row (assoc) or
// an empty result. No recompute - pure read.
$archive = ($sef !== '')
	? $sql->retrieve('race_archive', '*', "race_archive_sef = '" . $sef . "' LIMIT 1")
	: false;

if (!empty($archive) && is_array($archive))
{
	$caption = $tp->toHTML((string) $archive['race_archive_name'], false, 'TITLE');
	// VERBATIM frozen snapshot - rendered, never recomputed.
	$body    = $tp->toHTML((string) $archive['race_archive_html'], true, 'DESCRIPTION');
}
else
{
	$msg  = defined('LAN_RT_ARCHIVE_NOT_FOUND') ? LAN_RT_ARCHIVE_NOT_FOUND : 'Archive not found.';
	$body = "<div class='alert alert-danger'>" . $tp->toHTML($msg, false) . "</div>";
}

e107::getRender()->tablerender($caption, $body);

require_once(FOOTERF);
