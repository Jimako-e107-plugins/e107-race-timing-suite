<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - race/track landing page.
 *
 * MOVED here from timetracker/pretek.php: this is a racetrack ENTITY page (a
 * race/track landing that lists the track's categories), so it belongs with the
 * racetrack plugin. Reached via e107::url('racetrack', 'race', ...) -> the
 * 'race' (pretek) route in racetrack/e_url.php.
 *
 * The per-category links target the START report (start-as-point) =
 * racereports 'start' (report_start.php). report_start may not be built yet;
 * the link is correct and goes live when report_start ships (same alive-check
 * philosophy as race_points_menu.php).
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
    // SECURITY: $_GET values are user input. race_id is int-cast for the
    // lookup; race_sef is a string passed through $tp->toDB() before it touches
    // SQL (formerly both went straight into the query string).
    $race_id  = (int) $_GET['race_id'];
    $race_sef = isset($_GET['race_sef']) ? $tp->toDB($_GET['race_sef']) : '';

    $query = "SELECT * FROM #race WHERE race_id = " . $race_id . " AND race_sef = '" . $race_sef . "'";

    /* najdi pretek podla sef */
    $pretek_data = e107::getDb()->retrieve($query);

    // SECURITY: escape all DB-sourced output via $tp->toHTML().
    echo "<h1>" . $tp->toHTML($pretek_data['race_name'], false, 'TITLE') . "</h1>";

    echo $tp->toHTML(LAN_RT_PRETEK_CATEGORIES, false, 'TITLE');

    $query = "SELECT * FROM #race_category WHERE FIND_IN_SET(" . $race_id . ", race_category_race)";
    $category_data = e107::getDb()->retrieve($query, true);

    echo "<ul>";
    foreach ($category_data as $category)
    {
        // Link to the START report (start-as-point) = racereports 'start'
        // (report_start). That route consumes race_sef + race_category_sef, so
        // build exactly those tokens (not array_merge of the whole rows).
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
