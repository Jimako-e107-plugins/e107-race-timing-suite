<?php
/*
 * e107 website system
 *
 * raceevent base plugin - simple-mode URL (SEF) configuration.
 *
 * raceevent previously declared NO e_url routes. It now owns the event
 * OVERVIEW front page (the cross-suite link directory) under the original
 * 'preteky' alias:
 *
 *   index : event overview / link directory (raceevent/page_overview.php)
 *
 * The 'preteky' SEF alias used to belong to timetracker's own 'index' route,
 * which is now COMMENTED OUT in timetracker/e_url.php (intentional trace of the
 * strangler move). raceevent takes the alias here, so there is no collision.
 *
 * The route key is PLUGIN-SCOPED (e107::url('raceevent', 'index', ...)); the
 * 'preteky' SEF alias is the global route segment.
 */

class raceevent_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		// Event overview / link directory. Re-homes timetracker's old
		// 'index'/'preteky' front page into raceevent (the base plugin that owns
		// the event), pointed at the fresh page_overview.php.
		$config['index'] = array(
			'alias'    => 'preteky',                          // global SEF segment (the original alias).
			'regex'    => '^{alias}\/$',                      // matched against the url; redirects on match.
			'sef'      => '{alias}/',                         // used by e107::url() to build the url.
			'redirect' => '{e_PLUGIN}raceevent/page_overview.php', // file loaded when the regex matches.
		);

		return $config;
	}
}
