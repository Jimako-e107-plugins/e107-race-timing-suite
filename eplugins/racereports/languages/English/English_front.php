<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - languages/English/English_front.php
 *
*/

if (!defined('e107_INIT')) { exit; }

return array(
    // Live (online) report.
    'LAN_RACEREPORTS_LIVE_STATE'     => 'current standings',
    'LAN_RACEREPORTS_ALL_CATEGORIES' => 'all categories',

    // Online report - admin-only auto-refresh help note. The BODY is trusted HTML,
    // echoed raw (not through toHTML).
    'LAN_RACEREPORTS_HELP_REFRESH_TITLE' => 'Online results — automatic refresh',
    'LAN_RACEREPORTS_HELP_REFRESH_BODY'  =>
        '<p>The standings can refresh themselves so the announcer or a spectator sees the'
        . ' current order without reloading the page manually.</p>'
        . '<p><strong>Default interval (plugin settings):</strong> In'
        . ' "Online – auto-refresh interval" you enter the number of seconds. It applies'
        . ' to all online windows where the address does not say otherwise. For example'
        . ' 30 = refresh every 30 seconds. 0 = no automatic refresh.</p>'
        . '<p><strong>A different interval for a specific window (address parameter):</strong>'
        . ' By adding <code>?refresh=</code> with a number of seconds to the address you set'
        . ' the interval for this window only (e.g. <code>...?refresh=10</code>). The address'
        . ' parameter takes precedence — the plugin setting is then completely ignored for it.</p>'
        . '<ul>'
        . '<li><code>?refresh=10</code> → this window refreshes every 10 seconds.</li>'
        . '<li><code>?refresh=0</code> → this window does not refresh at all, even when an'
        . ' interval is set in the settings (e.g. "freezing" a window you want to inspect'
        . ' at leisure).</li>'
        . '<li>It applies only to the given window/address, it does not change the setting'
        . ' for others.</li>'
        . '</ul>'
        . '<p>Typical use: the finish-line projector fast (<code>?refresh=10</code>), an'
        . ' internal screen slower (<code>?refresh=60</code>), a control window frozen'
        . ' (<code>?refresh=0</code>).</p>',
    // Empty / not-found notices.
    'LAN_RACEREPORTS_NO_RACE'        => 'No race selected.',
    'LAN_RACEREPORTS_NO_POINT'       => 'No checkpoint selected.',

    // Finish (post-race results list) report - heading suffix.
    'LAN_RACEREPORTS_FINISH_TITLE'   => 'Results',

    // Start (start-point standings list) report - heading suffix.
    'LAN_RACEREPORTS_START_TITLE'    => 'Start list',

    // Custom / segment (between-two-points split) report - the page heading names
    // both points: "Od - <A> / Do - <B>" (from / to).
    'LAN_RACEREPORTS_OD'             => 'Od',
    'LAN_RACEREPORTS_DO'             => 'Do',

    // SUT report; the COL_* labels are shared with other reports.
    'LAN_RACEREPORTS_SUT_TITLE'      => 'Results',
    'LAN_RACEREPORTS_SUT_NO_FINISH'  => 'No finishers yet.',
    'LAN_RACEREPORTS_COL_RANK'        => 'Ranking',
    'LAN_RACEREPORTS_COL_TIME'        => 'Time',
    'LAN_RACEREPORTS_COL_SURNAME'     => 'Family Name',
    'LAN_RACEREPORTS_COL_FIRSTNAME'   => 'First Name',
    'LAN_RACEREPORTS_COL_GENDER'      => 'Gender',
    'LAN_RACEREPORTS_COL_BIRTHDATE'   => 'Birthdate',
    'LAN_RACEREPORTS_COL_NATIONALITY' => 'Nationality',
    'LAN_RACEREPORTS_COL_BIB'         => 'Bibnumber',
    // Category column (arrivals board header, finish export).
    'LAN_RACEREPORTS_COL_CATEGORY'    => 'Category',
    // Finish/start export column labels (the finish/start tables have no on-screen
    // header row; these are used only for the CSV/XLS header line).
    'LAN_RACEREPORTS_COL_NAME'        => 'Name',
    'LAN_RACEREPORTS_COL_STATUS'      => 'Status',

    // SUT export buttons (CSV / XLS download).
    'LAN_RACEREPORTS_EXPORT_CSV'      => 'CSV',
    'LAN_RACEREPORTS_EXPORT_XLS'      => 'XLS',

    // AKTUALNE (full per-race results matrix) report.
    'LAN_RACEREPORTS_AKT_TITLE'         => 'Full results',
    'LAN_RACEREPORTS_AKT_UNKNOWN_RACE'  => 'Unknown race.',
    'LAN_RACEREPORTS_AKT_EMPTY'         => 'No racers.',
    'LAN_RACEREPORTS_AKT_COL_POR'       => 'Pos.',
    'LAN_RACEREPORTS_AKT_COL_NAME'      => 'Name',
    'LAN_RACEREPORTS_AKT_COL_CAT'       => 'Cat.',
    'LAN_RACEREPORTS_AKT_COL_TIME'      => 'Time',
    'LAN_RACEREPORTS_AKT_COL_CATRANK'   => 'Rank in category',

    // NUMBER (racer progression) report: unknown bib, column headers and the
    // DNF/DSQ status note (DNF/DSQ also used as the SUT marker).
    'LAN_RACEREPORTS_NUM_UNKNOWN_BIB'   => 'Unknown bib number.',
    'LAN_RACEREPORTS_NUM_COL_POINT'     => 'Checkpoint',
    'LAN_RACEREPORTS_NUM_COL_TIMEOFDAY' => 'Time of day',
    'LAN_RACEREPORTS_NUM_COL_SPLIT'     => 'Split',
    'LAN_RACEREPORTS_NUM_COL_SEGMENT'   => 'Segment',
    'LAN_RACEREPORTS_NUM_DNF'           => 'DNF',
    'LAN_RACEREPORTS_NUM_DSQ'           => 'DSQ',
);
