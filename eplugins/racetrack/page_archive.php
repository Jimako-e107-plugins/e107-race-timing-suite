<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - page_archive.php
 *
*/

/*
 * Public archive page (docs/plugins/racetrack.md, "Archive page"). Shows the
 * stored race_archive_html and never recomputes it.
 *
 * SECURITY: ?a is user input -> $tp->toDB() before SQL, LIMIT 1. The stored HTML
 * is echoed verbatim: it was produced by racereports (every cell escaped at
 * generate time) or typed by a 'P'-permission admin, never by a visitor. The
 * caption still goes through toHTML(..., 'TITLE').
 */
if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// DataTables library from racereports (runtime check, not a hard dependency;
// without it the table stays static). racetrack's own init.js attaches to every
// .table in the snapshot. Must be registered BEFORE HEADERF so the CSS/JS land
// in <head>.
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

// retrieve() returns the single matching row (assoc) or an empty result.
$archive = ($sef !== '')
	? $sql->retrieve('race_archive', '*', "race_archive_sef = '" . $sef . "' LIMIT 1")
	: false;

if (!empty($archive) && is_array($archive))
{
	$caption = $tp->toHTML((string) $archive['race_archive_name'], false, 'TITLE');

	// Echoed as stored, NOT re-parsed: the HTML was already escaped at generate
	// time, and a second toHTML(..., 'DESCRIPTION') pass would alter the text.
	// If a snapshot needs {e_PLUGIN}-style constants resolved, use
	// $tp->replaceConstants($html, 'full') (no BBCode/shortcode pass).
	$body    = (string) $archive['race_archive_html'];
}
else
{
	$msg  = defined('LAN_RT_ARCHIVE_NOT_FOUND') ? LAN_RT_ARCHIVE_NOT_FOUND : 'Archive not found.';
	$body = "<div class='alert alert-danger'>" . $tp->toHTML($msg, false) . "</div>";
}

e107::getRender()->tablerender($caption, $body);

require_once(FOOTERF);
