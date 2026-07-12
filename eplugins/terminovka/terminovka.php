<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Terminovka.sk plugin - batch export worker
 *
 * Picks up every race_result row with sent=0 and re-tries sending it to the
 * API. Useful for racers who didn't cross the finish line naturally (manual
 * DNF/DSQ entries, late fixes) - the normal trigger path wouldn't catch
 * those.
*/

require_once('../../class2.php');
if (!getperms('P'))
{
    e107::redirect('admin');
    exit;
}

e107::lan('terminovka', true, true);

$tp        = e107::getParser();
$plugPrefs = e107::pref('terminovka');

// Terminovka owns its own refresh interval (decoupled from racerfid).
// If it is 0, the batch page will not refresh and we stop here.
$interval = (int) varset($plugPrefs['refresh_interval'], 0);

$export_actived = (int) varset($plugPrefs['export_actived'], 0);
$export_apikey  = varset($plugPrefs['export_apikey'], '');


if ($interval <= 0)
{
    echo LAN_TERMINOVKA_ERR_NO_INTERVAL;
    exit;
}

if (!$export_actived)
{
    echo LAN_TERMINOVKA_ERR_NOT_ACTIVE;
    exit;
}

if (!$export_apikey)
{
    echo LAN_TERMINOVKA_ERR_NO_APIKEY;
    exit;
}

// Find every unsent result and re-run the send logic.
$results = e107::getDb()->retrieve('race_result', '*', "WHERE race_result_sent = 0", true);

if (is_array($results))
{
    $eventMgr = e107::getEvent();

    foreach ($results as $result)
    {
        $racer_number = $result['race_result_number'];
        echo "<br>" . LAN_TERMINOVKA_SENDING . ": " . $tp->toHTML($racer_number);

        // Reuse the consolidated send-and-log path in e_event.php
        // (identical logic to the natural trigger path).
        $eventMgr->trigger('terminovka_finish_time', array(
            'racer_number' => $racer_number,
            'time'         => $result['race_result_time'],
        ));
    }
}

echo "<br>" . LAN_TERMINOVKA_DONE . ": " . date("d.m.Y H:i:s", time());

header("Refresh: " . $interval);
