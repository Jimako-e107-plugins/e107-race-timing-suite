<?php
/*
 * e107 website system
 *
 * race plugin - Italian admin language file (issue #37).
 *
 * Array-style LAN file (returns the terms) loaded via
 * e107::lan('racetrack', true, true). Array-style is used on purpose: e107\'s
 * includeLan() only applies the per-key English fallback for missing
 * translations when the language file RETURNS an array, so a partial Slovak
 * file (languages/Slovak/Slovak_admin.php) degrades cleanly to these English
 * strings instead of leaving constants undefined. This file is the canonical,
 * complete set; the Slovak file overrides per key.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Modulo di modifica del percorso.
    'LAN_ADMIN_RACE_001'      => 'URL SEF del percorso',
    'LAN_ADMIN_RACE_001_HELP' => 'Parte dell\'URL in cui vengono mostrati i dati della gara. Sito corrente + funzionalità + URL SEF.',
    'LAN_ADMIN_RACE_002'      => 'URL SEF dell\'archivio',
    'LAN_ADMIN_RACE_002_HELP' => 'Parte dell\'URL in cui viene mostrato l\'archivio delle gare. Sito corrente + URL SEF dell\'archivio. Può differire dall\'URL SEF della gara attiva e dovrebbe contenere l\'anno a causa delle gare che si ripetono negli anni successivi.',

    'LAN_ADMIN_RACE_003'      => 'Elenco percorsi',
    'LAN_ADMIN_RACE_004'      => 'Aggiungi percorso',

    'LAN_ADMIN_POINTS'        => 'Punti di controllo',
    'LAN_ADMIN_POINTS_ADD'    => 'Aggiungi punto di controllo',

    // --- Amministrazione archivio (race_archive) + pulsante "Archivia" nella riga della gara ------------
    'LAN_ADMIN_ARCHIVE'            => 'Archivio',
    'LAN_ADMIN_ARCHIVE_ADD'        => 'Aggiungi archivio',
    'LAN_ADMIN_ARCHIVE_TRACK'      => 'Percorso',
    'LAN_ADMIN_ARCHIVE_UNLINKED'  => 'Archivio scollegato',
    'LAN_ADMIN_ARCHIVE_CREATED'   => 'Creato',
    'LAN_ADMIN_ARCHIVE_UPDATED'   => 'Aggiornato',
    // Didascalie dei pulsanti.
    'LAN_ADMIN_ARCHIVE_ARCHIVOVAT' => 'Archivia',          
    'LAN_ADMIN_ARCHIVE_REGENERATE' => 'Rigenera',       
    'LAN_ADMIN_ARCHIVE_VIEW'       => 'Visualizza',             
    // Messaggi di generazione dei risultati / di controllo.
    'LAN_ADMIN_ARCHIVE_MSG_CREATED'  => 'Archivio creato',
    'LAN_ADMIN_ARCHIVE_MSG_UPDATED'  => 'Archivio aggiornato',
    'LAN_ADMIN_ARCHIVE_MSG_FAIL'     => 'Generazione dell\'archivio fallita',
    'LAN_ADMIN_ARCHIVE_MSG_NORACE'   => 'Gara non trovata',
    'LAN_ADMIN_ARCHIVE_MSG_NO_RR'    => 'Il plugin Racereports non è installato - l\'archivio non può essere generato.',
    'LAN_ADMIN_ARCHIVE_MSG_BAD_TOKEN' => 'Token di sicurezza non valido - la richiesta è stata ignorata.',
    'LAN_ADMIN_ARCHIVE_NOTE'         => 'Un archivio collegato (Percorso impostato) può essere rigenerato con il pulsante Rigenera. Visualizza mostra sempre l\'istantanea congelata e non rigenera mai. Scollegare il percorso (Percorso = Archivio scollegato) mantiene la riga ma nasconde il pulsante Rigenera.',

    // Etichetta della scheda del modulo di modifica del percorso (issue #34).
    'LAN_ADMIN_RACE_TAB_TRACK' => 'Percorso',
    // Etichetta della scheda Iscrizione Opt-in (mostrata solo quando racereg è installato).
    'LAN_ADMIN_RACE_TAB_REG'   => 'Iscrizione',

    // --- Configurazioni iscrizione + stringhe di amministrazione delle fasce di prezzo (issue #30) ----------
    // Flag di iscrizione al percorso + capacità.
    'LAN_ADMIN_RACE_CAPACITY'       => 'Capacità',
    'LAN_ADMIN_RACE_CAPACITY_HELP'  => 'Numero massimo di corridori nella lista di partenza. Viene ignorato quando la capacità illimitata è attiva.',
    'LAN_ADMIN_RACE_UNLIMITED'      => 'Capacità illimitata',
    'LAN_ADMIN_RACE_UNLIMITED_HELP' => 'Quando attiva, la capacità non viene controllata e chiunque viene inserito nella lista di partenza.',
    'LAN_ADMIN_RACE_APPROVAL'       => 'Richiede approvazione',
    'LAN_ADMIN_RACE_APPROVAL_HELP'  => 'Quando attiva, le registrazioni rimangono in attesa di approvazione e vengono inserite nella lista di partenza solo una volta approvate (non automaticamente in base alla capacità).',
    'LAN_ADMIN_RACE_CLOSED'         => 'Iscrizioni chiuse',
    'LAN_ADMIN_RACE_CLOSED_HELP'    => 'Quando attiva, non è possibile iscriversi a questo percorso.',

    // Fasce di prezzo scaglionate per data (tabella figlia).
    'LAN_ADMIN_PRICES'              => 'Fasce di prezzo',
    'LAN_ADMIN_PRICES_ADD'          => 'Aggiungi fascia di prezzo',
    'LAN_ADMIN_PRICE_TRACK'         => 'Percorso',
    'LAN_ADMIN_PRICE_VALUE'         => 'Prezzo',
    'LAN_ADMIN_PRICE_VALUE_HELP'    => 'Importo in EUR (es. 15.00).',
    'LAN_ADMIN_PRICE_FROM'          => 'Valido da',
    'LAN_ADMIN_PRICE_FROM_HELP'     => 'Data e ora a partire dalle quali si applica questo prezzo. Alla registrazione si applica lo scaglione con la data più recente che sia <= all\'ora corrente.',

    // --- Avvisi al momento del salvataggio per un percorso aperto (issue #47) --------------------
    'LAN_ADMIN_RACE_CAP_WARN'  => 'Questo percorso è aperto alle iscrizioni ma la sua capacità è 0 e non è impostato su capacità illimitata - nessuno può essere inserito nella lista di partenza. Imposta una capacità o attiva la capacità illimitata.',
    'LAN_ADMIN_RACE_FREE_WARN' => 'Questo percorso è aperto alle iscrizioni ma non ha fasce di prezzo associate - le registrazioni saranno gestite come gratuite ("bez poplatku"). Aggiungi una fascia di prezzo se si desidera addebitare una quota.',

    // --- Scorciatoie del menu amministrativo cross-plugin + avvisi di non installazione ----------
 
    // --- Pagina di aiuto per la configurazione del percorso (issue #47) ----------------------------------
    'LAN_ADMIN_RACE_TRACK_HELP' =>
        '<strong>Impostazioni di iscrizione per percorso</strong><br>'
        . '<strong>Capacità</strong> - numero massimo di corridori nella lista di partenza. '
        . '<strong>Capacità illimitata</strong> - quando attiva, la capacità viene ignorata e tutti vengono inseriti. '
        . 'Un percorso aperto (Iscrizioni chiuse = disattivato) con capacità 0 e non illimitata non consentirà l\'inserimento di nessuno - riceverai un avviso al momento del salvataggio.<br>'
        . '<strong>Richiede approvazione</strong> - le registrazioni rimangono in attesa dell\'organizzatore e vengono inserite solo dopo l\'approvazione (non automaticamente in base alla capacità).<br>'
        . '<strong>Iscrizioni chiuse</strong> - quando attiva, non è possibile registrarsi a questo percorso.<br>'
        . '<strong>Fasce di prezzo</strong> - quote scaglionate per data (menu Fasce di prezzo); si applica lo scaglione con la data "Valido da" più recente rispetto al momento attuale. '
        . 'Un percorso aperto senza fasce di prezzo viene considerato gratuito ("bez poplatku") - riceverai un avviso al momento del salvataggio.<br>'
        . '<em>I punti di controllo per la partenza e l\'arrivo devono obbligatoriamente utilizzare i codici start e finish, altrimenti verranno mostrati come normali punti di controllo intermedi.</em>',
);
