<?php


if (!defined('e107_INIT'))
{
    require_once('../../class2.php');
    exit;
}

if (!defined('ADMIN') || !ADMIN)
{
    e107::redirect();
    exit;
}

function formatNumber($number)
{
    if ($number === "" || $number === null)
    {
        return null; // Return null or handle as needed
    }
    return str_pad($number, 4, '0', STR_PAD_LEFT);
}

require_once(HEADERF);

e107::lan('racers', '', true);       // front strings (array form, EN + SK)
// The admin field labels reused by this form now have front-scoped duplicates
// (LAN_RACERS_FRONT_010..023), so no cross-scope _admin load is needed here.
// _global (LAN_RACERS_GLOBAL_*) is loaded automatically by the core on every
// page via the lan_global_list pref, so no manual 'global' load is needed here.

// Získajte hodnotu preferencie 'manualinput'
$manualInputEnabled = e107::pref('racers', 'manualinput');

// Ak manualinput nie je povolený (nie je 1), ukončíme kód s oznamom
if ($manualInputEnabled != 1)
{
    require_once(HEADERF);
    $text = LAN_RACERS_FRONT_001;

    $text = e107::getMessage()->addInfo($text)->render();
    e107::getRender()->tablerender('', $text);

    require_once(FOOTERF); // e107 pätička
    exit; // Ukončí ďalší kód
}



$sql = e107::getDb();
$chyba = 0;

if (isset($_POST['submit']))
{
    $firstname = $_POST['racer_firstname'];
    $surname = $_POST['racer_surname'];
    $number = $_POST['racer_number'];


    $birthday = $_POST['racer_birthday'];
    $gender = $_POST['racer_gender'];
    $category_id = $_POST['racer_category_id'];

    if (empty($firstname))
    {
        $chyba = 1;
        echo "<div class='error'>Vyplňte pole meno!</div>";
    }

    if (empty($surname))
    {
        $chyba = 1;
        echo "<div class='error'>Vyplňte pole priezvisko!</div>";
    }

    if (empty($number))
    {
        $chyba = 1;
        echo "<div class='error'>Vyplňte štartovacie číslo!</div>";
    }
    if (empty($birthday))
    {
        $chyba = 1;
        echo "<div class='error'>Vyplňte dátum narodenia</div>";
    }

    if (empty($gender))
    {
        $chyba = 1;
        echo "<div class='error'>Vyplňte typ pretekára!</div>";
    }
}
else {


$racerEditData = [];

if (isset($_GET['edit']) && is_numeric($_GET['edit']))
{
    $racerId = (int)$_GET['edit'];
    $racerEditData = $sql->retrieve('racer', '*', 'racer_id = ' . $racerId);
    $pageCaption = LAN_RACERS_FRONT_008;
}
else
{
    $pageCaption = LAN_RACERS_FRONT_009;
}
}
if (isset($_POST['submit']) && !$chyba)
{
    $sql = e107::getDb();
    $tp = e107::getParser();


    $number = formatNumber($number);

    $data = array(
        'racer_race_id'     => $tp->filter($_POST['racer_race_id'], 'int'),
        'racer_category_id' => $tp->filter($_POST['racer_category_id'], 'int'),
        'racer_number'      => $tp->filter($number, 'str'),
        'racer_surname'     => $tp->filter($_POST['racer_surname'], 'str'),
        'racer_firstname'   => $tp->filter($_POST['racer_firstname'], 'str'),
        'racer_gender'      => $tp->filter($_POST['racer_gender'], 'str'),
        'racer_nacionality' => $tp->filter($_POST['racer_nacionality'], 'str'),
        'racer_birthday'    => $tp->filter($_POST['racer_birthday'], 'str'),
        'racer_active'      => $tp->filter($_POST['racer_active'], 'int'),
        'racer_local'       => $tp->filter($_POST['racer_local'], 'int'),
        'racer_tags'        => $tp->filter($_POST['racer_tags'], 'str'),
        'racer_team'        => $tp->filter($_POST['racer_team'], 'str'),
        'racer_extid'       => $tp->filter($_POST['racer_extid'], 'int'),
        'racer_city'        => $tp->filter($_POST['racer_city'], 'str'),
    );

 
    if (!empty($_POST['racer_id']))
    {

        // Úprava existujúceho záznamu
        $racerId = (int)$_POST['racer_id'];


        if ($racerId > 0)
        {
            $update['data'] =  $data;
            $update['WHERE'] = 'racer_id = ' . $racerId;
            $result = $sql->update('racer', $update );
 
            if ($result ==  0)
            {
                e107::getMessage()->addSuccess('K žiadnej zmene nedošlo.');

                //$url = e107::url('racers', 'index');

                //e107::redirect($url);
            }
            elseif ($result>0)
            {

                e107::getMessage()->addInfo('Záznam bol úspešne upravený.');

              //  $url = e107::url('racers', 'index');
 
              //  e107::redirect($url);
            }
            else
            {
                e107::getMessage()->addError(LAN_RACERS_FRONT_007 . $sql->getLastErrorText());
            }

            echo  e107::getRender()->tablerender('', e107::getMessage()->render());
        }
    }
    else
    {

        // Check if racer_number already exists
        $racer_number = $data['racer_number'];
        $sql->select('racer', 'racer_id', "racer_number = '" . $tp->filter($racer_number) . "'");

        if ($sql->fetch())
        {
            // If a record with the same racer_number exists, display a warning
            e107::getMessage()->addError(LAN_RACERS_FRONT_005);
        }
        else
        {

            // Pridanie nového záznamu
            $sql->insert('racer', $data);
            e107::getMessage()->addSuccess('Záznam bol úspešne pridaný.');
        }
    }

    e107::getRender()->tablerender('', e107::getMessage()->render());
}
else {


$form = e107::getForm();
$tp = e107::getParser();

$allRaceData = e107::getDb()->retrieve("race", "*",   " ORDER BY race_id ", true, 'race_id');
$race_array[0] = LAN_RACERS_FRONT_022;
foreach ($allRaceData as $pretek)
{
    $race_array[$pretek['race_id']] = $pretek['race_name'];
}

$gender_array = array('M' => LAN_RACERS_FRONT_014, "F" => LAN_RACERS_FRONT_015, "S" => LAN_RACERS_GLOBAL_031);



$fields = [
    'racer_firstname' => [
        'title' => LAN_RACERS_FRONT_010,
        'title_short' => LAN_RACERS_FRONT_010,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control ',
            'id' => 'racerFirstname',
            'required' => 1
        ]
    ],
    'racer_surname' => [
        'title' => LAN_RACERS_FRONT_011,
        'title_short' => LAN_RACERS_FRONT_011,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control ',
            'id' => 'racerSurname',
            'required' => 1
        ]
    ],

    'racer_race_id'        => array(
        'title' => LAN_RACERS_FRONT_020,
        'title_short' => LAN_RACERS_FRONT_020,
        'tab' => 0,
        'type' => 'dropdown',
        'filter' => true,
        'data' => 'str',
        'help' => LAN_RACERS_FRONT_021,
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerRace',
            'required' => 1,
            'optArray' => $race_array
        ]
    ),

    'racer_number' => [
        'title' => LAN_RACERS_FRONT_023,
        'title_short' => LAN_RACERS_GLOBAL_018,
        'type' => 'text',
        'writeParms' => [
            'class' => 'form-control',
            'id' => 'racerNumber',
            'required' => 1
        ]
    ],
    'racer_birthday' => [
        'title' => LAN_RACERS_FRONT_012,
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
        'title' => LAN_RACERS_FRONT_013,
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
        'title' => LAN_RACERS_FRONT_016,
        'title_short' => LAN_RACERS_GLOBAL_019,
        'type' => 'dropdown',
        'data' => 'safestr',
    ),

    'racer_city' => array(
        'title' => LAN_RACERS_GLOBAL_025,
        'title_short' => LAN_RACERS_GLOBAL_025,
        'type' => 'text',
        'date ' => 'str',
        'filter' => true,
        'writeParms' => ['size' => 'xlarge'],
    ),

    'racer_local'      => array(
        'title' => LAN_RACERS_FRONT_017,
        'title_short' => LAN_RACERS_GLOBAL_022,
        'type' => 'checkbox',
        'date ' => 'int',
        'filter' => true,
    ),

    'racer_team' => array(
        'title' => LAN_RACERS_FRONT_018,
        'title_short' => LAN_RACERS_FRONT_018,
        'type' => 'text',
        'date ' => 'str',
        'filter' => true,
        'writeParms' => ['size' => 'xlarge'],
    ),


    'racer_tags' => array(
        'title' => LAN_RACERS_FRONT_019,
        'title_short' => LAN_RACERS_GLOBAL_023,
        'type' => 'tags',
        'date ' => 'str'
    ),

    'racer_active'      => array(
        'title' => LAN_RACERS_GLOBAL_030,
        'title_short' => LAN_RACERS_GLOBAL_024,
        'type' => 'checkbox',
        'date ' => 'int',
        'filter' => true,
        'writeParms' => ['default' => 1],
    ),
];

$states = e107::pref('racers', 'states');

$states_array = explode(',', $states);
$states_assoc_array = array_combine($states_array, $states_array);
$fields['racer_nacionality']['writeParms']['optArray'] = $states_assoc_array;

$categories = $sql->retrieve('race_category', '*', true, true);
$tmp[0] = LAN_RACERS_FRONT_022;
foreach ($categories as $cat)
{
    $tmp[$cat['race_category_id']] = $cat['race_category_name'];
}
$fields['racer_category_id']['writeParms']['optArray'] = $tmp;
$fields['racer_category_id']['writeParms']['id'] = "racerCategory";

$formText  = $form->open('racerForm', 'post', e_SELF, ['class' => 'container mt-4']);

$formText  .= "<div class='row'>";

if (!isset($_GET['edit']) && !$chyba) unset($_POST);

foreach ($fields as $key => $fld)
{
    // $value = isset($_POST[$key]) ? $tp->filter($_POST[$key], 'str') : '';
    $value = $racerEditData ? $tp->toForm($racerEditData[$key]) : (isset($_POST[$key]) ? $tp->filter($_POST[$key], 'str') : '');
    $formText .= "<div class='mb-3 col-md-6'>
                <label for='{$fld['writeParms']['id']}' class='form-label'>{$fld['title']}</label>
                " . $form->renderElement($key, $value, $fld) . "
              </div>";
}
$formText  .= "</div>";


$ageCalculationMethod = e107::pref('racers', 'startforage');
if ($ageCalculationMethod) $calvalue = "today";
else $calvalue = "startOfYear";

$formText .= $form->hidden('folder', e_PLUGIN_DIR);
$formText .= $form->hidden('ageCalculationMethod', $calvalue, ['id' => 'ageCalculationMethod']);

if ($racerEditData)
{
    $formText .= $form->hidden('racer_id', $racerEditData['racer_id']);
}

$formText .= $form->button('submit', LAN_RACERS_FRONT_003);
$formText .= $form->close();

e107::js('footer', e_PLUGIN . "racers/racers.js", 'jquery', 5);
}


e107::getRender()->tablerender($pageCaption, $formText);



require_once(FOOTERF);
