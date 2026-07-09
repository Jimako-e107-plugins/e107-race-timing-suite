<?php


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
