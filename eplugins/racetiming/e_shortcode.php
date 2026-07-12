<?php
/*
* Copyright (c) e107 Inc e107.org, Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
*
* racetiming shortcode batch class - shortcodes available site-wide.
*
* Hosts the manual passing-entry "keypad app" link list. The TAG NAME
* {TIMETRACKER_VSTUP} is INTENTIONALLY KEPT (not renamed to RACETIMING_*) for
* backward compatibility: existing and archived event-pages contain the literal
* {TIMETRACKER_VSTUP} in their body, so renaming would break them. Only the
* plugin that REGISTERS the tag moved (timetracker -> racetiming); the rendered
* checkpoint links resolve through racetiming's `kontrola` route, producing the
* byte-identical /kontrola/CODE/PASS/ public URL.
*/

if(!defined('e107_INIT'))
{
	exit;
}



class racetiming_shortcodes extends e_shortcode
{
	public $override = false; // when set to true, existing core/plugin shortcodes matching methods below will be overridden.

	// /* {TIMETRACKER_VSTUP} */
	function sc_timetracker_vstup($parm = null)  // Naming:  "sc_" + [plugin-directory] + '_uniquename'
	{
        $query = "SELECT * FROM  #race AS r,  #race_point AS rc
        WHERE FIND_IN_SET(r.race_id, rc.race_point_race) ORDER BY race_point_order";

        $start = array();
        $finish = array();
        $tmp = array();

        $point_data = e107::getDb()->retrieve($query, true);

        foreach ($point_data as $point)
        {
/*
            if ($point['race_point_code'] == "start"
            )
            {
                continue;
            }

            if ($point['race_point_code'] == "finish"
            )
            {
                continue;
            }
*/
            $point['race_point_sef'] = $point['race_point_code'];
            $url = e107::url('racetiming', 'kontrola', $point);


            //$row = (array) $cat;
            //$row['id'] = $cat->getId();
            $sublinks[] = array(
                'link_name'           => $point['race_name'] . " - " .  $point['race_point_name'],
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

        $text = "<ul>";
        foreach($sublinks AS $link) {


            $url = $link['url'];

            $text .= "<li>";
            $text .= "<a class='pb-3' href='{$link['link_url']}' target='_blank'>" . $link['link_name']  . "</a>";
            $text .= "</li>";

        }

        $text .= "</ul>";

        return $text;

	}

}
