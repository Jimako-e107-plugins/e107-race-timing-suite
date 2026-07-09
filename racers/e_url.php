<?php


class racers_url // plugin-folder + '_url'
{
	function config() 
	{
		$config = array();

		// $config['index'] = array(
		// 	'alias'         => 'preteky',                            // default alias '_blank'. {alias} is substituted with this value below. Allows for customization within the admin area.
		// 	'regex'			=> '^{alias}\/$', 						// matched against url, and if true, redirected to 'redirect' below.
		// 	'sef'			=> '{alias}/', 							// used by e107::url(); to create a url from the db table.
		// 	'redirect'		=> '{e_PLUGIN}timetracker/index.php', 		// file-path of what to load when the regex returns true.

		// );

		// $config['racers'] = array(
		// 	'alias'         => 'racers',                         
		// 	'regex'			=> '^{alias}\/(.*)\/(.*)\/$',
		// 	'sef'			=> '{alias}/{race_sef}/{race_category_sef}/', 	
		// 	'redirect'		=> '{e_PLUGIN}racers/timetracker_racers.php?r=$1&c=$2', 
		// );

		// $config['number'] = array(
		// 	'alias'         => 'racers',
		// 	'regex'			=> '^{alias}\/(.*)\/(.*)\/(.*)$',
		// 	'sef'			=> '{alias}/number/{racer_starter_number}/',
		// 	'redirect'		=> '{e_PLUGIN}timetracker/timetracker_racers.php?r=number&c=$2',
		// );

		$config['registracia'] = array(
			'alias'         => 'racers',
			'regex'			=> '^{alias}\/registracia\/(.*)$', 	
			'sef'			=> '{alias}/registracia/',
			'redirect'		=> '{e_PLUGIN}racers/registracia.php$1',

		);


		// Public start list (štartovacia listina, roaster) by track + category. Kept after
		// the more specific 'registracia' and 'index' routes so they match first.
		$config['startlist'] = array(
			'alias'         => 'startlist',
			'regex'			=> '^{alias}\/(.*)\/(.*)\/$',
			'sef'			=> '{alias}/{race_sef}/{race_category_sef}/',
			'redirect'		=> '{e_PLUGIN}racers/startlist.php?r=$1&c=$2',

		);

		$config['index'] = array(
			'alias'         => 'racers',
			'regex'			=> '^{alias}\/list\/(.*)$',
			'sef'			=> '{alias}/list/',
			'redirect'		=> '{e_PLUGIN}racers/racers.php$1',

		);

        return $config;

    }
}
