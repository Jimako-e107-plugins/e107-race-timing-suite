<?php
/*
 * e107 website system
 *
 * raceevent base plugin - front templates.
 *
 * Loaded via e107::getTemplate('raceevent', null, '<key>'), which reads this
 * file and the $RACEEVENT_TEMPLATE array. Templates use {SHORTCODE} placeholders
 * resolved by raceevent/e_shortcode.php (class raceevent_shortcodes).
 */

if (!defined('e107_INIT'))
{
	exit;
}

// Welcome menu template. Kept deliberately simple/extensible: add more event
// fields later by adding their shortcode placeholders here (e.g.
// {RACEEVENT_EVENT_DATE}, {RACEEVENT_EVENT_CITY}, ...).
$RACEEVENT_TEMPLATE['welcome'] = '
<div class="raceevent-welcome">
	<h2 class="raceevent-welcome-title">{RACEEVENT_EVENT_NAME}</h2>
	<div class="raceevent-welcome-description">{RACEEVENT_EVENT_DESCRIPTION}</div>
</div>
';
