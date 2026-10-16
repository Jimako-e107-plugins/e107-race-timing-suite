<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - languages/Italian/Italian_front.php
 *
*/

if (!defined('e107_INIT')) { exit; }

return array(
    // Classifiche in tempo reale (online).
    'LAN_RACEREPORTS_LIVE_STATE'     => 'classifiche attuali',
    'LAN_RACEREPORTS_ALL_CATEGORIES' => 'tutte le categorie',

    // Online report - admin-only auto-refresh help note. The BODY is trusted HTML,
    // echoed raw (not through toHTML).
    'LAN_RACEREPORTS_HELP_REFRESH_TITLE' => 'Risultati online — aggiornamento automatico',
    'LAN_RACEREPORTS_HELP_REFRESH_BODY'  =>
        '<p>Le classifiche possono aggiornarsi automaticamente in modo che lo speaker o uno spettatore possano vedere'
        . ' l\'ordine attuale senza dover ricaricare manualmente la pagina.</p>'
        . '<p><strong>Intervallo predefinito (impostazioni del plugin):</strong> In'
        . ' "Online – intervallo di aggiornamento automatico" inserisci il numero di secondi. Si applica'
        . ' a tutte le finestre online tranne nei casi in cui l\'indirizzo specifichi diversamente. Ad esempio,'
        . ' 30 = aggiornamento ogni 30 secondi. 0 = nessun aggiornamento automatico.</p>'
        . '<p><strong>Un intervallo diverso per una finestra specifica (parametro dell\'indirizzo):</strong>'
        . ' Aggiungendo <code>?refresh=</code> seguito dal numero di secondi all\'indirizzo, imposti'
        . ' l\'intervallo solo per questa finestra (es. <code>...?refresh=10</code>). Il parametro dell\'indirizzo'
        . ' ha la precedenza — l\'impostazione del plugin verrà quindi completamente ignorata in questo caso.</p>'
        . '<ul>'
        . '<li><code>?refresh=10</code> → questa finestra si aggiorna ogni 10 secondi.</li>'
        . '<li><code>?refresh=0</code> → questa finestra non si aggiorna affatto, anche quando è impostato'
        . ' un intervallo nelle impostazioni (utile es. per "congelare" una finestra che si desidera esaminare'
        . ' con calma).</li>'
        . '<li>Si applica solo alla finestra/indirizzo specificati, non modifica l\'impostazione'
        . ' per gli altri.</li>'
        . '</ul>'
        . '<p>Uso tipico: il proiettore sulla linea del traguardo veloce (<code>?refresh=10</code>), uno'
        . ' schermo interno più lento (<code>?refresh=60</code>), una finestra di controllo congelata'
        . ' (<code>?refresh=0</code>).</p>',
    // Avvisi vuoti / non trovati.
    'LAN_RACEREPORTS_NO_RACE'        => 'Nessuna gara selezionata.',
    'LAN_RACEREPORTS_NO_POINT'       => 'Nessun punto di controllo selezionato.',

    // Resoconto Traguardo (elenco dei risultati post-gara) - suffisso del titolo.
    'LAN_RACEREPORTS_FINISH_TITLE'   => 'Risultati',

    // Resoconto Partenza (elenco delle posizioni al punto di partenza) - suffisso del titolo.
    'LAN_RACEREPORTS_START_TITLE'    => 'Elenco di partenza',

    // Resoconto personalizzato / segmento (intermedio tra due punti) - il titolo della pagina nomina'
    // entrambi i punti: "Od - <A> / Do - <B>" (da / a).
    'LAN_RACEREPORTS_OD'             => 'Da (Od)',
    'LAN_RACEREPORTS_DO'             => 'A (Do)',

    // SUT report; the COL_* labels are shared with other reports.
    'LAN_RACEREPORTS_SUT_TITLE'      => 'Risultati',
    'LAN_RACEREPORTS_SUT_NO_FINISH'  => 'Nessun arrivato al momento.',
    'LAN_RACEREPORTS_COL_RANK'        => 'Posizione',
    'LAN_RACEREPORTS_COL_TIME'        => 'Tempo',
    'LAN_RACEREPORTS_COL_SURNAME'     => 'Cognome',
    'LAN_RACEREPORTS_COL_FIRSTNAME'   => 'Nome',
    'LAN_RACEREPORTS_COL_GENDER'      => 'Sesso',
    'LAN_RACEREPORTS_COL_BIRTHDATE'   => 'Data di nascita',
    'LAN_RACEREPORTS_COL_NATIONALITY' => 'Nazionalità',
    'LAN_RACEREPORTS_COL_BIB'         => 'Numero pettorale',
    // Category column (arrivals board header, finish export).
    'LAN_RACEREPORTS_COL_CATEGORY'    => 'Categoria',
    // Etichette delle colonne di esportazione Traguardo/Partenza (le tabelle di traguardo/partenza non hanno una riga'
    // di intestazione sullo schermo; queste vengono utilizzate solo per la riga di intestazione CSV/XLS).
    'LAN_RACEREPORTS_COL_NAME'        => 'Nome',
    'LAN_RACEREPORTS_COL_STATUS'      => 'Stato',

    // SUT export buttons (CSV / XLS download).
    'LAN_RACEREPORTS_EXPORT_CSV'      => 'CSV',
    'LAN_RACEREPORTS_EXPORT_XLS'      => 'XLS',

    // Resoconto AKTUALNE (matrice completa dei risultati per gara).
    'LAN_RACEREPORTS_AKT_TITLE'         => 'Risultati completi',
    'LAN_RACEREPORTS_AKT_UNKNOWN_RACE'  => 'Gara sconosciuta.',
    'LAN_RACEREPORTS_AKT_EMPTY'         => 'Nessun corridore.',
    'LAN_RACEREPORTS_AKT_COL_POR'       => 'Pos.',
    'LAN_RACEREPORTS_AKT_COL_NAME'      => 'Nome',
    'LAN_RACEREPORTS_AKT_COL_CAT'       => 'Cat.',
    'LAN_RACEREPORTS_AKT_COL_TIME'      => 'Tempo',
    'LAN_RACEREPORTS_AKT_COL_CATRANK'   => 'Posizione nella categoria',

    // Resoconto NUMERO (progressione del singolo corridore): pettorale sconosciuto, intestazioni di colonna e'
    // nota sullo stato DNF/DSQ.
    'LAN_RACEREPORTS_NUM_UNKNOWN_BIB'   => 'Numero di pettorale sconosciuto.',
    'LAN_RACEREPORTS_NUM_COL_POINT'     => 'Punto di controllo',
    'LAN_RACEREPORTS_NUM_COL_TIMEOFDAY' => 'Ora del giorno',
    'LAN_RACEREPORTS_NUM_COL_SPLIT'     => 'Tempo parziale (Split)',
    'LAN_RACEREPORTS_NUM_COL_SEGMENT'   => 'Segmento',
    'LAN_RACEREPORTS_NUM_DNF'           => 'DNF (Ritirato)',
    'LAN_RACEREPORTS_NUM_DSQ'           => 'DSQ (Squalificato)',
);
