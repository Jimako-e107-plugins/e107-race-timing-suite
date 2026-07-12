<?php


// SEF entry point for /racers/list/ (e_url 'index' route -> racers.php). Bootstrap
// e107 unconditionally so direct file access works too; require_once is a no-op when
// e107 is already loaded via the SEF route, so /racers/list/ resolution is unchanged.
require_once(__DIR__ . '/../../class2.php');

if (!defined('ADMIN') || !ADMIN)
{
    e107::redirect();
    exit;
}


require_once(HEADERF);

e107::lan('racers', true, true);     // admin field labels (array form, EN + SK)
// _global (LAN_RACERS_GLOBAL_*) is loaded automatically by the core on every
// page via the lan_global_list pref, so no manual 'global' load is needed here.

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
    // DataTables (basic search + sort) via the shared LOCAL loader owned by
    // racereports - no CDN. SearchBuilder is deliberately NOT adopted. Guarded on
    // isInstalled so the table still renders (just without sort/search) when
    // racereports is absent.
    if (e107::isInstalled('racereports'))
    {
        require_once(e_PLUGIN . 'racereports/includes/race_report.php');
        race_report_load_datatables();
    }
    // racers-owned init: targets `table.racers` with a SK language block (the shared
    // init.js targets the racereports report tables, not this one).
    e107::js('footer', e_PLUGIN_ABS . "racers/init.js");

    //zoznam
    // Bootstrap 5 table for listing existing racers
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

            // Render every field verbatim as a STRING so special bib values like
            // "0000" survive (the old `!= 0` loose compare blanked them - DATA LOSS).
            // Genuine int checkbox columns keep their "blank when 0" look, handled
            // per-field so the list display is otherwise unchanged.
            $raw = isset($racer[$key]) ? (string) $racer[$key] : '';
            if ($field['type'] == 'checkbox')
            {
                $value = ($raw !== '' && $raw !== '0') ? $tp->toHTML($raw, false, 'defs') : '';
            }
            else
            {
                $value = ($raw !== '') ? $tp->toHTML($raw, false, 'defs') : '';
            }
            // Foreign-key ids resolve to admin-entered names - escape them (they
            // previously reached the <td> RAW = stored XSS) and guard the lookup so a
            // deleted race/category id does not PHP-8-warn.
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
