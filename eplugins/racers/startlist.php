<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - startlist.php
 *
*/

/*
 * Public start list (docs/plugins/racers.md, "Start list page"). The start time
 * is read from the racetiming engine (race_clock, point 'start'), guarded by
 * isInstalled('racetiming'), so racetiming is not a hard dependency.
 */
if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}

// Register CSS BEFORE HEADERF: HEADERF flushes registered CSS into <head>, later
// e107::css() calls never reach it. Same compact-row rule as the racereports pages.
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);

$tp  = e107::getParser();
$sql = e107::getDb();

e107::lan('racers', '', true);     // racers front strings

$paramRaceSef  = $tp->filter(varset($_GET['r'], ''));
$paramCategSef = $tp->filter(varset($_GET['c'], ''));

// Name via getRacerName() -> racers_name::build().
$rr = e107::getSingleton('plugin_racers_racers', e_PLUGIN . 'racers/includes/racers.php');

// Start-time source (optional racetiming). One build() loads every crossing once.
$rtInstalled = e107::isInstalled('racetiming');
$clock = null;
if ($rtInstalled)
{
    require_once(e_PLUGIN . 'racetiming/includes/race_clock.php');
    $clock = (new race_clock())->build();
}

$allRaceData  = $sql->retrieve("race", "*", " ORDER BY race_id ", true, 'race_id');
$allCategData = $sql->retrieve("race_category", "*", true, true, 'race_category_id');

// Restrict to the selected track (all = every track).
$races = $allRaceData;
if ($paramRaceSef && $paramRaceSef != "all")
{
    $races = array();
    foreach ($allRaceData as $rid => $race)
    {
        if (isset($race['race_sef']) && $race['race_sef'] === $paramRaceSef)
        {
            $races[$rid] = $race;
            break;
        }
    }
}

// Restrict to the selected category (all = every category).
$categories = $allCategData;
if ($paramCategSef && $paramCategSef != "all")
{
    $categories = array();
    foreach ($allCategData as $cid => $category)
    {
        if (isset($category['race_category_sef']) && $category['race_category_sef'] === $paramCategSef)
        {
            $categories[$cid] = $category;
            break;
        }
    }
}

// Per-track navigation, via the racers start-list route.
$navCategSef = ($paramCategSef !== '') ? $paramCategSef : 'all';
$nav  = "<a target='_blank' class='btn btn-default' href='" . e107::url('racers', 'startlist', array('race_sef' => 'all', 'race_category_sef' => 'all')) . "'>" . LAN_RACERS_STARTLIST_ALL . "</a>";
foreach ($allRaceData as $race)
{
    // Track tabs keep the current category; the ALL tab resets both to 'all'.
    $url  = e107::url('racers', 'startlist', array('race_sef' => $race['race_sef'], 'race_category_sef' => $navCategSef));
    $nav .= "<a target='_blank' class='btn btn-default' href='" . $url . "'>" . $tp->toHTML($race['race_name'], false, 'TITLE') . "</a>";
}
$nav .= "<hr>";

// Build int-cast id lists for the IN() clauses.
$raceIds = array();
foreach ($races as $race)
{
    $raceIds[] = (int) $race['race_id'];
}
$categIds = array();
foreach ($categories as $category)
{
    $categIds[] = (int) $category['race_category_id'];
}

$pointTable = '';
if (!empty($raceIds) && !empty($categIds))
{
    $where = "racer_number != '' AND racer_race_id IN (" . implode(',', $raceIds) . ") AND racer_category_id IN (" . implode(',', $categIds) . ")";
    $racersData = $sql->retrieve("racer", "*", $where, true, 'racer_number');

    $line = 0;
    foreach ($racersData as $racer)
    {
        $line++;
        $number    = $racer['racer_number']; // string - never (int)
        $racerName = $rr->getRacerName($racer);

        // Start time as wall-clock time of day (formatTimeOfDay, 3 decimals); empty
        // when there is no start crossing or racetiming is not installed.
        $racerTime = "";
        if ($rtInstalled)
        {
            $epoch = $clock->timeOfDay($number, 'start');
            if ($epoch !== null)
            {
                $racerTime = race_clock::formatTimeOfDay($epoch, 3);
            }
        }

        $catId    = (int) $racer['racer_category_id'];
        $color    = isset($allCategData[$catId]['race_category_color']) ? $allCategData[$catId]['race_category_color'] : '';
        $raceId   = (int) $racer['racer_race_id'];
        $raceName = isset($allRaceData[$raceId]['race_name']) ? $allRaceData[$raceId]['race_name'] : '';

        if ($racer['racer_active'])
        {
            $pointTable .= "<tr style='background-color: " . $tp->toAttribute($color) . ";'>";
        }
        else
        {
            $pointTable .= "<tr>";
        }
        $pointTable .= "<td>" . $line . "</td>";
        $pointTable .= "<td>" . $tp->toHTML($number, false, 'TITLE') . "</td>";
        $pointTable .= "<td>" . $racerName . "</td>";
        $pointTable .= "<td>" . $tp->toHTML($racer['racer_gender'], false, 'TITLE') . "</td>";
        $pointTable .= "<td>" . $tp->toHTML($racer['racer_nacionality'], false, 'TITLE') . "</td>";
        $pointTable .= "<td>" . $tp->toHTML($raceName, false, 'TITLE') . "</td>";
        $pointTable .= "<td class='text-center'>" . $tp->toHTML($racerTime, false, 'TITLE') . "</td>";
        $pointTable .= "</tr>";
    }
}

$tableStart  = "<div class='table-responsive'><table class='table table-striped table-bordered'>";
$tableStart .= "<thead><tr>"
    . "<th>#</th>"
    . "<th>" . LAN_RACERS_STARTLIST_NUMBER . "</th>"
    . "<th>" . LAN_RACERS_STARTLIST_NAME . "</th>"
    . "<th>" . LAN_RACERS_STARTLIST_TYPE . "</th>"
    . "<th>" . LAN_RACERS_STARTLIST_NATION . "</th>"
    . "<th>" . LAN_RACERS_STARTLIST_TRACK . "</th>"
    . "<th>" . LAN_RACERS_STARTLIST_STARTTIME . "</th>"
    . "</tr></thead><tbody>";
$tableEnd = "</tbody></table></div>";

$text = $nav . $tableStart . $pointTable . $tableEnd;

e107::getRender()->tablerender(LAN_RACERS_STARTLIST_CAPTION, $text);

require_once(FOOTERF);
