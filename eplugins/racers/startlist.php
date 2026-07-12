<?php
/*
 * e107 website system
 *
 * racers - public start list (štartovacia listina).
 *
 * Lists ALL registered competitors for a track (race_sef) + category
 * (race_category_sef), before the start. The "Čas štartu" column shows the
 * start time only for racers who already started; an empty cell flags who is
 * still missing at the start.
 *
 * The start time is read from the timing layer via the racetiming engine
 * (race_clock::timeOfDay at the race_time point 'start', formatted with
 * formatTimeOfDay). The read is GUARDED by isInstalled('racetiming'); when
 * racetiming is absent the cell is empty (racer not started). This decouples
 * the start list from timetracker - the previously deliberate, temporary
 * timetracker coupling is now done (see NOTES.md).
 */
if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}

// Register the compact-row rule BEFORE HEADERF: require_once(HEADERF) renders the
// document <head> and flushes all registered CSS (incl. 'inline') into it, so any
// e107::css() call AFTER it never reaches the head. The racereports report pages
// (report_point/online/finish/stu.php) all register this exact rule before HEADERF
// - matched here so the rows render with the same compact padding.
$style = '
.table td, .table th {
    padding: 3px!important;
}';
e107::css('inline', $style);

require_once(HEADERF);

$tp  = e107::getParser();
$sql = e107::getDb();

e107::lan('racers', '', true);     // racers front strings (array form, EN + SK)
// raceevent's _front was loaded here previously but this page references no
// raceevent strings (only LAN_RACERS_STARTLIST_*), so that dead load was removed.

$paramRaceSef  = $tp->filter(varset($_GET['r'], ''));
$paramCategSef = $tp->filter(varset($_GET['c'], ''));

// racers' own name renderer (canonical) - not racetrack/functions.php.
$rr = e107::getSingleton('plugin_racers_racers', e_PLUGIN . 'racers/includes/racers.php');

// Start-time source: the racetiming engine (race_clock), guarded by
// isInstalled('racetiming'). When racetiming is absent the start cell is empty
// (racer not started). One build() loads every crossing once for the page.
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
    // Carry the CURRENT category across track tabs so switching tracks keeps the
    // chosen category (the ALL tab still resets both to 'all').
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

        // Start time: filled from the racetiming engine when installed, otherwise
        // empty. An empty cell means the racer has not started yet. The displayed
        // form is the wall-clock TIME-OF-DAY (HH:MM:SS.mmm) - formatTimeOfDay at 3
        // decimals reproduces the legacy microtimeToSeconds string exactly.
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
