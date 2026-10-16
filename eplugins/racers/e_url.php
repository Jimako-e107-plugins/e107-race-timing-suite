<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - e_url.php
 *
*/

/*
 * SEF routes (URLs: docs/plugins/racers.md, "Front-end"). Route keys are
 * plugin-scoped (e107::url('racers', 'startlist', ...)).
 */

class racers_url // plugin-folder + '_url'
{
	function config() 
	{
		$config = array();

		$config['registracia'] = array(
			'alias'         => 'racers',
			'regex'			=> '^{alias}\/registracia\/(.*)$', 	
			'sef'			=> '{alias}/registracia/',
			'redirect'		=> '{e_PLUGIN}racers/registracia.php$1',

		);


		// Public start list by track + category ('all' = every track / category).
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
