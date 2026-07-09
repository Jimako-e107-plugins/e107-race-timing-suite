<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2012 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Language file
 */

/**
 *	RFID import plugin (folder/id: racerfid) - Slovak admin language file
 *
 *	@package	e107_plugins
 *	@subpackage	racerfid
 */


define('LAN_RACETRACKING_TESTACCESS', 'Test prístupu k databáze');
define('LAN_RACETRACKING_002', 'Nastaviť ');
define('LAN_RACETRACKING_CONFIG', 'Hlavné nastavenie');
define('LAN_RACETRACKING_SERVER', 'Server:');
define('LAN_RACETRACKING_USERNAME', 'Používateľské meno:');
define('LAN_RACETRACKING_PASSWORD', 'Heslo:');
define('LAN_RACETRACKING_DATABASE', 'Databáza:');
define('LAN_RACETRACKING_TABLE',   'Tabuľka:');
define('LAN_RACETRACKING_PREFIX', 'Prefix tabuľky:');
define('LAN_RACETRACKING_PORT', "Port: ");
define('LAN_RACETRACKING_FIELDNAME', 'Pole mena:');
define('LAN_RACETRACKING_FIELDSTART', 'Pole času štartu:');
define('LAN_RACETRACKING_FIELDFINISH', 'Pole času cieľa:');
define('LAN_RACETRACKING_FIELDNUMBER', 'Pole štartového čísla:');

define('LAN_RACETRACKING_HELP_001', "Tu nastavte prístup k externej databáze. Spustite Test pripojenia. Ak zlyhá a ste si istý nastavením, skontrolujte hodnotu hesla. Skúste ho zadať ručne a nepoužívajte html entity. ");

define('LAN_RACETRACKING_HELP_002', "Prístup k nastaveniu DB má len hlavný administrátor. Ako vlastník pluginu ho môžete iba vypnúť a zakázať obsah cronu (cron stále beží, ale nič nerobí). Hlavný administrátor môže akciu cronu spustiť ručne zo Správcu plánovaných úloh. ");


define('LAN_RACETRACKING_CRON_001', 'Importuje záznamy z čítačky pre štart a cieľ, ak ešte neexistujú.');

define('LAN_RACETRACKING_PREF_001', 'Plugin aktívny');
define('LAN_RACETRACKING_PREF_001_HELP', "Keď je vypnutý, importný cron nebeží a manuálny import sa nedá spustiť.");
define('LAN_RACETRACKING_PREF_002', 'Cron zakázaný');
define('LAN_RACETRACKING_PREF_002_HELP', "Keď je zapnuté, cron sa potichu ukončí bez akcie, aj keď je plugin aktívny.");

/* ---- Manual import / refresh interval (admin_config renderHelp + prefs) --- */
define('LAN_RACETRACKING_PREF_REFRESH',      'Počet sekúnd pre manuálny import z čítačky');
define('LAN_RACETRACKING_PREF_REFRESH_HELP', '0 - import sa nespustí');
define('LAN_RACETRACKING_HELP_MANUAL_LINK',  'Link pre ručný import:');
define('LAN_RACETRACKING_HELP_MANUAL_BTN',   'Manuálny refresh');
define('LAN_RACETRACKING_HELP_INTERVAL_NOTE','Interval refreshu musí byť väčší ako 0');

/* ---- Test-connection / import output (import.php) ------------------------ */
define('LAN_RACETRACKING_USED_TABLE',         'Použitý názov tabuľky:');
define('LAN_RACETRACKING_TOTAL_RECORDS',      'Celkový počet záznamov:');
define('LAN_RACETRACKING_RECORDS_WITH',       'Záznamy s');
define('LAN_RACETRACKING_POINT',              'Bod:');
define('LAN_RACETRACKING_NOT_IMPORTED',       'neimportuje sa');
define('LAN_RACETRACKING_IMPORTED_FROM_FIELD','importuje sa z poľa');
define('LAN_RACETRACKING_INVALID_IDENTIFIER', 'Neplatný SQL identifikátor (povolené sú len písmená, číslice a podčiarkovník):');
define('LAN_RACETRACKING_DEPS_MISSING',       'Pre spustenie importu musia byť nainštalované pluginy timetracker a race.');

/* ---- Batch worker / cron (citacka.php, e_cron.php) ----------------------- */
define('LAN_RACETRACKING_IMPORT_DONE',   'Import dobehol');
define('LAN_RACETRACKING_NO_INTERVAL',   'Nie je nastavený interval refreshu');
define('LAN_RACETRACKING_NOT_ACTIVATED', 'Import nie je aktivovaný');
