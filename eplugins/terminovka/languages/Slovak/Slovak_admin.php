<?php
/*
 * e107 website system
 *
 * Terminovka.sk plugin - Slovak admin strings
*/

define('LAN_TERMINOVKA_PLUGIN',      'Terminovka.sk');
define('LAN_TERMINOVKA_EXPORT_LOGS', 'Logy exportu');

/* ---- Skratky medzi pluginmi presunuté do raceevent_admin_links ------------ */

/* ---- Injected race track edit form (e_admin.php addon, issue #34) --------- */
if (!defined('LAN_TERMINOVKA_TAB'))             define('LAN_TERMINOVKA_TAB', 'Terminovka');
if (!defined('LAN_TERMINOVKA_TRACK_EXTID'))     define('LAN_TERMINOVKA_TRACK_EXTID', 'ID trate na terminovka.sk');
if (!defined('LAN_TERMINOVKA_TRACK_EXTID_HELP')) define('LAN_TERMINOVKA_TRACK_EXTID_HELP', 'ID trate na terminovka.sk, použité ako externé ID trate pri exporte. Ak trať tam nie je zverejnená, ponechajte 0.');

/* ---- Preference titles / help -------------------------------------------- */
define('LAN_TERMINOVKA_PREF_ACTIVE',        'Povoliť export');
define('LAN_TERMINOVKA_PREF_TOKEN',         'Export Token');
define('LAN_TERMINOVKA_PREF_URL',           'Export URL');
define('LAN_TERMINOVKA_PREF_URL_HELP',      'napr. https://www.terminovka.sk/api/track-participant/final-time');
define('LAN_TERMINOVKA_PREF_INTERVAL',      'Interval obnovenia (sekundy)');
define('LAN_TERMINOVKA_PREF_INTERVAL_HELP', 'Ako často sa stránka manuálneho exportu obnoví a znova skúsi odoslať neodoslané výsledky. Hodnota 0 obnovovanie vypne.');

/* ---- Preferences help box ------------------------------------------------ */
define('LAN_TERMINOVKA_HELP_BATCH_TITLE',    'Manuálny batch export');
define('LAN_TERMINOVKA_HELP_BATCH_TEXT',     'Preposiela existujúce neodoslané výsledky. Používa sa pre tých, čo nedobehli do cieľa.');
define('LAN_TERMINOVKA_HELP_BATCH_BTN',      'Spustiť manuálny export');
define('LAN_TERMINOVKA_HELP_TEST_TITLE',     'Test konfigurácie');
define('LAN_TERMINOVKA_HELP_TEST_TEXT',      'Overí, či je export aktivovaný a či je zadaný API kľúč.');
define('LAN_TERMINOVKA_HELP_TEST_BTN',       'Test');
define('LAN_TERMINOVKA_HELP_INTERVAL_TITLE', 'Interval obnovenia');
define('LAN_TERMINOVKA_HELP_INTERVAL_TEXT',  'Batch export používa vlastnú predvoľbu <code>refresh_interval</code>. Ak je 0, stránka exportu sa nebude obnovovať.');

/* ---- Export-log list field titles + help --------------------------------- */
define('LAN_TERMINOVKA_FIELD_NUMBER',   'Štartové číslo');
define('LAN_TERMINOVKA_FIELD_TIME',     'Čas');
define('LAN_TERMINOVKA_FIELD_SENT',     'Poslané');
define('LAN_TERMINOVKA_FIELD_LOG',      'Log');
define('LAN_TERMINOVKA_FIELD_CREATED',  'Vytvorené');
define('LAN_TERMINOVKA_FIELD_UPDATED',  'Aktualizované');
define('LAN_TERMINOVKA_FIELD_TIMESENT', 'Odoslané');
define('LAN_TERMINOVKA_LOG_HELP',       'Záznamy sú vygenerované/aktualizované zostavou Online (timetracker). Odosielané na API podľa príznaku "neposlané". Toto je iba zobrazenie - výsledky upravujte v plugine timetracker.');

/* ---- Configuration errors (shared by e_event / batch / test) ------------- */
define('LAN_TERMINOVKA_ERR_NOT_ACTIVE', 'Export nie je aktivovaný');
define('LAN_TERMINOVKA_ERR_NO_TOKEN',   'Nie je zadaný token');
define('LAN_TERMINOVKA_ERR_NO_URL',     'Nie je zadaná cieľová URL');
define('LAN_TERMINOVKA_ERR_NO_APIKEY',  'Nie je zadaný API Kľúč');
define('LAN_TERMINOVKA_ERR_NO_INTERVAL','Nie je nastavený interval refreshu');
define('LAN_TERMINOVKA_ERR_NO_RACER',   'Nemám údaje o pretekárovi');
define('LAN_TERMINOVKA_ERR_NO_RACE',    'Nemám údaje o trati');

/* ---- Batch worker (terminovka.php) --------------------------------------- */
define('LAN_TERMINOVKA_SENDING', 'Posielam štartovacie číslo');
define('LAN_TERMINOVKA_DONE',    'Export dobehol');

/* ---- Diagnostic test tool (terminovka_test.php) -------------------------- */
define('LAN_TERMINOVKA_TEST_MISSING_PARAM',    'Chýba parameter ?n= v URL adrese. Použite ?n=test pre kontrolu konfigurácie alebo ?n=<číslo> pre overenie pretekára.');
define('LAN_TERMINOVKA_TEST_BAD_PARAM',        'Parameter ?n= musí byť buď "test" alebo číslo (racer_extid). Dostal som: ');
define('LAN_TERMINOVKA_TEST_ACTIVE_OK',        'Export je aktivovaný');
define('LAN_TERMINOVKA_TEST_APIKEY_OK',        'API kľúč je zadaný, ale nie je overený');
define('LAN_TERMINOVKA_TEST_RACER_NOT_FOUND',  'Nenájdený pretekár s ID ');
define('LAN_TERMINOVKA_TEST_RACER_MULTI',      'Nájdených viac pretekárov s ID ');
define('LAN_TERMINOVKA_TEST_RACER_FOUND',      'Nájdený pretekár so štartovým číslom ');
define('LAN_TERMINOVKA_TEST_RESULT_NOT_FOUND', 'Nenájdený výsledok pre ID ');
define('LAN_TERMINOVKA_TEST_RESULT_FOUND',     'Nájdený výsledok pre ');
define('LAN_TERMINOVKA_TEST_RESULT_TIME',      '. Výsledný čas: ');
define('LAN_TERMINOVKA_TEST_ALREADY_SENT',     'Už doslané: ');
define('LAN_TERMINOVKA_TEST_FORCE_HINT',       ' Ak to chcete zopakovať, použite parameter &force=1.');
define('LAN_TERMINOVKA_TEST_FORCING',          'Vynútené opakované poslanie...');
define('LAN_TERMINOVKA_TEST_SENDING',          'Odosielam...');
define('LAN_TERMINOVKA_TEST_SEND_OK',          'Úspešne odoslané.');
define('LAN_TERMINOVKA_TEST_SEND_FAIL',        'Odoslanie zlyhalo. Pozri log.');
define('LAN_TERMINOVKA_TEST_NO_RESPONSE',      'Nedostal som žiadnu odpoveď od API.');
