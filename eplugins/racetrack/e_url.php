<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - e_url.php
 *
*/

/*
 * SEF routes - listed in docs/reference/routes.md. Route keys are plugin-scoped
 * (e107::url('racetrack', 'race'|'archiv')).
 */

class racetrack_url  
{
	function config() 
	{
		$config = array();
 

        $config['race'] = array(
            'alias'         => 'pretek',                             
            'regex'            => '^{alias}\/(.*)\/(.*)$',                          
            'sef'            => '{alias}/{race_id}/{race_sef}',                              
            'redirect'        => '{e_PLUGIN}racetrack/pretek.php?race_id=$1&race_sef=$2',          
        );

 
        $config['archiv'] = array(
            'alias'         => 'archiv',
            'regex'         => '^{alias}\/(.*)$',
            'sef'           => '{alias}/{race_archive_sef}',
            'redirect'      => '{e_PLUGIN}racetrack/page_archive.php?a=$1',
        );

 
        return $config;

    }
}
