<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - e_url.php
 *
*/

/*
 * SEF routes - listed in docs/reference/routes.md. Route keys are plugin-scoped
 * (e107::url('raceevent', 'index')).
 */

class raceevent_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		// Event overview / link directory.
		$config['index'] = array(
			'alias'    => 'preteky',                          // global SEF segment (the original alias).
			'regex'    => '^{alias}\/$',                      // matched against the url; redirects on match.
			'sef'      => '{alias}/',                         // used by e107::url() to build the url.
			'redirect' => '{e_PLUGIN}raceevent/page_overview.php', // file loaded when the regex matches.
		);

		return $config;
	}
}
