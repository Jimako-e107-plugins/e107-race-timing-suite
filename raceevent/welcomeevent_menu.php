<?php
/*
 * e107 website system
 *
 * raceevent base plugin - welcome event menu.
 *
 * Auto-discovered by the menu manager from the *_menu.php filename convention
 * (the same way timetracker exposed welcomeintro_menu.php - no plugin.xml entry
 * is required). Parses the 'welcome' front template with the raceevent
 * shortcodes and renders the result.
 *
 * The event name + description come from the RACEEVENT plugin prefs
 * (e107::getPlugConfig('raceevent')) via the {RACEEVENT_EVENT_NAME} and
 * {RACEEVENT_EVENT_DESCRIPTION} shortcodes in raceevent/e_shortcode.php.
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
