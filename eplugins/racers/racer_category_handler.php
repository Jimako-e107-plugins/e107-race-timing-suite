<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - racer_category_handler.php
 *
*/

/*
 * AJAX endpoint for the on-site registration form (racers.js): returns the
 * <option> list of categories matching the posted birthday, gender and track.
 * Matching logic: includes/registration.php (escapes the values for SQL).
 *
 * Admin only, like the on-site form (registracia.php) that calls it. Read-only
 * (SELECT), so no CSRF token is needed.
 */

require_once('../../class2.php');

if (!defined('ADMIN') || !ADMIN)
{
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $tp = e107::getParser();
    $text = '';

    $birthday = (string) varset($_POST['birthday'], '');
    $gender = (string) varset($_POST['gender'], '');
    $race = (string) varset($_POST['race'], '');

    if(empty($gender) OR empty($birthday) or empty($race) ) {
        $text .= "<option value=''>Vyberte kategóriu</option>";
    }

    require_once(e_PLUGIN.'racers/includes/registration.php');
    $categories = plugin_racers_registration::getCategories($birthday, $gender, $race);

    if(empty($categories)) {
        $text .= "<option value='x'>Zadám neskôr</option>";
    }
    if ($categories)
    {
        foreach ($categories as $category)
        {
            $text .= "<option value='" . (int) $category['race_category_id'] . "'>" . $tp->toHTML($category['race_category_name'], false, 'TITLE') . "</option>";
        }
    }

    echo $text;

}
