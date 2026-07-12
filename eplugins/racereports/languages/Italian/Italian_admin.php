<?php
/*
 * e107 website system
 *
 * racereports plugin - Italian admin language file.
 *
 * Array-style LAN file loaded via e107::lan('racereports', true, true).
 * Array-style is used on purpose: e107's includeLan() only applies the per-key
 * English fallback for missing translations when the language file RETURNS an
 * array, so a partial Slovak file (languages/Slovak/Slovak_admin.php) degrades
 * cleanly to these English strings instead of leaving constants undefined. This
 * file is the canonical, complete set; the Slovak file overrides per key.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Schermata principale amministratore (admin/admin_config.php).
    'LAN_ADMIN_RACEREPORTS_001' => 'Report gara',
    'LAN_ADMIN_RACEREPORTS_002' => 'Risultati e report per la suite di cronometraggio: tabellone dei risultati in diretta, classifica generale e per categoria, il percorso di un singolo concorrente attraverso tutti i punti di controllo e tutti i tempi rilevati in un singolo punto di controllo. Legge i tempi da racetiming e i nomi/categorie da racers; non dichiara tabelle proprie.',   // Etichetta dei permessi amministratore.
    'LAN_ADMIN_RACEREPORTS_003' => 'Amministrazione dei resoconti di gara',

    // Voci del menu di navigazione sinistro + intestazioni di pagina.
    'LAN_ADMIN_RACEREPORTS_004' => 'Panoramica della gara',
    'LAN_ADMIN_RACEREPORTS_005' => 'Risultati online',
    'LAN_ADMIN_RACEREPORTS_006' => 'Tempi ai punti di controllo',
    'LAN_ADMIN_RACEREPORTS_007' => 'Risultati (SUT)',
    'LAN_ADMIN_RACEREPORTS_008' => 'Risultati (traguardo)',
    'LAN_ADMIN_RACEREPORTS_009' => 'Elenco di partenza',
    'LAN_ADMIN_RACEREPORTS_105' => 'Segmento (Da-A)',
    'LAN_ADMIN_RACEREPORTS_107' => 'Arrivi (dobeh)',
    'LAN_ADMIN_RACEREPORTS_116' => 'Progressione del corridore',

    // Link ai resoconti nella pagina principale.
    'LAN_ADMIN_RACEREPORTS_010' => 'Nessuna gara presente.',
    'LAN_ADMIN_RACEREPORTS_011' => 'Nessuna categoria presente.',
    'LAN_ADMIN_RACEREPORTS_012' => 'Nessun punto di controllo presente.',
    'LAN_ADMIN_RACEREPORTS_020' => 'Classifiche online',
    'LAN_ADMIN_RACEREPORTS_021' => 'online - tutte le categorie',
    'LAN_ADMIN_RACEREPORTS_030' => 'Tempi dopo i punti di controllo',
    'LAN_ADMIN_RACEREPORTS_031' => 'online - tutti i punti di controllo',
    'LAN_ADMIN_RACEREPORTS_040' => 'Test di parità',
    'LAN_ADMIN_RACEREPORTS_041' => 'Controllo di parità (motore pulito vs comparatore legacy)',
    'LAN_ADMIN_RACEREPORTS_042' => 'Il comparatore di parità rimane limitato agli amministratori. Il test automatico del motore correlato (parity/engine_selftest.php) è eseguibile solo da CLI e non è collegato all\'interfaccia web.',

    // Schermata panoramica: elenco informativo dei tipi di resoconto supportati.
    'LAN_ADMIN_RACEREPORTS_050' => 'Tipi di risultati supportati',
    'LAN_ADMIN_RACEREPORTS_051' => 'Online',
    'LAN_ADMIN_RACEREPORTS_052' => 'Tempi ai punti di controllo',

    // Schermata SUT: risultati per percorso riservati ai soli atleti che hanno terminato la gara.
    'LAN_ADMIN_RACEREPORTS_060' => 'Risultati per percorso (solo arrivati)',

    // Schermata NUMERO: progressione del corridore - per ogni pettorale -> resoconto del singolo corridore su tutto il percorso.
    'LAN_ADMIN_RACEREPORTS_117' => 'Corridori (per numero di pettorale)',

    // Schermata TRAGUARDO: elenco dei risultati post-gara (arrivati + DNF/DSQ/DNS).
    'LAN_ADMIN_RACEREPORTS_080' => 'Risultati al traguardo',
    'LAN_ADMIN_RACEREPORTS_081' => 'traguardo - tutte le categorie',
    'LAN_ADMIN_RACEREPORTS_082' => 'traguardo - tutti i percorsi su un\'unica pagina',

    // Schermata PARTENZA: elenco delle posizioni al punto di partenza (partiti + gruppo dei non partiti).
    'LAN_ADMIN_RACEREPORTS_090' => 'Elenco di partenza',
    'LAN_ADMIN_RACEREPORTS_091' => 'partenza - tutte le categorie',
    'LAN_ADMIN_RACEREPORTS_092' => 'partenza - tutti i percorsi su un\'unica pagina',

    // Schermata DOBEH: link al tabellone degli arrivi per ciascun punto di controllo.
    'LAN_ADMIN_RACEREPORTS_108' => 'Tabellone degli arrivi',
    'LAN_ADMIN_RACEREPORTS_109' => 'arrivi - tutti i punti di controllo',

    // Pagina IMPOSTAZIONI (admin/admin_config.php) - preferenze del plugin.
    'LAN_ADMIN_RACEREPORTS_070' => 'Tempo SUT - cifre decimali',
    'LAN_ADMIN_RACEREPORTS_071' => 'Cifre decimali per i tempi di arrivo nel resoconto SUT (arrivati). 0 = secondi interi (HH:MM:SS), come in precedenza.',
    'LAN_ADMIN_RACEREPORTS_072' => 'SUT - colora categorie',
    'LAN_ADMIN_RACEREPORTS_073' => 'Disattivato = elenco dei risultati pulito senza colori di sfondo per le categorie. Attivato = sfondo della riga personalizzato per categoria nel resoconto SUT (arrivati).',
    'LAN_ADMIN_RACEREPORTS_074' => 'Online - intervallo di aggiornamento automatico (s)',
    'LAN_ADMIN_RACEREPORTS_075' => '0 = nessun aggiornamento automatico. Valore espresso in secondi. Il parametro ?refresh nell\'indirizzo ha la precedenza.',
    'LAN_ADMIN_RACEREPORTS_076' => 'Traguardo - colora categorie',
    'LAN_ADMIN_RACEREPORTS_077' => 'Attivato (predefinito) = sfondo della riga personalizzato per categoria sulle righe dei finalisti nel resoconto del traguardo (risultati). Disattivato = elenco dei risultati pulito senza colori delle categorie. Le righe degli atleti ritirati/squalificati/non partiti (DNF/DSQ/DNS) non vengono mai colorate.',
    'LAN_ADMIN_RACEREPORTS_078' => 'Partenza - colora categorie',
    'LAN_ADMIN_RACEREPORTS_079' => 'Attivato (predefinito) = sfondo della riga personalizzato per categoria sulle righe dei partenti nel resoconto di partenza (elenco di partenza). Disattivato = elenco pulito senza colori delle categorie. Le righe dei non partiti non vengono mai colorate.',

    // Area resoconti AKTUALNE (matrice completa dei risultati per gara) (admin/admin_aktualne.php).
    'LAN_ADMIN_RACEREPORTS_100' => 'Risultati completi',
    'LAN_ADMIN_RACEREPORTS_101' => 'Risultati di gara completi (tutti i punti di controllo)',
    // Schermata PERSONALIZZATA (segmento): selettore Da/A a due menu a tendina per gara (admin/admin_custom.php).
    'LAN_ADMIN_RACEREPORTS_106' => 'Resoconto del segmento (tra due punti)',
    'LAN_ADMIN_RACEREPORTS_102' => 'Da (Od)',
    'LAN_ADMIN_RACEREPORTS_103' => 'A (Do)',
    'LAN_ADMIN_RACEREPORTS_104' => 'Apri resoconto del segmento',
    'LAN_ADMIN_RACEREPORTS_093' => 'Arrivi - intervallo di aggiornamento automatico (s)',
    'LAN_ADMIN_RACEREPORTS_094' => '0 = nessun aggiornamento automatico. Valore espresso in secondi. Il parametro ?refresh nell\'indirizzo ha la precedenza. Separato dall\'intervallo online - il tabellone degli arrivi ha un ritmo indipendente.',
    'LAN_ADMIN_RACEREPORTS_095' => 'Traguardo - colonna categoria',
    'LAN_ADMIN_RACEREPORTS_096' => 'Disattivato (predefinito) = nessuna colonna per la categoria. Attivato = mostra una colonna con il nome della categoria nell\'elenco del traguardo (risultati) e nella relativa esportazione CSV/XLS.',
    'LAN_ADMIN_RACEREPORTS_110' => 'Risultati - cifre decimali inferiori al secondo',
    'LAN_ADMIN_RACEREPORTS_111' => '0-3. Si applica ai resoconti dei risultati (online, punti di controllo, traguardo, partenza, segmento, arrivi). I dati sono sempre memorizzati a 3 decimali (ms); questo valore influisce solo sulla visualizzazione (troncata, non arrotondata). Il SUT ha una propria impostazione; il resoconto Aktuálne (panoramica) non è gestito da questa opzione.',
    // Intestazioni delle schede di preferenza nella schermata delle impostazioni (e_admin_ui $preftabs). 
    // Le chiavi delle schede stringa (dec/colors/refresh/custom) associano ogni preferenza all'identità di una scheda; 
    // il riordinamento dell'elenco delle schede non influisce mai sulle preferenze.
    'LAN_ADMIN_RACEREPORTS_112' => 'Decimali',
    'LAN_ADMIN_RACEREPORTS_113' => 'Colorazione',
    'LAN_ADMIN_RACEREPORTS_114' => 'Aggiornamento',
    'LAN_ADMIN_RACEREPORTS_115' => 'Altro',
);
