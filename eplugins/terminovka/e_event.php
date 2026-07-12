<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Terminovka.sk plugin - event handlers
 *
 * Listens for timetracker events and exports results to the terminovka.sk
 * external API. timetracker OWNS and WRITES the `race_result` table; this
 * plugin reads unsent rows, sends them, and logs the send outcome by
 * triggering `terminovka_saveresult` (timetracker performs the write).
*/

if (!defined('e107_INIT')) { exit; }


class terminovka_event // plugin-folder + '_event'
{

    function config()
    {
        $event = array();

        // Consolidated "racer finished" event.
        // Timetracker triggers this from ONE place when a racer crosses the
        // finish line; this handler owns the race_result lifecycle end-to-end
        // (insert-if-missing, update-if-changed, send-to-API-if-unsent).
        // Replaces the two copy-pasted blocks that used to live in
        // timetracker_class::get_racer_time_on_point() and
        // get_racer_time_between_points().
        $event[] = array(
            'name'     => 'terminovka_finish_time',
            'function' => 'onFinishTime',
        );

        // Legacy events - kept for backward compatibility and for manual
        // re-send workflows (terminovka.php batch worker, terminovka_test.php).
        $event[] = array(
            'name'     => 'terminovka_saveresult',
            'function' => 'saveresult',
        );

        $event[] = array(
            'name'     => 'terminovka_senddata',
            'function' => 'senddata',
        );

        $event[] = array(
            'name'     => 'terminovka_deleteresult',
            'function' => 'deleteresult',
        );

        $event[] = array(
            'name'     => 'terminovka_sendagain',
            'function' => 'sendagain',
        );

        return $event;
    }


    /**
     * Consolidated finish-time handler.
     *
     * Called by timetracker whenever a racer crosses the finish line with a
     * valid time. Owns the full race_result lifecycle:
     *
     *   1. No row for this racer yet   -> INSERT (sent=0, will be picked up
     *                                     by the batch worker or by a later
     *                                     finish-time trigger).
     *   2. Row exists, time changed    -> UPDATE (sent=0, retry).
     *   3. Row exists, time unchanged,
     *      not yet sent                -> SEND to API now, UPDATE log+sent.
     *   4. Row exists, already sent    -> do nothing.
     *
     * Expected $data keys: racer_number, time
     */
    function onFinishTime($data = array())
    {
        if (empty($data['racer_number']) || empty($data['time']))
        {
            return;
        }

        $racer_number = $data['racer_number'];
        $time_text    = $data['time'];

        $existing = $this->findResult($racer_number);

        if (!$existing)
        {
            $this->insertResult($racer_number, $time_text);
            return;
        }

        if ($existing['race_result_time'] !== $time_text)
        {
            $this->updateResultTime($existing, $racer_number, $time_text);
            return;
        }

        if (!$existing['race_result_sent'])
        {
            $this->sendAndLog($racer_number, $time_text);
        }
    }


    function findResult($racer_number)
    {
        // The start number is a 4-char string that may carry leading zeros
        // (e.g. "0042"), so it must be quoted - an (int) cast would turn
        // "0042" into 42 and miss the stored row.
        $tp = e107::getParser();
        return e107::getDb()->retrieve('race_result', '*',
            "WHERE race_result_number = '" . $tp->toDB($racer_number) . "'");
    }


    function insertResult($racer_number, $time_text)
    {
        $row = array(
            'race_result_number'  => $racer_number,
            'race_result_time'    => $time_text,
            'race_result_sent'    => 0,
            'race_result_log'     => '---',
            'race_result_created' => time(),
            'race_result_updated' => time(),
        );
        e107::getEvent()->trigger('terminovka_saveresult', $row);
    }


    function updateResultTime($existing, $racer_number, $time_text)
    {
        $row = array(
            'race_result_number'  => $racer_number,
            'race_result_time'    => $time_text,
            'race_result_sent'    => 0,
            'race_result_log'     => '---',
            'race_result_created' => $existing['race_result_created'],
            'race_result_updated' => time(),
        );
        e107::getEvent()->trigger('terminovka_saveresult', $row);
    }


    /**
     * Send the result to the terminovka.sk API and persist the outcome.
     *
     * Extracted so that both the finish-time trigger path and the manual
     * batch-worker path (terminovka.php) can use identical logic.
     */
    function sendAndLog($racer_number, $time_text)
    {
        $data = array(
            'race_result_number'   => $racer_number,
            'race_result_time'     => $time_text,
            'race_result_sent'     => 0,
            'race_result_log'      => '',
            'race_result_created'  => time(),
            'race_result_updated'  => '',
            'race_result_timesent' => time(),
        );

        $resp_data = e107::getEvent()->trigger('terminovka_senddata', $data);

        // senddata() always returns an array now. If the export is not
        // configured (or the racer/race lookup failed) it returns
        // success=0 with a status message - handle that gracefully instead
        // of trying to json_decode a non-existent response.
        if (!is_array($resp_data))
        {
            return;
        }

        $ok = !empty($resp_data['success']) ? 1 : 0;

        $data['race_result_sent']     = $ok;
        // Log only the API status/response - never the outgoing payload,
        // which contains the racer's name and surname (PII).
        $data['race_result_log']      = json_encode($resp_data);
        $data['race_result_timesent'] = time();

        e107::getEvent()->trigger('terminovka_saveresult', $data);
    }


    /* ------------------------------------------------------------------
     * Legacy handlers - kept for backward compatibility.
     * ------------------------------------------------------------------ */

    function deleteresult($data = array())
    {
        if (empty($data['race_result_number'])) return;
        $tp    = e107::getParser();
        $num   = $tp->toDB($data['race_result_number']);
        $query = " race_result_number = '" . $num . "'";

        $result = e107::getDb()->retrieve('race_result', '*', $query);
        if ($result)
        {
            e107::getDb()->delete('race_result', $query);
        }
    }


    function saveresult($data = array())
    {
        if (empty($data['race_result_number'])) return;
        $tp     = e107::getParser();
        $num    = $tp->toDB($data['race_result_number']);
        $query  = " WHERE race_result_number = '" . $num . "'";
        $result = e107::getDb()->retrieve('race_result', '*', $query);

        if (!$result)
        {
            e107::getDb()->insert('race_result', $data);
        }
        else
        {
            $data['WHERE'] = " race_result_number = '" . $num . "'";
            e107::getDb()->update('race_result', $data);
        }
    }


    function sendagain($data = array())
    {
        if (empty($data['race_result_number'])) return;
        $tp     = e107::getParser();
        $num    = $tp->toDB($data['race_result_number']);
        $query  = " WHERE race_result_number = '" . $num . "'";
        $result = e107::getDb()->retrieve('race_result', '*', $query);

        if ($result)
        {
            $update = array(
                'race_result_sent' => 0,
                'WHERE'            => $query,
            );
            e107::getDb()->update('race_result', $update);
        }
    }


    /**
     * Actually POST the result to terminovka.sk.
     */
    function senddata($data = array())
    {
        e107::lan('terminovka', true, true);

        $check_error = $this->docheck();
        if ($check_error)
        {
            // Always return an array so the caller can use array access
            // without tripping over a string offset on PHP 8.
            return array(
                'success' => 0,
                'result'  => $check_error,
            );
        }

        $tp            = e107::getParser();
        $export_apikey = e107::pref('terminovka', 'export_apikey');
        $export_url    = e107::pref('terminovka', 'export_url');
        $url           = $export_url;
        $racer_number  = isset($data['race_result_number']) ? $data['race_result_number'] : '';

        // Explicit array access only - extract() on a DB row would clobber
        // locals like $url / $export_apikey with arbitrary column values.
        $racer = e107::getDb()->retrieve('racer', '*',
            " WHERE racer_number = '" . $tp->toDB($racer_number) . "'");
        if (!$racer)
        {
            return array(
                'success' => 0,
                'result'  => LAN_TERMINOVKA_ERR_NO_RACER,
            );
        }

        $racer_extid     = isset($racer['racer_extid'])     ? $racer['racer_extid']     : '';
        $racer_race_id   = isset($racer['racer_race_id'])   ? (int) $racer['racer_race_id']   : 0;
        $racer_category_id = isset($racer['racer_category_id']) ? (int) $racer['racer_category_id'] : 0;
        $racer_firstname = isset($racer['racer_firstname']) ? $racer['racer_firstname'] : '';
        $racer_surname   = isset($racer['racer_surname'])   ? $racer['racer_surname']   : '';

        $race = e107::getDb()->retrieve('race', '*', " WHERE race_id = " . $racer_race_id);
        if (!$race)
        {
            return array(
                'success' => 0,
                'result'  => LAN_TERMINOVKA_ERR_NO_RACE,
            );
        }

        // The external track ID is owned by terminovka (issue #34): read it
        // from terminovka_track by race_id (parameterized). Fall back to 0 when
        // no mapping exists so export never fatals on unmapped tracks.
        $track      = e107::getDb()->retrieve('terminovka_track', 'ext_id', 'race_id=' . $racer_race_id);
        $race_extid = ($track !== false && $track !== null) ? (int) $track : 0;

        $category = e107::getDb()->retrieve('race_category', '*',
            " WHERE race_category_id = " . $racer_category_id);
        $race_category_name = ($category && isset($category['race_category_name']))
            ? $category['race_category_name'] : '';

        $senddata = array(
            'id'              => $racer_extid,
            'externalTrackId' => $race_extid,
            'startNumber'     => isset($data['race_result_number']) ? $data['race_result_number'] : '',
            'resultTime'      => isset($data['race_result_time']) ? substr($data['race_result_time'], 0, 9) : '',
            'name'            => $racer_firstname,
            'surname'         => $racer_surname,
            'category'        => $race_category_name,
        );

        $headr = array(
            'Content-type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $export_apikey,
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headr);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($senddata));
        // Don't let a stalled remote endpoint hang the batch worker.
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        // Keep TLS verification ON - this transmits participant PII.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $resp     = curl_exec($curl);
        $curl_err = curl_errno($curl);
        $curl_msg = curl_error($curl);
        curl_close($curl);

        if ($resp === false || $curl_err)
        {
            // Log the transport-level error only - no payload, no PII.
            return array(
                'success' => 0,
                'result'  => 'cURL error (' . (int) $curl_err . '): ' . $curl_msg,
            );
        }

        $decoded = json_decode($resp, true);
        $success = (is_array($decoded) && !empty($decoded['success'])) ? 1 : 0;

        // 'result' holds the API's raw response body (no outgoing payload,
        // so no racer name/surname is ever written to race_result_log).
        return array(
            'success' => $success,
            'result'  => $resp,
        );
    }


    function docheck()
    {
        e107::lan('terminovka', true, true);

        $export_actived = e107::pref('terminovka', 'export_actived');
        $export_apikey  = e107::pref('terminovka', 'export_apikey');
        $export_url     = e107::pref('terminovka', 'export_url');

        if (!$export_actived)       return LAN_TERMINOVKA_ERR_NOT_ACTIVE;
        if (!$export_apikey)        return LAN_TERMINOVKA_ERR_NO_TOKEN;
        if (!$export_url)           return LAN_TERMINOVKA_ERR_NO_URL;
        return false;
    }
}
