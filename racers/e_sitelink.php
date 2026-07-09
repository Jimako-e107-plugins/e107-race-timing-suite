<?php
/*
 * e107 website system
 *
 * racers - sitelinks for the public start list (štartovacia listina).
 *
 * Moved here from timetracker (racers_list) and racetrack
 * (race_points_list_by_race), which both built dead e107::url('timetracker',
 * 'racers', ...) links. The start list belongs to racers; the start itself
 * (race_start_list) stays in timetracker.
 */

if (!defined('e107_INIT'))
{
    exit;
}

class racers_sitelink // include plugin-folder in the name.
{
    function config()
    {
        $links = array();

        $links[] = array(
            'name'            => "Štartovacie listiny",
            'function'        => "racers_start_list",
            'description'     => ""
        );

        return $links;
    }

    function racers_start_list($type = null)
    {
        $sublinks = array();

        // Per track (every category).
        $pretek_data = e107::getDb()->retrieve("race", "*", " ORDER BY race_name DESC ", true);
        foreach ($pretek_data as $pretek)
        {
            $all = array('race_sef' => $pretek['race_sef'], 'race_category_sef' => 'all');
            $url = e107::url('racers', 'startlist', $all);

            $sublinks[] = array(
                'link_name'            => $pretek['race_name'],
                'link_url'            => $url,
                'link_description'    => '',
                'link_button'        => '',
                'link_category'        => '',
                'link_order'        => '',
                'link_parent'        => '',
                'link_open'            => '',
                'link_class'        => 0
            );
        }

        // Per track + category.
        $query = "SELECT * FROM  #race AS r,  #race_category AS rc
                WHERE FIND_IN_SET(r.race_id, rc.race_category_race) ORDER BY race_id, race_category_sef DESC ";
        $category_data = e107::getDb()->retrieve($query, true);

        foreach ($category_data as $point)
        {
            $url = e107::url('racers', 'startlist', $point);

            $sublinks[] = array(
                'link_name'           => $point['race_name'] . " - " .  $point['race_category_name'],
                'link_url'            => $url,
                'link_description'    =>  '',
                'link_button'        => '',
                'link_category'        => '',
                'link_order'        => NULL,
                'link_parent'        => '',
                'link_open'          => '_blank',
                'link_class'        => 0
            );
        }
 
        return $sublinks;
    }
}
