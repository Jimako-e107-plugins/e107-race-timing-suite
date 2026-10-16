<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - e_url.php
 *
*/

/*
 * SEF routes - listed in docs/reference/routes.md. 'kontrola' is the checkpoint
 * keypad URL (vstup.php). Those links are handed out to marshals before the race,
 * so alias, regex and sef must not change. The redirect passes k = checkpoint code
 * and p = checkpoint password.
 */

class racetiming_url // plugin-folder + '_url'
{
	function config()
	{
		$config = array();

		$config['kontrola'] = array(
			'alias'         => 'kontrola',                            // default alias '_blank'. {alias} is substituted with this value below. Allows for customization within the admin area.
			'regex'			=> '^{alias}\/(.*)\/(.*)\/$', 						// matched against url, and if true, redirected to 'redirect' below.
			'sef'			=> '{alias}/{race_point_code}/{race_point_password}/', 							// used by e107::url(); to create a url from the db table.
			'redirect'		=> '{e_PLUGIN}racetiming/vstup.php?k=$1&p=$2', 		// file-path of what to load when the regex returns true.

		);

		return $config;

	}
}
