<?php
/*
 * e107 website system
 *
 * Terminovka.sk plugin - English admin strings
*/

define('LAN_TERMINOVKA_PLUGIN',      'Terminovka.sk');
define('LAN_TERMINOVKA_EXPORT_LOGS', 'Export logs');

/* ---- Cross-plugin admin-menu shortcuts moved to raceevent_admin_links ----- */

/* ---- Injected race track edit form (e_admin.php addon, issue #34) --------- */
if (!defined('LAN_TERMINOVKA_TAB'))             define('LAN_TERMINOVKA_TAB', 'Terminovka');
if (!defined('LAN_TERMINOVKA_TRACK_EXTID'))     define('LAN_TERMINOVKA_TRACK_EXTID', 'terminovka.sk track ID');
if (!defined('LAN_TERMINOVKA_TRACK_EXTID_HELP')) define('LAN_TERMINOVKA_TRACK_EXTID_HELP', "The track's ID on terminovka.sk, used as the external track ID during export. Leave 0 if the track is not published there.");

/* ---- Preference titles / help -------------------------------------------- */
define('LAN_TERMINOVKA_PREF_ACTIVE',        'Enable export');
define('LAN_TERMINOVKA_PREF_TOKEN',         'Export token');
define('LAN_TERMINOVKA_PREF_URL',           'Export URL');
define('LAN_TERMINOVKA_PREF_URL_HELP',      'e.g. https://www.terminovka.sk/api/track-participant/final-time');
define('LAN_TERMINOVKA_PREF_INTERVAL',      'Refresh interval (seconds)');
define('LAN_TERMINOVKA_PREF_INTERVAL_HELP', 'How often the manual batch export page refreshes and re-tries unsent results. Set to 0 to disable refresh.');

/* ---- Preferences help box ------------------------------------------------ */
define('LAN_TERMINOVKA_HELP_BATCH_TITLE',    'Manual batch export');
define('LAN_TERMINOVKA_HELP_BATCH_TEXT',     'Re-sends existing unsent results. Used for racers who did not cross the finish line naturally.');
define('LAN_TERMINOVKA_HELP_BATCH_BTN',      'Run manual export');
define('LAN_TERMINOVKA_HELP_TEST_TITLE',     'Configuration test');
define('LAN_TERMINOVKA_HELP_TEST_TEXT',      'Checks whether the export is activated and whether the API key is set.');
define('LAN_TERMINOVKA_HELP_TEST_BTN',       'Test');
define('LAN_TERMINOVKA_HELP_INTERVAL_TITLE', 'Refresh interval');
define('LAN_TERMINOVKA_HELP_INTERVAL_TEXT',  'The batch export uses its own <code>refresh_interval</code> preference. If it is 0, the export page will not refresh.');

/* ---- Export-log list field titles + help --------------------------------- */
define('LAN_TERMINOVKA_FIELD_NUMBER',   'Start number');
define('LAN_TERMINOVKA_FIELD_TIME',     'Time');
define('LAN_TERMINOVKA_FIELD_SENT',     'Sent');
define('LAN_TERMINOVKA_FIELD_LOG',      'Log');
define('LAN_TERMINOVKA_FIELD_CREATED',  'Created');
define('LAN_TERMINOVKA_FIELD_UPDATED',  'Updated');
define('LAN_TERMINOVKA_FIELD_TIMESENT', 'Sent at');
define('LAN_TERMINOVKA_LOG_HELP',       'These records are generated/updated by the Online standings (timetracker). They are sent to the API based on the "unsent" flag. This is a read-only view - edit results in timetracker.');

/* ---- Configuration errors (shared by e_event / batch / test) ------------- */
define('LAN_TERMINOVKA_ERR_NOT_ACTIVE', 'Export is not activated');
define('LAN_TERMINOVKA_ERR_NO_TOKEN',   'Token is not set');
define('LAN_TERMINOVKA_ERR_NO_URL',     'Target URL is not set');
define('LAN_TERMINOVKA_ERR_NO_APIKEY',  'API key is not set');
define('LAN_TERMINOVKA_ERR_NO_INTERVAL','Refresh interval is not set');
define('LAN_TERMINOVKA_ERR_NO_RACER',   'No racer data');
define('LAN_TERMINOVKA_ERR_NO_RACE',    'No track data');

/* ---- Batch worker (terminovka.php) --------------------------------------- */
define('LAN_TERMINOVKA_SENDING', 'Sending start number');
define('LAN_TERMINOVKA_DONE',    'Export finished');

/* ---- Diagnostic test tool (terminovka_test.php) -------------------------- */
define('LAN_TERMINOVKA_TEST_MISSING_PARAM',    'Missing ?n= parameter in the URL. Use ?n=test to check the configuration or ?n=<number> to verify a racer.');
define('LAN_TERMINOVKA_TEST_BAD_PARAM',        'The ?n= parameter must be either "test" or a number (racer_extid). Received: ');
define('LAN_TERMINOVKA_TEST_ACTIVE_OK',        'Export is activated');
define('LAN_TERMINOVKA_TEST_APIKEY_OK',        'API key is set, but not verified');
define('LAN_TERMINOVKA_TEST_RACER_NOT_FOUND',  'No racer found with ID ');
define('LAN_TERMINOVKA_TEST_RACER_MULTI',      'Multiple racers found with ID ');
define('LAN_TERMINOVKA_TEST_RACER_FOUND',      'Racer found with start number ');
define('LAN_TERMINOVKA_TEST_RESULT_NOT_FOUND', 'No result found for ID ');
define('LAN_TERMINOVKA_TEST_RESULT_FOUND',     'Result found for ');
define('LAN_TERMINOVKA_TEST_RESULT_TIME',      '. Result time: ');
define('LAN_TERMINOVKA_TEST_ALREADY_SENT',     'Already sent: ');
define('LAN_TERMINOVKA_TEST_FORCE_HINT',       ' To repeat it, use the &force=1 parameter.');
define('LAN_TERMINOVKA_TEST_FORCING',          'Forcing a re-send...');
define('LAN_TERMINOVKA_TEST_SENDING',          'Sending...');
define('LAN_TERMINOVKA_TEST_SEND_OK',          'Successfully sent.');
define('LAN_TERMINOVKA_TEST_SEND_FAIL',        'Sending failed. See the log.');
define('LAN_TERMINOVKA_TEST_NO_RESPONSE',      'Received no response from the API.');
