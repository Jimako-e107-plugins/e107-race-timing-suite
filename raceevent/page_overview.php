<?php
/*
 * e107 website system
 *
 * raceevent base plugin - EVENT OVERVIEW front page.
 *
 * Public front page for the cross-suite link directory. Served via the
 * e107::url('raceevent', 'index', ...) route (SEF alias 'preteky', the original
 * alias - see e_url.php). This is the strangler replacement for the legacy
 * hand-made timetracker/index.php overview; timetracker's own 'index'/'preteky'
 * route stays commented as a trace.
 *
 * The link list itself lives in ONE shared include
 * (includes/event_overview.php) so this page and the admin "Prehľad preteku"
 * screen render identically. This file only adds the front chrome
 * (HEADERF/FOOTERF + tablerender) around the include's returned HTML.
 *
 * READ-ONLY: the include does db reads + link building only, no writes.
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// Front LAN for the page caption (the include loads it too, but the caption is
// used here before the include runs).
e107::lan('raceevent');

require_once(HEADERF);

require_once(e_PLUGIN . 'raceevent/includes/event_overview.php');

// The shared include returns the HTML; the front page wraps it in tablerender.
$text = raceevent_event_overview();

e107::getRender()->tablerender(LAN_RACEEVENT_OV_CAPTION, $text, 'raceevent-overview');

require_once(FOOTERF);
