<?php
/*
 * e107 website system
 *
 * racetiming plugin - simple-mode URL (SEF) configuration.
 *
 * racetiming's FIRST front route. Serves the manual passing-entry "keypad app"
 * (vstup.php) relocated here from timetracker together with the
 * {TIMETRACKER_VSTUP} shortcode (see e_shortcode.php) and this `kontrola` route.
 *
 * The `kontrola` key is MOVED verbatim from timetracker/e_url.php (and from
 * racetrack/e_url.php, which held a duplicate of it). The PUBLIC SEF URL is
 * byte-identical - /kontrola/{race_point_code}/{race_point_password}/ - because
 * the alias, regex and sef are unchanged; ONLY the owning plugin and the
 * redirect file-path moved. The checkpoint links are already distributed in the
 * field, so this must not change. GET params k and p are preserved.
 *
 * racetiming OWNS race_time and this feature writes raw passings into race_time,
 * so the route belongs here.
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
