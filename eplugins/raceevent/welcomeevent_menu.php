<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - welcomeevent_menu.php
 *
*/

/*
 * Welcome menu. Discovered by the Menu Manager from the *_menu.php filename (no
 * plugin.xml entry needed); renders the 'welcome' template.
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

// Front LAN (languages/<lang>_front.php) for the menu caption.
e107::lan('raceevent', true);

// Load the welcome template and parse its shortcodes the native e107 way. The
// raceevent e_shortcode.php batch is registered site-wide, so parseTemplate()
// resolves {RACEEVENT_*} automatically.
$template = e107::getTemplate('raceevent', null, 'welcome');
$text     = e107::getParser()->parseTemplate($template, true);

e107::getRender()->tablerender(LAN_RACEEVENT_WELCOME, $text, 'raceevent-welcome');
