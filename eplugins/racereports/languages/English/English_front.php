<?php
/*
 * e107 website system
 *
 * racereports plugin - English front language file.
 *
 * Array-style LAN file loaded on the front-end via
 * e107::lan('racereports', '', true) by the online (report_online.php) and point
 * (report_point.php) report pages. Canonical, complete set; the Slovak file
 * (languages/Slovak/Slovak_front.php) overrides per key and falls back here for
 * any missing term. LAN key pattern: LAN_RACEREPORTS_<NAME>.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Live (online) report.
    'LAN_RACEREPORTS_LIVE_STATE'     => 'current standings',
    'LAN_RACEREPORTS_ALL_CATEGORIES' => 'all categories',

    // Online report - auto-refresh page help (collapsible note at the top). The
    // BODY is trusted HTML (echoed raw, not through toHTML) so the headings, list
    // and <code> markup render; the source of truth is this LAN file.
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

    // SUT (per-track finishers-only results) report.
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
    // Dobeh (arrivals board) header - category column ("<category> — <Nth>").
    'LAN_RACEREPORTS_COL_CATEGORY'    => 'Category',
    // Finish/start export column labels (the finish/start tables have no on-screen
    // header row; these are used only for the CSV/XLS header line).
    'LAN_RACEREPORTS_COL_NAME'        => 'Name',
    'LAN_RACEREPORTS_COL_STATUS'      => 'Status',

    // SUT export buttons (CSV / fake-XLS download of the displayed results).
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

    // NUMBER (single-racer progression) report: unknown bib, column headers and the
    // DNF/DSQ status note.
    'LAN_RACEREPORTS_NUM_UNKNOWN_BIB'   => 'Unknown bib number.',
    'LAN_RACEREPORTS_NUM_COL_POINT'     => 'Checkpoint',
    'LAN_RACEREPORTS_NUM_COL_TIMEOFDAY' => 'Time of day',
    'LAN_RACEREPORTS_NUM_COL_SPLIT'     => 'Split',
    'LAN_RACEREPORTS_NUM_COL_SEGMENT'   => 'Segment',
    'LAN_RACEREPORTS_NUM_DNF'           => 'DNF',
    'LAN_RACEREPORTS_NUM_DSQ'           => 'DSQ',
);
