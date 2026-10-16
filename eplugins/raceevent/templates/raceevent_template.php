<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - templates/raceevent_template.php
 *
*/

/*
 * Front templates, loaded via e107::getTemplate('raceevent', null, '<key>').
 * The {RACEEVENT_*} placeholders are resolved by e_shortcode.php.
 */

if (!defined('e107_INIT'))
{
	exit;
}

// Welcome menu template. Only {RACEEVENT_EVENT_NAME} and
// {RACEEVENT_EVENT_DESCRIPTION} exist; another event field needs a new sc_*
// method in e_shortcode.php before it can be placed here.
$RACEEVENT_TEMPLATE['welcome'] = '
<div class="raceevent-welcome">
	<h2 class="raceevent-welcome-title">{RACEEVENT_EVENT_NAME}</h2>
	<div class="raceevent-welcome-description">{RACEEVENT_EVENT_DESCRIPTION}</div>
</div>
';
