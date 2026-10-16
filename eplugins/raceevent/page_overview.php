<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - page_overview.php
 *
*/

/*
 * Public event overview page. The link list is built by the shared include
 * includes/event_overview.php (also used by admin/admin_overview.php); this file
 * only adds the front chrome (HEADERF/FOOTERF + tablerender).
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
