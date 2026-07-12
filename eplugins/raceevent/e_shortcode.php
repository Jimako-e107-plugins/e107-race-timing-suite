<?php
/*
 * e107 website system
 *
 * raceevent base plugin - site-wide shortcode batch.
 *
 * Equivalent to multiple .sc files: e107 auto-registers this batch site-wide
 * (via the e_shortcode_list pref built when the plugin is installed/scanned),
 * so the codes below resolve in any parseTemplate(..., true) call - the same
 * mechanism timetracker uses for its {TIMETRACKER_*} codes.
 *
 * Every value is read from the RACEEVENT plugin prefs
 * (e107::getPlugConfig('raceevent')) and escaped on output:
 *   - event_name        -> toAttribute() (htmlspecialchars, safe for HTML text
 *                          and attribute context);
 *   - event_description -> toHTML() (renders the textarea/HTML body).
 */

if (!defined('e107_INIT'))
{
	exit;
}


class raceevent_shortcodes extends e_shortcode
{
	// When true, existing core/plugin shortcodes matching the methods below
	// would be overridden. We only add new {RACEEVENT_*} codes, so keep false.
	public $override = false;

	/* {RACEEVENT_EVENT_NAME} - event name from raceevent prefs, escaped for HTML/attribute context. */
	function sc_raceevent_event_name($parm = null)
	{
		$value = e107::getPlugConfig('raceevent')->get('event_name');

		return e107::getParser()->toAttribute($value);
	}

	/* {RACEEVENT_EVENT_DESCRIPTION} - event description (textarea) from raceevent prefs, rendered as HTML. */
	function sc_raceevent_event_description($parm = null)
	{
		$value = e107::getPlugConfig('raceevent')->get('event_description');

		return e107::getParser()->toHTML($value, true, 'BODY');
	}
}
