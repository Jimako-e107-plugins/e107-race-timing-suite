<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - pretek.php
 *
*/

/*
 * Public track page (docs/plugins/racetrack.md, "Track page"), reached via
 * the 'race' route in e_url.php.
 */
if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}


require_once(HEADERF);                     // render the header (everything before the main content area)

$tp = e107::getParser();

e107::lan('racetrack', '', true);          // array-style front LAN (defines LAN_RT_PRETEK_CATEGORIES)

if (isset($_GET['race_id']))
{
    // SECURITY: race_id is int-cast; race_sef goes through $tp->toDB().
    $race_id  = (int) $_GET['race_id'];
    $race_sef = isset($_GET['race_sef']) ? $tp->toDB($_GET['race_sef']) : '';

    $query = "SELECT * FROM #race WHERE race_id = " . $race_id . " AND race_sef = '" . $race_sef . "'";

    /* Find the track by id + sef. */
    $pretek_data = e107::getDb()->retrieve($query);

    // SECURITY: escape all DB-sourced output via $tp->toHTML().
    echo "<h1>" . $tp->toHTML($pretek_data['race_name'], false, 'TITLE') . "</h1>";

    echo $tp->toHTML(LAN_RT_PRETEK_CATEGORIES, false, 'TITLE');

    $query = "SELECT * FROM #race_category WHERE FIND_IN_SET(" . $race_id . ", race_category_race)";
    $category_data = e107::getDb()->retrieve($query, true);

    echo "<ul>";
    foreach ($category_data as $category)
    {
        // racereports 'start' route: pass exactly race_sef + race_category_sef.
        $url = e107::url('racereports', 'start', array(
            'race_sef'          => $pretek_data['race_sef'],
            'race_category_sef' => $category['race_category_sef'],
        ));

        echo "<li>";
        echo "<a href='" . $url . "' target='_blank'>"
            . $tp->toHTML($pretek_data['race_name'], false, 'TITLE')
            . " - "
            . $tp->toHTML($category['race_category_name'], false, 'TITLE')
            . "</a>";
        echo "</li>";
    }
    echo "</ul>";
}


require_once(FOOTERF);					// render the footer (everything after the main content area)
