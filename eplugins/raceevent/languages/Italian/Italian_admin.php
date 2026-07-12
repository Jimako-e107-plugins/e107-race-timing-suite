<?php
/*
 * e107 website system
 *
 * raceevent base plugin - Italian admin strings.
 */

if (!defined('e107_INIT')) { exit; }

return array(

    /* ---- Admin menu ---------------------------------------------------------- */
    'LAN_RACEEVENT_CONFIG' => "Configurazione evento",

    /* ---- Cross-plugin admin-menu shortcuts (raceevent_admin_links helper) ----- */
    'LAN_RACEEVENT_LINK_EVENT' => "» Evento",
    'LAN_RACEEVENT_LINK_TRACKS' => "» Percorsi",
    'LAN_RACEEVENT_LINK_ARCHIVE' => "» Archivio",
    'LAN_RACEEVENT_LINK_RACERS' => "» Corridori",
    'LAN_RACEEVENT_LINK_CATEGORIES' => "» Categorie",
    'LAN_RACEEVENT_LINK_TERMINOVKA' => "» Terminovka.sk",
    'LAN_RACEEVENT_LINK_REGISTRATION' => "» Registrazione",
    'LAN_RACEEVENT_LINK_RFID' => "» Importazione RFID",
    'LAN_RACEEVENT_LINK_TIMING' => "» Cronometraggio",
    'LAN_RACEEVENT_LINK_REPORTS' => "» Report",

    'LAN_RACEEVENT_TAB_EVENT' => "Evento",
    'LAN_RACEEVENT_TAB_REGISTRATION' => "Registrazione",

    /* ---- Event fields (plugin prefs) ----------------------------------------- */
    'LAN_RACEEVENT_NAME' => "Nome evento",
    'LAN_RACEEVENT_DATE' => "Data evento",
    'LAN_RACEEVENT_CITY' => "Città / paese",
    'LAN_RACEEVENT_LOCATION' => "Luogo / base",
    'LAN_RACEEVENT_DESCRIPTION' => "Descrizione",
    'LAN_RACEEVENT_ORGANIZER' => "Organizzatore",

    /* ---- Event flags (plugin prefs) ------------------------------------------ */
    'LAN_RACEEVENT_IS_CHARITY' => "Evento di beneficenza",
    'LAN_RACEEVENT_IS_CHILDREN_RUNS' => "Corse per bambini incluse",
    'LAN_RACEEVENT_IS_DOG_ALLOWED' => "Partecipazione con cane consentita",

    /* ---- Registration window + payee (plugin prefs) -------------------------- */
    'LAN_RACEEVENT_REG_START' => "Apertura iscrizioni",
    'LAN_RACEEVENT_REG_START_HELP' => "Le iscrizioni sono accettate da questo momento in poi. Lasciare vuoto (0) per nessun limite minimo. Se vengono impostati entrambi i limiti, l'apertura deve precedere la chiusura.",
    'LAN_RACEEVENT_REG_END' => "Chiusura iscrizioni",
    'LAN_RACEEVENT_REG_END_HELP' => "Le iscrizioni sono accettate fino a questo momento. Lasciare vuoto (0) per nessun limite massimo. Se vengono impostati entrambi i limiti, la chiusura deve seguire l'apertura.",
    'LAN_RACEEVENT_PAYEE_IBAN' => "IBAN del beneficiario",
    'LAN_RACEEVENT_PAYEE_IBAN_HELP' => "Conto bancario (IBAN) mostrato come destinazione del pagamento nella pagina di conferma dell'iscrizione. Gli spazi vengono rimossi automaticamente. Richiesto (insieme al nome del beneficiario) per il codice QR di pagamento.",
    'LAN_RACEEVENT_PAYEE_NAME' => "Nome del beneficiario",
    'LAN_RACEEVENT_PAYEE_NAME_HELP' => "Nome del titolare del conto / beneficiario mostrato con i dettagli del pagamento. Richiesto ogni volta che viene impostato un IBAN: PAY by square non può generare il codice QR senza di esso.",
    'LAN_RACEEVENT_PAYEE_SWIFT' => "SWIFT / BIC del beneficiario",
    'LAN_RACEEVENT_PAYEE_SWIFT_HELP' => "Identificativo bancario opzionale (BIC), di 8 o 11 caratteri. Gli spazi vengono rimossi automaticamente.",
    'LAN_RACEEVENT_IBAN_WARN' => "L'IBAN è stato salvato ma non sembra valido (formato / checksum). Si prega di ricontrollarlo.",
    'LAN_RACEEVENT_SWIFT_WARN' => "Lo SWIFT / BIC è stato salvato ma non sembra valido (previsti 8 o 11 caratteri). Si prega di ricontrollarlo.",
    'LAN_RACEEVENT_PAYEE_NAME_REQUIRED' => "Il nome del beneficiario è richiesto quando viene impostato un IBAN, altrimenti il codice QR di pagamento non può essere generato. La modifica non è stata salvata - aggiungi il nome del beneficiario e salva di nuovo.",
    'LAN_RACEEVENT_REG_WINDOW_INVALID' => "La registrazione non può chiudersi prima (o contemporaneamente) della sua apertura. La modifica non è stata salvata - imposta l'orario di chiusura successivamente a quello di apertura.",
    'LAN_RACEEVENT_PAYEE_INCOMPLETE_WARN' => "La finestra di iscrizione è impostata ma il blocco del beneficiario è incompleto (IBAN o nome del beneficiario mancante). Il codice QR di pagamento e le istruzioni non funzioneranno finché non saranno compilati entrambi.",

    /* ---- Help ---------------------------------------------------------------- */
    'LAN_RACEEVENT_CONFIG_HELP' => "Configura l'evento. Questa è un'installazione a evento singolo: l'evento viene memorizzato come preferenze del plugin (non righe del database) e condiviso con il resto della suite di cronometraggio delle gare tramite e107::getPlugConfig('raceevent')."
        . "<hr><strong>Pagamento / QR (campi richiesti)</strong><br>"
        . "Il codice QR di pagamento (PAY by square) richiede SIA l'IBAN del beneficiario sia il Nome del beneficiario. Il nome del beneficiario è obbligatorio - bysquare non può codificare il QR senza di esso, pertanto un IBAN impostato senza un nome viene rifiutato al momento del salvataggio. Lo SWIFT / BIC è opzionale."
        . "<hr><strong>Finestra di iscrizione</strong><br>"
        . "Le registrazioni sono accettate solo tra l'Apertura iscrizioni e la Chiusura iscrizioni. Ciascun lato può essere lasciato vuoto (0) per nessun limite. Se sono impostati entrambi, l'apertura deve essere tassativamente precedente alla chiusura - altrimenti il salvataggio viene rifiutato. Se viene impostata una finestra di iscrizione ma il blocco del beneficiario è incompleto, verrai avvisato che i pagamenti / il QR non funzioneranno.",

    /* --- Maintenance page (udrzba) --- */
    'LAN_TR_UDRZBA_ARCHIVE_WARN_TITLE' => "Controlla l'archivio prima di iniziare una nuova stagione!",
    'LAN_TR_UDRZBA_ARCHIVE_WARN_LINKED' => "[x] i record dell'archivio sono ancora collegati a un percorso.",
    'LAN_TR_UDRZBA_ARCHIVE_WARN_ADVICE' => "Si consiglia di scollegarli tutti, altrimenti potrebbero essere aggiornati o impedire la cancellazione delle gare.",

    'LAN_TR_UDRZBA_EXECUTE' => "Esegui",
    'LAN_TR_UDRZBA_EDIT_PREFS' => "Modifica preferenze",

    'LAN_TR_UDRZBA_ACTIVE_HEADING' => "Manutenzione - tabelle attive",
    'LAN_TR_UDRZBA_CLEAN' => "Svuota",
    'LAN_TR_UDRZBA_ITEM' => "Elemento",
    'LAN_TR_UDRZBA_COUNT' => "Conteggio record",

    'LAN_TR_UDRZBA_LEGACY_HEADING' => "Tabelle legacy / obsolete",
    'LAN_TR_UDRZBA_DROP' => "ELIMINA (DROP)",
    'LAN_TR_UDRZBA_TABLE_DESC' => "Tabella / descrizione",
    'LAN_TR_UDRZBA_STATUS' => "Stato",
    'LAN_TR_UDRZBA_NOTEXIST' => "non esiste",
    'LAN_TR_UDRZBA_EMPTY' => "vuota",
    'LAN_TR_UDRZBA_ROWS' => "righe",

    'LAN_TR_UDRZBA_PLUGINS_HEADING' => "Controllo plugin richiesti",
    'LAN_TR_UDRZBA_PLUGIN' => "Plugin",
    'LAN_TR_UDRZBA_DESC' => "Descrizione",
    'LAN_TR_UDRZBA_MANDATORY' => "Obbligatorio",
    'LAN_TR_UDRZBA_PREFS' => "Preferenze",
    'LAN_TR_UDRZBA_PLUGIN_MISSING' => "Mancante in eplugins",
    'LAN_TR_UDRZBA_PLUGIN_DISABLED' => "Disattivato",
    'LAN_TR_UDRZBA_PLUGIN_ACTIVE' => "Attivo",
    'LAN_TR_UDRZBA_YES' => "SÌ",
    'LAN_TR_UDRZBA_NO' => "No",
    'LAN_TR_UDRZBA_NONE' => "nessuno",

    /* --- Maintenance action messages --- */
    'LAN_TR_UDRZBA_MSG_BAD_TOKEN' => "Token di sicurezza non valido. Azione interrotta.",
    'LAN_TR_UDRZBA_MSG_CLEANED' => "Tabella [x] svuotata ([y] righe).",
    'LAN_TR_UDRZBA_MSG_LEGACY_CLEANED' => "Tabella legacy [x] svuotata.",
    'LAN_TR_UDRZBA_MSG_LEGACY_DROPPED' => "Tabella legacy [x] rimossa (DROP).",
    'LAN_TR_UDRZBA_MSG_INVALID_TABLE' => "Tabella non valida per l'eliminazione.",
    'LAN_TR_UDRZBA_MSG_TRACKS_BLOCKED' => "I percorsi non possono essere svuotati mentre altre tabelle contengono ancora dati. Svuota prima le altre tabelle.",
    'LAN_TR_UDRZBA_TRACKS_HINT' => "svuota prima le altre tabelle",

    /* --- Maintenance table labels --- */
    'LAN_TR_TBL_RACES' => "Percorsi",
    'LAN_TR_TBL_POINTS' => "Punti di controllo",
    'LAN_TR_TBL_CATEGORIES' => "Categorie",
    'LAN_TR_TBL_RACERS' => "Corridori / numeri di partenza",
    'LAN_TR_TBL_RESULTS' => "Risultati",
    'LAN_TR_TBL_TIMES' => "Tempi ai punti di controllo",
    'LAN_TR_TBL_READER' => "Lettore",
    'LAN_TR_TBL_OLD_RACERS' => "Vecchio elenco corridori",

    /* --- Required plugins (titles / descriptions) --- */
    'LAN_TR_PLUG_RACEREPORTS' => "Racereports (elenchi d'arrivo)",
    'LAN_TR_PLUG_RACEREPORTS_DESC' => "Plugin principale per la misurazione del tempo, punti di controllo e risultati.",
    'LAN_TR_PLUG_RACE' => "Race (gare di base)",
    'LAN_TR_PLUG_RACE_DESC' => "Plugin di base per la definizione di gare e percorsi.",
    'LAN_TR_PLUG_RACERS' => "Racers (concorrenti)",
    'LAN_TR_PLUG_RACERS_DESC' => "Gestione di corridori, categorie e numeri di partenza.",
    'LAN_TR_PLUG_RACETRACKING' => "Importazione RFID (lettore)",
    'LAN_TR_PLUG_RACETRACKING_DESC' => "Importa i tempi dal lettore RFID.",
    'LAN_TR_PLUG_TERMINOVKA' => "Esportazione Terminovka.sk",
    'LAN_TR_PLUG_TERMINOVKA_DESC' => "Esportazione opzionale dei risultati su terminovka.sk.",
    'LAN_TR_PLUG_REGISTRACIA' => "Registrazione",
    'LAN_TR_PLUG_REGISTRACIA_DESC' => "Iscrizione alla gata (pianificata).",

    /* --- Navigation check (checklinks) --- */
    'LAN_RACEEVENT_CHECKLINKS' => "Controllo navigazione",
    'LAN_RACEEVENT_CHECKLINKS_HELP' => "Elenca i collegamenti di navigazione che richiamano una funzione del plugin (link_function = plugin::method). Segnala i collegamenti il cui plugin o la cui funzione non esistono più, e i collegamenti il cui proprietario non corrisponde al plugin chiamato. I collegamenti interrotti possono essere nascosti (impostati sulla classe utente \"nobody\"); modificali o eliminali in Collegamenti del Sito.",
    'LAN_RACEEVENT_CL_HEADING' => "Collegamenti di navigazione guidati da funzioni",
    'LAN_RACEEVENT_CL_COL_LINK' => "Collegamento",
    'LAN_RACEEVENT_CL_COL_FUNCTION' => "Funzione",
    'LAN_RACEEVENT_CL_COL_PLUGIN' => "Plugin",
    'LAN_RACEEVENT_CL_COL_METHOD' => "Metodo",
    'LAN_RACEEVENT_CL_COL_OWNER' => "Proprietario",
    'LAN_RACEEVENT_CL_COL_STATUS' => "Stato",
    'LAN_RACEEVENT_CL_COL_ACTION' => "Azione",
    'LAN_RACEEVENT_CL_OK' => "OK",
    'LAN_RACEEVENT_CL_BROKEN_PLUGIN' => "Plugin mancante",
    'LAN_RACEEVENT_CL_BROKEN_METHOD' => "Funzione mancante",
    'LAN_RACEEVENT_CL_MALFORMED' => "Funzione malformata",
    'LAN_RACEEVENT_CL_OWNER_MISMATCH' => "Incongruenza proprietario / funzione",
    'LAN_RACEEVENT_CL_HIDE' => "Nascondi",
    'LAN_RACEEVENT_CL_EDIT' => "Modifica",
    'LAN_RACEEVENT_CL_EXECUTE' => "Nascondi selezionati",
    'LAN_RACEEVENT_CL_ALREADY_HIDDEN' => "già nascosto",
    'LAN_RACEEVENT_CL_NONE' => "Nessun collegamento di navigazione guidato da funzioni trovato.",
    'LAN_RACEEVENT_CL_MSG_BAD_TOKEN' => "Token di sicurezza non valido. Azione interrotta.",
    'LAN_RACEEVENT_CL_MSG_HIDDEN' => "Collegamento [x] nascosto (impostato su nobody).",

    /* --- Event overview (Prehľad preteku) admin screen --- */
    'LAN_RACEEVENT_OV_MENU' => "Panoramica evento",
    'LAN_RACEEVENT_OV_HELP' => "Una directory incrociata della suite contenente i collegamenti a ogni report di gara (lo mesmo output della pagina pubblica \"preteky\"). Per ogni collegamento mostra se il file di destinazione del report esiste sul disco: verde = fatto, rosso = mancante o la rotta non è registrata (es. il report di partenza finché non viene generato). Sola lettura.",
);
