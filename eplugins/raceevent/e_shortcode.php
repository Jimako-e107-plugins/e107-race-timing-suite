<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - e_shortcode.php
 *
*/

/*
 * Site-wide shortcode batch: e107 registers it via the core e_shortcode_list pref
 * when the plugin is installed/scanned, so the codes resolve in any
 * parseTemplate(..., true) call. Values come from e107::getPlugConfig('raceevent');
 * event_name is escaped with toAttribute(), event_description is rendered with
 * toHTML(). The shortcodes are listed in docs/reference/shortcodes.md.
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

	function sc_raceevent_event_name($parm = null)
	{
		$value = e107::getPlugConfig('raceevent')->get('event_name');

		return e107::getParser()->toAttribute($value);
	}

	function sc_raceevent_event_description($parm = null)
	{
		$value = e107::getPlugConfig('raceevent')->get('event_description');

		return e107::getParser()->toHTML($value, true, 'BODY');
	}
}
