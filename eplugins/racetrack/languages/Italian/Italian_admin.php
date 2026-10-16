<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - languages/Italian/Italian_admin.php
 *
*/

if (!defined('e107_INIT')) { exit; }

return array(
    // Track edit form.
    'LAN_ADMIN_RACE_001'      => 'URL SEF del percorso',
    'LAN_ADMIN_RACE_001_HELP' => 'Parte dell\'URL in cui vengono mostrati i dati della gara. Sito corrente + funzionalità + URL SEF.',
    'LAN_ADMIN_RACE_002'      => 'URL SEF dell\'archivio',
    'LAN_ADMIN_RACE_002_HELP' => 'Parte dell\'URL in cui viene mostrato l\'archivio delle gare. Sito corrente + URL SEF dell\'archivio. Può differire dall\'URL SEF della gara attiva e dovrebbe contenere l\'anno a causa delle gare che si ripetono negli anni successivi.',

    'LAN_ADMIN_RACE_003'      => 'Elenco percorsi',
    'LAN_ADMIN_RACE_004'      => 'Aggiungi percorso',

    'LAN_ADMIN_POINTS'        => 'Punti di controllo',
    'LAN_ADMIN_POINTS_ADD'    => 'Aggiungi punto di controllo',

    // --- Archive screen + Track list "Archive" button ------------------------
    'LAN_ADMIN_ARCHIVE'            => 'Archivio',
    'LAN_ADMIN_ARCHIVE_ADD'        => 'Aggiungi archivio',
    'LAN_ADMIN_ARCHIVE_TRACK'      => 'Percorso',
    'LAN_ADMIN_ARCHIVE_UNLINKED'  => 'Archivio scollegato',
    'LAN_ADMIN_ARCHIVE_CREATED'   => 'Creato',
    'LAN_ADMIN_ARCHIVE_UPDATED'   => 'Aggiornato',
    // Button captions.
    'LAN_ADMIN_ARCHIVE_ARCHIVOVAT' => 'Archivia',          
    'LAN_ADMIN_ARCHIVE_REGENERATE' => 'Rigenera',       
    'LAN_ADMIN_ARCHIVE_VIEW'       => 'Visualizza',             
    // Generate result / guard messages.
    'LAN_ADMIN_ARCHIVE_MSG_CREATED'  => 'Archivio creato',
    'LAN_ADMIN_ARCHIVE_MSG_UPDATED'  => 'Archivio aggiornato',
    'LAN_ADMIN_ARCHIVE_MSG_FAIL'     => 'Generazione dell\'archivio fallita',
    'LAN_ADMIN_ARCHIVE_MSG_NORACE'   => 'Gara non trovata',
    'LAN_ADMIN_ARCHIVE_MSG_NO_RR'    => 'Il plugin Racereports non è installato - l\'archivio non può essere generato.',
    'LAN_ADMIN_ARCHIVE_MSG_BAD_TOKEN' => 'Token di sicurezza non valido - la richiesta è stata ignorata.',
    'LAN_ADMIN_ARCHIVE_NOTE'         => 'Un archivio collegato (Percorso impostato) può essere rigenerato con il pulsante Rigenera. Visualizza mostra sempre l\'istantanea congelata e non rigenera mai. Scollegare il percorso (Percorso = Archivio scollegato) mantiene la riga ma nasconde il pulsante Rigenera.',

    // Track edit form tab label.
    'LAN_ADMIN_RACE_TAB_TRACK' => 'Percorso',
    // Registration tab (shown only when racereg is installed).
    'LAN_ADMIN_RACE_TAB_REG'   => 'Iscrizione',

    // --- Registration settings + price tiers ---------------------------------
    // Track registration flags + capacity.
    'LAN_ADMIN_RACE_CAPACITY'       => 'Capacità',
    'LAN_ADMIN_RACE_CAPACITY_HELP'  => 'Numero massimo di corridori nella lista di partenza. Viene ignorato quando la capacità illimitata è attiva.',
    'LAN_ADMIN_RACE_UNLIMITED'      => 'Capacità illimitata',
    'LAN_ADMIN_RACE_UNLIMITED_HELP' => 'Quando attiva, la capacità non viene controllata e chiunque viene inserito nella lista di partenza.',
    'LAN_ADMIN_RACE_APPROVAL'       => 'Richiede approvazione',
    'LAN_ADMIN_RACE_APPROVAL_HELP'  => 'Quando attiva, le registrazioni rimangono in attesa di approvazione e vengono inserite nella lista di partenza solo una volta approvate (non automaticamente in base alla capacità).',
    'LAN_ADMIN_RACE_CLOSED'         => 'Iscrizioni chiuse',
    'LAN_ADMIN_RACE_CLOSED_HELP'    => 'Quando attiva, non è possibile iscriversi a questo percorso.',

    // Date-tiered price tiers.
    'LAN_ADMIN_PRICES'              => 'Fasce di prezzo',
    'LAN_ADMIN_PRICES_ADD'          => 'Aggiungi fascia di prezzo',
    'LAN_ADMIN_PRICE_TRACK'         => 'Percorso',
    'LAN_ADMIN_PRICE_VALUE'         => 'Prezzo',
    'LAN_ADMIN_PRICE_VALUE_HELP'    => 'Importo in EUR (es. 15.00).',
    'LAN_ADMIN_PRICE_FROM'          => 'Valido da',
    'LAN_ADMIN_PRICE_FROM_HELP'     => 'Data e ora a partire dalle quali si applica questo prezzo. Alla registrazione si applica lo scaglione con la data più recente che sia <= all\'ora corrente.',

    // --- Save-time warnings for an open track -------------------------------
    'LAN_ADMIN_RACE_CAP_WARN'  => 'Questo percorso è aperto alle iscrizioni ma la sua capacità è 0 e non è impostato su capacità illimitata - nessuno può essere inserito nella lista di partenza. Imposta una capacità o attiva la capacità illimitata.',
    'LAN_ADMIN_RACE_FREE_WARN' => 'Questo percorso è aperto alle iscrizioni ma non ha fasce di prezzo associate - le registrazioni saranno gestite come gratuite ("bez poplatku"). Aggiungi una fascia di prezzo se si desidera addebitare una quota.',

 
    // --- Track list help panel ----------------------------------------------
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
