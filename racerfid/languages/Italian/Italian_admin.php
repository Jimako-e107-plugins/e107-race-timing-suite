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
 *     RFID import plugin (folder/id: racerfid) - Italian admin language file
 *
 *     Imports RFID chip-reader records from an external database into
 *     timetracker's race_time table.
 *
 *     @package    e107_plugins
 *     @subpackage    racerfid
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_RACETRACKING_TESTACCESS' => "Testa l'accesso al database",
    'LAN_RACETRACKING_002' => "Configura ",
    'LAN_RACETRACKING_CONFIG' => "Configurazione principale",
    'LAN_RACETRACKING_SERVER' => "Server:",
    'LAN_RACETRACKING_USERNAME' => "Nome utente:",
    'LAN_RACETRACKING_PASSWORD' => "Password:",
    'LAN_RACETRACKING_DATABASE' => "Database:",
    'LAN_RACETRACKING_TABLE' => "Tabella:",
    'LAN_RACETRACKING_PREFIX' => "Prefisso tabella:",
    'LAN_RACETRACKING_PORT' => "Porta: ",
    'LAN_RACETRACKING_FIELDNAME' => "Campo Nome:",
    'LAN_RACETRACKING_FIELDSTART' => "Campo Ora di Partenza:",
    'LAN_RACETRACKING_FIELDFINISH' => "Campo Ora di Arrivo:",
    'LAN_RACETRACKING_FIELDNUMBER' => "Campo Numero Pettorale:",

    'LAN_RACETRACKING_HELP_001' => "Configura qui l'accesso al database esterno. Esegui il test di connessione. Se fallisce e sei sicuro delle impostazioni, controlla il valore della password. Prova ad accedere manualmente e non utilizzare entità HTML.",
    'LAN_RACETRACKING_HELP_002' => "Solo l'amministratore principale ha accesso alla configurazione del database. In qualità di proprietario del plugin puoi solo disattivarlo e disabilitare il contenuto del cron (il cron rimarrà in esecuzione ma senza eseguire alcuna operazione). L'amministratore principale può avviare manualmente l'azione del cron dalla Gestione delle Attività Pianificate.",

    'LAN_RACETRACKING_CRON_001' => "Importa i record dal lettore per la partenza e l'arrivo qualora non siano già esistenti.",

    'LAN_RACETRACKING_PREF_001' => "Plugin Attivo",
    'LAN_RACETRACKING_PREF_001_HELP' => "Quando disattivato, il cron di importazione non viene eseguito e l'importazione manuale non può essere avviata.",
    'LAN_RACETRACKING_PREF_002' => "Cron disattivato",
    'LAN_RACETRACKING_PREF_002_HELP' => "Quando attivato, il cron termina silenziosamente senza compiere alcuna operazione, anche se il plugin risulta attivo.",

    /* ---- Manual import / refresh interval (admin_config renderHelp + prefs) --- */
    'LAN_RACETRACKING_PREF_REFRESH' => "Secondi per l'importazione manuale dal lettore",
    'LAN_RACETRACKING_PREF_REFRESH_HELP' => "0 - l'importazione non verrà eseguita",
    'LAN_RACETRACKING_HELP_MANUAL_LINK' => "Link per l'importazione manuale:",
    'LAN_RACETRACKING_HELP_MANUAL_BTN' => "Aggiornamento manuale",
    'LAN_RACETRACKING_HELP_INTERVAL_NOTE' => "L'intervallo di aggiornamento deve essere maggiore di 0",

    /* ---- Test-connection / import output (import.php) ------------------------ */
    'LAN_RACETRACKING_USED_TABLE' => "Nome della tabella utilizzata:",
    'LAN_RACETRACKING_TOTAL_RECORDS' => "Record totali:",
    'LAN_RACETRACKING_RECORDS_WITH' => "Record con",
    'LAN_RACETRACKING_POINT' => "Punto:",
    'LAN_RACETRACKING_NOT_IMPORTED' => "non importato",
    'LAN_RACETRACKING_IMPORTED_FROM_FIELD' => "importato dal campo",
    'LAN_RACETRACKING_INVALID_IDENTIFIER' => "Identificatore SQL non valido (sono ammessi solo lettere, numeri e trattino basso):",
    'LAN_RACETRACKING_DEPS_MISSING' => "I plugin timetracker e race devono essere installati affinché l'importazione possa essere eseguita.",

    /* ---- Batch worker / cron (citacka.php, e_cron.php) ----------------------- */
    'LAN_RACETRACKING_IMPORT_DONE' => "Importazione completata",
    'LAN_RACETRACKING_NO_INTERVAL' => "L'intervallo di aggiornamento non è impostato",
    'LAN_RACETRACKING_NOT_ACTIVATED' => "L'importazione non è attivata",
);
