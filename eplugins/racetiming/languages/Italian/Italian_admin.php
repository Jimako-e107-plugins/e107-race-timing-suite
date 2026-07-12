<?php
/*
 * e107 website system
 *
 * racetiming plugin - Italian admin language file.
 *
 * Array-style LAN file loaded via e107::lan('racetiming', true, true).
 * Array-style is used on purpose: e107's includeLan() only applies the per-key
 * English fallback for missing translations when the language file RETURNS an
 * array, so a partial Slovak file (languages/Slovak/Slovak_admin.php) degrades
 * cleanly to these English strings instead of leaving constants undefined. This
 * file is the canonical, complete set; the Slovak file overrides per key.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Schermata segnaposto amministratore (admin/admin_config.php).
    'LAN_ADMIN_RACETIMING_001' => 'Cronometraggio gara',
    'LAN_ADMIN_RACETIMING_002' => 'Struttura di cronometraggio gara - questo plugin gestisce la tabella dei punti di controllo race_time. Il motore di calcolo dei tempi non è ancora implementato.',
    // Etichetta dei permessi amministratore.
    'LAN_ADMIN_RACETIMING_003' => 'Amministrazione cronometraggio gara',

    // --- race_time CRUD admin (admin/admin_config.php) ---
    // Didascalie del menu dispatcher.
    'LAN_ADMIN_RACETIMING_010' => 'Inserimenti orari',
    'LAN_ADMIN_RACETIMING_011' => 'Aggiungi inserimento orario',
    // Etichette dei campi race_time (titoli + aiuto).
    'LAN_ADMIN_RACETIMING_020' => 'Punto di controllo / Checkpoint',
    'LAN_ADMIN_RACETIMING_021' => 'Seleziona punto di controllo / checkpoint',
    'LAN_ADMIN_RACETIMING_022' => 'Pettorale / Numero di partenza',
    'LAN_ADMIN_RACETIMING_023' => 'Inserisci il numero di pettorale/partenza (compresi gli zeri iniziali)',
    'LAN_ADMIN_RACETIMING_024' => 'Tempo misurato',
    'LAN_ADMIN_RACETIMING_025' => 'Tempo misurato al punto di controllo',
    'LAN_ADMIN_RACETIMING_026' => 'Stato / Stato di arrivo',
    'LAN_ADMIN_RACETIMING_027' => 'DNF = Ritirato, DSQ = Squalificato, DNS = Non partito',
    'LAN_ADMIN_RACETIMING_028' => 'Creato',
    'LAN_ADMIN_RACETIMING_029' => 'Aggiornato',

    // --- generazione massiva partenze (admin/admin_generujstart.php) ---
    // Spostato da timetracker (ex LAN_TR_*). 040 = etichetta scheda preferenze,
    // 041/042 = titolo/aiuto preferenza `starttime`, 043 = menu pagina generazione,
    // 044-051 = modulo di generazione + stringhe dei risultati, 052 = menu pagina preferenze,
    // 053 = avviso mostrato quando il modulo viene inviato senza alcun percorso selezionato.
    'LAN_ADMIN_RACETIMING_040' => 'Generazione partenze',
    'LAN_ADMIN_RACETIMING_041' => 'Ora di partenza predefinita',
    'LAN_ADMIN_RACETIMING_042' => 'Utilizzato come valore predefinito durante la generazione della partenza.',
    'LAN_ADMIN_RACETIMING_043' => 'Genera partenza',
    'LAN_ADMIN_RACETIMING_044' => 'Seleziona gare e orario',
    'LAN_ADMIN_RACETIMING_045' => 'Tempo misurato',
    'LAN_ADMIN_RACETIMING_046' => 'Imposta ora corrente',
    'LAN_ADMIN_RACETIMING_047' => 'Genera orari di partenza',
    'LAN_ADMIN_RACETIMING_048' => 'Generazione ID gara {ID}',
    'LAN_ADMIN_RACETIMING_049' => 'Per questo concorrente è già stata generata la partenza',
    'LAN_ADMIN_RACETIMING_050' => 'Partenza generata',
    'LAN_ADMIN_RACETIMING_051' => 'Il concorrente non è partito',
    'LAN_ADMIN_RACETIMING_052' => 'Impostazione ora di partenza',
    'LAN_ADMIN_RACETIMING_053' => 'Nessun percorso selezionato',
);
