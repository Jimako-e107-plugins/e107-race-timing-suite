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

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_RACETRACKING_TESTACCESS' => 'Test prístupu k databáze',
    'LAN_RACETRACKING_002' => 'Nastaviť ',
    'LAN_RACETRACKING_CONFIG' => 'Hlavné nastavenie',
    'LAN_RACETRACKING_SERVER' => 'Server:',
    'LAN_RACETRACKING_USERNAME' => 'Používateľské meno:',
    'LAN_RACETRACKING_PASSWORD' => 'Heslo:',
    'LAN_RACETRACKING_DATABASE' => 'Databáza:',
    'LAN_RACETRACKING_TABLE' => 'Tabuľka:',
    'LAN_RACETRACKING_PREFIX' => 'Prefix tabuľky:',
    'LAN_RACETRACKING_PORT' => "Port: ",
    'LAN_RACETRACKING_FIELDNAME' => 'Pole mena:',
    'LAN_RACETRACKING_FIELDSTART' => 'Pole času štartu:',
    'LAN_RACETRACKING_FIELDFINISH' => 'Pole času cieľa:',
    'LAN_RACETRACKING_FIELDNUMBER' => 'Pole štartového čísla:',

    'LAN_RACETRACKING_HELP_001' => "Tu nastavte prístup k externej databáze. Spustite Test pripojenia. Ak zlyhá a ste si istý nastavením, skontrolujte hodnotu hesla. Skúste ho zadať ručne a nepoužívajte html entity. ",

    'LAN_RACETRACKING_HELP_002' => "Prístup k nastaveniu DB má len hlavný administrátor. Ako vlastník pluginu ho môžete iba vypnúť a zakázať obsah cronu (cron stále beží, ale nič nerobí). Hlavný administrátor môže akciu cronu spustiť ručne zo Správcu plánovaných úloh. ",


    'LAN_RACETRACKING_CRON_001' => 'Importuje záznamy z čítačky pre štart a cieľ, ak ešte neexistujú.',

    'LAN_RACETRACKING_PREF_001' => 'Plugin aktívny',
    'LAN_RACETRACKING_PREF_001_HELP' => "Keď je vypnutý, importný cron nebeží a manuálny import sa nedá spustiť.",
    'LAN_RACETRACKING_PREF_002' => 'Cron zakázaný',
    'LAN_RACETRACKING_PREF_002_HELP' => "Keď je zapnuté, cron sa potichu ukončí bez akcie, aj keď je plugin aktívny.",

    /* ---- Manual import / refresh interval (admin_config renderHelp + prefs) --- */
    'LAN_RACETRACKING_PREF_REFRESH' => 'Počet sekúnd pre manuálny import z čítačky',
    'LAN_RACETRACKING_PREF_REFRESH_HELP' => '0 - import sa nespustí',
    'LAN_RACETRACKING_HELP_MANUAL_LINK' => 'Link pre ručný import:',
    'LAN_RACETRACKING_HELP_MANUAL_BTN' => 'Manuálny refresh',
    'LAN_RACETRACKING_HELP_INTERVAL_NOTE' => 'Interval refreshu musí byť väčší ako 0',

    /* ---- Test-connection / import output (import.php) ------------------------ */
    'LAN_RACETRACKING_USED_TABLE' => 'Použitý názov tabuľky:',
    'LAN_RACETRACKING_TOTAL_RECORDS' => 'Celkový počet záznamov:',
    'LAN_RACETRACKING_RECORDS_WITH' => 'Záznamy s',
    'LAN_RACETRACKING_POINT' => 'Bod:',
    'LAN_RACETRACKING_NOT_IMPORTED' => 'neimportuje sa',
    'LAN_RACETRACKING_IMPORTED_FROM_FIELD' => 'importuje sa z poľa',
    'LAN_RACETRACKING_INVALID_IDENTIFIER' => 'Neplatný SQL identifikátor (povolené sú len písmená, číslice a podčiarkovník):',
    'LAN_RACETRACKING_DEPS_MISSING' => 'Pre spustenie importu musia byť nainštalované pluginy timetracker a race.',

    /* ---- Batch worker / cron (citacka.php, e_cron.php) ----------------------- */
    'LAN_RACETRACKING_IMPORT_DONE' => 'Import dobehol',
    'LAN_RACETRACKING_NO_INTERVAL' => 'Nie je nastavený interval refreshu',
    'LAN_RACETRACKING_NOT_ACTIVATED' => 'Import nie je aktivovaný',
);
