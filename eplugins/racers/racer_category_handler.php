<?php
require_once('../../class2.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $race = $_POST['race'];

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
            $text .= "<option value='" . $category['race_category_id'] . "'>" . $category['race_category_name'] . "</option>";
        }
    }

    echo $text;

}
