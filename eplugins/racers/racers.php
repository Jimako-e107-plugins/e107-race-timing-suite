<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - racers.php
 *
*/

/*
 * Racer list (/racers/list/, e_url 'index'): docs/plugins/racers.md, "Racer list page".
 */

// Unconditional bootstrap: works via the SEF route and on direct file access
// (require_once is a no-op when e107 is already loaded).
require_once(__DIR__ . '/../../class2.php');

if (!defined('ADMIN') || !ADMIN)
{
    e107::redirect();
    exit;
}


require_once(HEADERF);

e107::lan('racers', true, true);     // admin field labels
// _global is loaded by the core on every page (lan_global_list pref).

$sql = e107::getDb();
$tp  = e107::getParser();   // explicit parser - do not rely on the legacy global $tp
$chyba = 0;
$racers = array();          // defined for edit mode (read in the list guard below)
 

 

$allRaceData = e107::getDb()->retrieve("race", "*",   " ORDER BY race_id ", true, 'race_id');
$race_array[0] = LAN_RACERS_ADMIN_024;
foreach ($allRaceData as $pretek)
{
    $race_array[$pretek['race_id']] = $pretek['race_name'];
}

$gender_array = array('M' => LAN_RACERS_ADMIN_015, "F" => LAN_RACERS_ADMIN_016, "S" => LAN_RACERS_GLOBAL_031);



$fields = [

    'racer_number' => [
        'title' => LAN_RACERS_ADMIN_026,
        'title_short' => LAN_RACERS_GLOBAL_018,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerNumber',
            'required' => 1
        ]
    ],

    'racer_firstname' => [
        'title' => LAN_RACERS_ADMIN_009,
        'title_short' => LAN_RACERS_ADMIN_009,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control ',
            'id' => 'racerFirstname',
            'required' => 1
        ]
    ],
    'racer_surname' => [
        'title' => LAN_RACERS_ADMIN_010,
        'title_short' => LAN_RACERS_ADMIN_010,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control ',
            'id' => 'racerSurname',
            'required' => 1
        ]
    ],

    'racer_race_id'        => array(
        'title' => LAN_RACERS_ADMIN_023,
        'title_short' => LAN_RACERS_ADMIN_023,
        'tab' => 0,
        'type' => 'dropdown',
        'filter' => true,
        'data' => 'str',
        'help' => LAN_RACERS_ADMIN_023_HELP,
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerRace',
            'required' => 1,
            'optArray' => $race_array
        ]
    ),

    'racer_birthday' => [
        'title' => LAN_RACERS_ADMIN_011,
        'title_short' => LAN_RACERS_GLOBAL_020,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerBirthday',
            'required' => 1,
            'placeholder' => 'd.m.yyyy',
            'post' => "<small id='birthdayError' class='form-text text-danger' style='display:none;'>" . LAN_RACERS_GLOBAL_027 . "</small>
    <div class='mb-3'>
        <label for='age' class='form-label'>" . LAN_RACERS_GLOBAL_028 . "</label>
        <span id='racerAge' class='form-control-racerAge'></span>
    </div>"

        ]
    ],
    'racer_gender'      => array(
        'title' => LAN_RACERS_ADMIN_014,
        'title_short' => LAN_RACERS_GLOBAL_021,
        'type' => 'radio',
        'data' => 'safestr',
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerGender',
            'required' => 1,
            'pre' => '<div class="d-flex mb-3">',
            'post' => '</div>',
            'optArray' => $gender_array
        ]
    ),
    'racer_category_id'        => array(
        'title' => LAN_RACERS_GLOBAL_026,
        'title_short' => LAN_RACERS_GLOBAL_026,
        'type' => 'dropdown',
        'filter' => true,
        'data' => 'str',

    ),

    'racer_nacionality'           => array(
        'title' => LAN_RACERS_ADMIN_017,
        'title_short' => LAN_RACERS_GLOBAL_019,
        'type' => 'dropdown',
        'data' => 'safestr',
    ),

    'racer_city' => array(
        'title' => LAN_RACERS_GLOBAL_025,
        'title_short' => LAN_RACERS_GLOBAL_025,
        'type' => 'text',
        'data' =>'str',
        'filter' => true,
        'writeParms' => ['size' => 'xlarge'],
    ),

    'racer_local'      => array(
        'title' => LAN_RACERS_ADMIN_018,
        'title_short' => LAN_RACERS_GLOBAL_022,
        'type' => 'checkbox',
        'data' =>'int',
        'filter' => true,
    ),

    'racer_team' => array(
        'title' => LAN_RACERS_ADMIN_019,
        'title_short' => LAN_RACERS_ADMIN_019,
        'type' => 'text',
        'data' =>'str',
        'filter' => true,
        'writeParms' => ['size' => 'xlarge'],
    ),


    'racer_tags' => array(
        'title' => LAN_RACERS_ADMIN_020,
        'title_short' => LAN_RACERS_GLOBAL_023,
        'type' => 'tags',
        'data' =>'str'
    ),

    'racer_active'      => array(
        'title' => LAN_RACERS_GLOBAL_030,
        'title_short' => LAN_RACERS_GLOBAL_024,
        'type' => 'checkbox',
        'data' =>'int',
        'filter' => true,
        'writeParms' => ['default' => 1],
    ),
];

$states = e107::pref('racers', 'states');
$states_array = explode(',', $states);
$fields['racer_nacionality']['writeParms']['optArray'] = $states_array;

$categories = $sql->retrieve('race_category', '*', true, true);
$tmp[0] = LAN_RACERS_ADMIN_024;
foreach ($categories as $cat)
{
    $tmp[$cat['race_category_id']] = $cat['race_category_name'];
}
$fields['racer_category_id']['writeParms']['optArray'] = $tmp;
$fields['racer_category_id']['writeParms']['id'] = "racerCategory";
 
 

if (!isset($_GET['edit']))
{
    // DataTables (search + sort) via racereports' local loader, no CDN. Without
    // racereports the table renders without sort/search.
    if (e107::isInstalled('racereports'))
    {
        require_once(e_PLUGIN . 'racereports/includes/race_report.php');
        race_report_load_datatables();
    }
    // racers' own init.js targets table.racers (racereports' init.js does not).
    e107::js('footer', e_PLUGIN_ABS . "racers/init.js");

    // Racer list table.
    $racers = $sql->retrieve('racer', '*', true, true);

    $tableText = "<div class='table-responsive'><table class='racers table table-condensed'>
<thead class='thead-dark'>
<tr>";
    // Render table headers dynamically
    foreach ($fields as $key => $field)
    {
        $tableText .= "<th>{$field['title_short']}</th>";
    }
    $tableText .= "</tr>
</thead>
<tbody>";
}
if ($racers && !isset($_GET['edit']))
{
    foreach ($racers as $index => $racer)
    {
        $tableText .= "<tr>";

        // Render table row dynamically based on $fields
        foreach ($fields as $key => $field)
        {

            // Compare as STRING so bibs like "0000" are not blanked; checkbox
            // columns stay blank when 0.
            $raw = isset($racer[$key]) ? (string) $racer[$key] : '';
            if ($field['type'] == 'checkbox')
            {
                $value = ($raw !== '' && $raw !== '0') ? $tp->toHTML($raw, false, 'defs') : '';
            }
            else
            {
                $value = ($raw !== '') ? $tp->toHTML($raw, false, 'defs') : '';
            }
            // Track / category ids resolve to admin-entered names: escape them, and
            // guard the lookup so a deleted track/category id does not warn.
            if ($key == 'racer_race_id')
            {
                $name  = isset($race_array[$racer[$key]]) ? $race_array[$racer[$key]] : '';
                $value = $tp->toHTML($name, false, 'defs');
            }
            if ($key == 'racer_category_id')
            {
                $name  = isset($tmp[$racer[$key]]) ? $tmp[$racer[$key]] : '';
                $value = $tp->toHTML($name, false, 'defs');
            }
            if ($key == 'racer_number')
            {
                $edit_url = e107::url('racers', 'registracia');
                $value .= "&nbsp;
    <a href='" . $edit_url . "?edit=" . $racer['racer_id'] . "' class='btn btn-warning btn-sm'>
        <i class='fas fa-edit'></i>  
    </a>
 ";
            }
            $tableText .= "<td>{$value}</td>";
        }

        $tableText .= "</tr>";
    }
}


$tableText .= "</tbody></table></div>";


// Render the table
e107::getRender()->tablerender(LAN_RACERS_ADMIN_033, $tableText);


require_once(FOOTERF);
