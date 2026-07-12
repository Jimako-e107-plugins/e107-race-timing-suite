<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Terminovka.sk plugin - diagnostic test tool
 *
 * Internal admin tool for manually verifying a single racer's export.
 * Accepts ?n=<racer_extid> or ?n=test. Use ?force=1 to resend a result
 * that was already marked as sent.
 *
 * No public URL alias - this is meant to be accessed directly by admins
 * by typing the plugin path.
*/

if (!defined('e107_INIT'))
{
    require_once(__DIR__ . '/../../class2.php');
}

if (!getperms('P'))
{
    e107::redirect('admin');
    exit;
}

require_once(HEADERF);

e107::lan('terminovka', true, true);

$mes      = e107::getMessage();
$tp       = e107::getParser();
$eventMgr = e107::getEvent();

$force = (isset($_GET['force']) && $_GET['force'] == 1);

// Validate the ?n= parameter up front. Accepted values:
//   ?n=test       -> config-only diagnostic mode
//   ?n=<integer>  -> look up racer by racer_extid
if (!isset($_GET['n']))
{
    $mes->addError(LAN_TERMINOVKA_TEST_MISSING_PARAM);
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

$n_param = $_GET['n'];
$is_test_mode = ($n_param === 'test');

if (!$is_test_mode)
{
    $extid = (int) $tp->filter($n_param, 'int');
    if (!$extid)
    {
        $mes->addError(LAN_TERMINOVKA_TEST_BAD_PARAM . $tp->toHTML($n_param));
        echo $mes->render();
        require_once(FOOTERF);
        exit;
    }
}

// Config check - runs for both test and racer modes.
$export_actived = e107::pref('terminovka', 'export_actived');
$export_apikey  = e107::pref('terminovka', 'export_apikey');

if ($export_actived)
{
    $mes->addSuccess(LAN_TERMINOVKA_TEST_ACTIVE_OK);
}
else
{
    $mes->addError(LAN_TERMINOVKA_ERR_NOT_ACTIVE);
}

if ($export_apikey)
{
    $mes->addSuccess(LAN_TERMINOVKA_TEST_APIKEY_OK);
}
else
{
    $mes->addError(LAN_TERMINOVKA_ERR_NO_APIKEY);
}

// "test" mode - just check config and exit
if ($is_test_mode)
{
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

// Find the racer(s) with this external ID.
$racers = e107::getDb()->retrieve('racer', '*', ' WHERE racer_extid = ' . $extid, true);

if (!is_array($racers) || count($racers) === 0)
{
    $mes->addError(LAN_TERMINOVKA_TEST_RACER_NOT_FOUND . $extid);
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

if (count($racers) > 1)
{
    $numbers = array();
    foreach ($racers as $r) $numbers[] = $r['racer_number'];
    $mes->addError(LAN_TERMINOVKA_TEST_RACER_MULTI . $extid . ": " . $tp->toHTML(implode(", ", $numbers)));
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

$racer_number = $racers[0]['racer_number'];
$mes->addSuccess(LAN_TERMINOVKA_TEST_RACER_FOUND . $tp->toHTML($racer_number));

// Look up existing result row. The start number is a string that may carry
// leading zeros, so it must be quoted via toDB() rather than (int)-cast.
$result = e107::getDb()->retrieve('race_result', '*',
    " WHERE race_result_number = '" . $tp->toDB($racer_number) . "'");

if (!$result)
{
    $mes->addError(LAN_TERMINOVKA_TEST_RESULT_NOT_FOUND . $extid);
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

$mes->addSuccess(LAN_TERMINOVKA_TEST_RESULT_FOUND . $tp->toHTML($racer_number)
    . LAN_TERMINOVKA_TEST_RESULT_TIME . $tp->toHTML($result['race_result_time']));

// Already sent? Only resend if force=1.
if ($result['race_result_sent'] && !$force)
{
    $mes->addInfo(LAN_TERMINOVKA_TEST_ALREADY_SENT . $tp->toHTML($result['race_result_log'])
        . LAN_TERMINOVKA_TEST_FORCE_HINT);
    echo $mes->render();
    require_once(FOOTERF);
    exit;
}

if ($result['race_result_sent'] && $force)
{
    $mes->addInfo(LAN_TERMINOVKA_TEST_FORCING);
}
else
{
    $mes->addInfo(LAN_TERMINOVKA_TEST_SENDING);
}

// Build the payload (identical shape to what the trigger path uses).
$data = array(
    'race_result_number'   => $racer_number,
    'race_result_time'     => $result['race_result_time'],
    'race_result_sent'     => 0,
    'race_result_log'      => '',
    'race_result_created'  => (int) $result['race_result_created'],
    'race_result_updated'  => time(),
    'race_result_timesent' => time(),
);

$resp_data = $eventMgr->trigger('terminovka_senddata', $data);

if (is_array($resp_data) && isset($resp_data['result']))
{
    $response = $resp_data['result'];
    echo "<h4>Response:</h4><pre>" . htmlspecialchars(print_r($response, true), ENT_QUOTES, 'UTF-8') . "</pre>";

    $decoded = json_decode($response, true);
    echo "<h4>Decoded:</h4><pre>" . htmlspecialchars(print_r($decoded, true), ENT_QUOTES, 'UTF-8') . "</pre>";

    // senddata() reports success itself; the raw body is shown above only
    // for diagnostics.
    $ok = !empty($resp_data['success']) ? 1 : 0;

    $data['race_result_sent']     = $ok;
    $data['race_result_log']      = json_encode($resp_data);
    $data['race_result_timesent'] = time();

    $eventMgr->trigger('terminovka_saveresult', $data);

    if ($ok)
    {
        $mes->addSuccess(LAN_TERMINOVKA_TEST_SEND_OK);
    }
    else
    {
        $mes->addError(LAN_TERMINOVKA_TEST_SEND_FAIL);
    }
}
else
{
    $mes->addError(LAN_TERMINOVKA_TEST_NO_RESPONSE);
}

echo $mes->render();
require_once(FOOTERF);
exit;