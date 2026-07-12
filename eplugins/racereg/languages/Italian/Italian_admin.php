<?php
/*
 * e107 website system
 *
 * racereg plugin - Italian admin strings.
 */

if (!defined('e107_INIT')) { exit; }

return array(

    /* ---- Admin menu ---------------------------------------------------------- */
    'LAN_RACEREG_CONFIG' => "Configurazione",
    'LAN_RACEREG_REG_LIST' => "Iscrizioni",
    'LAN_RACEREG_REG_CREATE' => "Aggiungi iscrizione",
    'LAN_RACEREG_REG_INFO' => "Ulteriori informazioni",
    'LAN_RACEREG_PAY_LIST' => "Pagamenti",
    'LAN_RACEREG_PAY_CREATE' => "Aggiungi pagamento",

    /* ---- More info page ------------------------------------------------------ */
    'LAN_RACEREG_REG_FORM_LINK' => "Modulo di iscrizione",

    /* ---- Registration fields ------------------------------------------------- */
    'LAN_RACEREG_TRACK' => "Percorso",
    'LAN_RACEREG_FIRST_NAME' => "Nome",
    'LAN_RACEREG_LAST_NAME' => "Cognome",
    'LAN_RACEREG_BIRTH_DATE' => "Data di nascita",
    'LAN_RACEREG_STREET' => "Via / Indirizzo",
    'LAN_RACEREG_CITY' => "Città",
    'LAN_RACEREG_POSTAL' => "Codice postale",
    'LAN_RACEREG_COUNTRY' => "Paese",
    'LAN_RACEREG_EMAIL' => "Email",
    'LAN_RACEREG_PHONE' => "Telefono",
    'LAN_RACEREG_CLUB' => "Club / Società",
    'LAN_RACEREG_REG_DATE' => "Data di iscrizione",
    'LAN_RACEREG_START_LIST_AT' => "In elenco di partenza (data)",
    'LAN_RACEREG_VS' => "Simbolo variabile",
    'LAN_RACEREG_VS_HELP' => "Simbolo numerico univoco generato automaticamente. Bloccato dopo la creazione.",
    'LAN_RACEREG_AMOUNT_DUE' => "Importo dovuto",
    'LAN_RACEREG_AMOUNT_DUE_HELP' => "Inserito manualmente in questa versione. Il congelamento automatico dei prezzi verrà introdotto successivamente.",
    'LAN_RACEREG_APPROVAL' => "Stato di approvazione",
    'LAN_RACEREG_PRIVATE_NOTE' => "Nota privata",

    /* ---- Approval status labels ---------------------------------------------- */
    'LAN_RACEREG_APPROVAL_0' => "In attesa",
    'LAN_RACEREG_APPROVAL_1' => "Approvato",
    'LAN_RACEREG_APPROVAL_2' => "Rifiutato",

    /* ---- Payment fields ------------------------------------------------------- */
    'LAN_RACEREG_PAY_REGISTRATION' => "Iscrizione",
    'LAN_RACEREG_PAY_AMOUNT' => "Importo",
    'LAN_RACEREG_PAY_STATUS' => "Stato",
    'LAN_RACEREG_PAY_PAID_AT' => "Pagato il",
    'LAN_RACEREG_PAY_NOTE' => "Nota",
    'LAN_RACEREG_PAY_CREATED' => "Creato il",

    /* ---- Payment status labels ----------------------------------------------- */
    'LAN_RACEREG_PAYST_0' => "In attesa",
    'LAN_RACEREG_PAYST_1' => "Valido",
    'LAN_RACEREG_PAYST_2' => "Errato",
    'LAN_RACEREG_PAYST_3' => "Rimborsato",

    /* ---- Messages / help ----------------------------------------------------- */
    'LAN_RACEREG_SOFT_DELETED' => "Iscrizione contrassegnata come eliminata.",
    'LAN_RACEREG_REG_HELP' => "Le iscrizioni contengono dati personali (PII): visibili solo all'organizzatore, nessuna esposizione sul front-end. Le eliminazioni sono logiche (mantenute per scopi di audit / ripristino). Il simbolo variabile è generato automaticamente e bloccato; l'importo dovuto viene inserito manualmente in questa versione.",
    'LAN_RACEREG_PAY_HELP' => "Pagamenti collegati a un'iscrizione. Un'iscrizione può contenere più righe di pagamento. Filtra l'elenco per iscrizione utilizzando la casella di filtro.",
    'LAN_RACEREG_CONFIG_DOC_HELP' => 
        "<strong>Configurazione che gestisce iscrizioni e pagamenti</strong><br>"
        . "Questi campi risiedono nei plugin correlati; una configurazione errata interrompe silenziosamente la registrazione o il funzionamento del codice QR."
        . "<br><br><strong>Beneficiario (Configurazione evento &rarr; raceevent)</strong><br>"
        . "Il codice QR di pagamento (PAY by square) richiede SIA l'IBAN del beneficiario sia il Nome del beneficiario. Il nome del beneficiario è obbligatorio - bysquare non può codificare il QR senza di esso - quindi un IBAN salvato senza nome viene rifiutato. Lo SWIFT / BIC è opzionale."
        . "<br><br><strong>Finestra di iscrizione (raceevent)</strong><br>"
        . "Le iscrizioni sono accettate solo tra \"Apertura iscrizioni\" e \"Chiusura iscrizioni\". Ciascun lato può essere lasciato vuoto (0) per nessun limite; se sono impostati entrambi, l'apertura deve essere tassativamente precedente alla chiusura."
        . "<br><br><strong>Impostazioni per percorso (Percorsi &rarr; racetrack)</strong><br>"
        . "Capacità = limite massimo nell'elenco di partenza; la capacità illimitata la ignora. Richiede approvazione = le iscrizioni rimangono in attesa dell'organizzatore. Iscrizioni chiuse = non è possibile iscriversi al percorso. Le fasce di prezzo impostano la quota in base alla data; un percorso aperto alle iscrizioni senza alcuna fascia di prezzo viene considerato gratuito (\"bez poplatku\").",

    /* ---- Scaffold placeholder (kept for reference) --------------------------- */
    'LAN_RACEREG_SCAFFOLD_INFO' => "Questo è lo scaffold di racereg. Le funzionalità di iscrizione e pagamento (flusso di registrazione, QR PAY by square, elenco amministratore / contrassegna come pagato) compariranno qui nelle versioni successive.",
    'LAN_RACEREG_CONFIG_HELP' => "Iscrizioni e pagamenti per la suite di cronometraggio delle gare. Dipende dai plugin raceevent (evento) e race (percorsi). Questo plugin conterrà i dati personali più sensibili della suite - limitare accuratamente i permessi di amministrazione.",

    /* ---- Organizer actions (issue #26) --------------------------------------- */
    'LAN_RACEREG_PAID_STATUS' => "Pagato",
    'LAN_RACEREG_PAID_STATUS_HELP' => "Derivato dai pagamenti validi rispetto all'importo dovuto. Solo visualizzazione - non memorizzato.",
    'LAN_RACEREG_PAID_NOFEE' => "Nessuna quota",
    'LAN_RACEREG_PAID_UNPAID' => "Non pagato",
    'LAN_RACEREG_PAID_PARTIAL' => "Parziale",
    'LAN_RACEREG_PAID_PAID' => "Pagato",

    'LAN_RACEREG_ACT_APPROVE' => "Approva",
    'LAN_RACEREG_ACT_REJECT' => "Rifiuta",
    'LAN_RACEREG_ACT_PROMOTE' => "Promuovi",
    'LAN_RACEREG_ACT_MARKPAID' => "Contrassegna come pagato",
    'LAN_RACEREG_ACT_PAYMENT' => "Mostra dettagli di pagamento",
    'LAN_RACEREG_ACT_BACK' => "Torna all'elenco",

    /* ---- Shared payment view + QR (issue #40) -------------------------------- */
    'LAN_RACEREG_PAY_PAYEE' => "Beneficiario",
    'LAN_RACEREG_PAY_IBAN' => "IBAN",
    'LAN_RACEREG_PAY_SWIFT' => "SWIFT / BIC",
    'LAN_RACEREG_PAY_NO_IBAN' => "Il conto di pagamento non è ancora stato configurato.",
    'LAN_RACEREG_PAY_NOTE_TEXT' => "Utilizza il simbolo variabile mostrato sopra come causale/riferimento del pagamento.",
    'LAN_RACEREG_QR_TITLE' => "Paga tramite codice QR",
    'LAN_RACEREG_QR_HINT' => "Scansiona questo codice PAY by square con l'app della tua banca per precompilare il pagamento (IBAN, importo e simbolo variabile).",
    'LAN_RACEREG_PAY_LINK_NOTE' => "Link di pagamento pubblico per questa iscrizione (il richiedente può usarlo per pagare in un secondo momento):",
    'LAN_RACEREG_CONFIRM_REJECT' => "Rifiutare questa iscrizione?",

    'LAN_RACEREG_MSG_PAID' => "Pagamento contrassegnato come valido.",
    'LAN_RACEREG_MSG_RECORDED' => "Registrato un pagamento valido di [x] EUR.",
    'LAN_RACEREG_MSG_ALREADY_PAID' => "Questa iscrizione è già interamente pagata.",
    'LAN_RACEREG_MSG_APPROVED_PLACED' => "Iscrizione approvata e inserita nell'elenco di partenza.",
    'LAN_RACEREG_MSG_APPROVED_SUB' => "Iscrizione approvata; il percorso è completo, inserito come riserva.",
    'LAN_RACEREG_MSG_REJECTED' => "Iscrizione rifiutata.",
    'LAN_RACEREG_MSG_PROMOTED' => "Riserva promossa nell'elenco di partenza.",
    'LAN_RACEREG_MSG_FULL' => "Il percorso è completo - impossibile inserire questa iscrizione.",
    'LAN_RACEREG_MSG_NOOP' => "Nessuna modifica apportata.",
    'LAN_RACEREG_MSG_AUTOPROMOTED' => "Una riserva è stata promossa automaticamente nel posto liberatosi.",
    'LAN_RACEREG_MSG_CREATE_SUBSTITUTE' => "Il percorso è completo - questa iscrizione è stata aggiunta come riserva.",

    'LAN_RACEREG_ERR_TOKEN' => "Token di sicurezza non valido. Riprova.",
    'LAN_RACEREG_ERR_NOTFOUND' => "Iscrizione o pagamento non trovati.",

    /* ---- Paid-status quick filter -------------------------------------------- */
    'LAN_RACEREG_PAIDFILTER' => "Stato del pagamento",
    'LAN_RACEREG_PAIDFILTER_ALL' => "Tutti",

    /* ---- Registration-by-track overview -------------------------------------- */
    'LAN_RACEREG_RT_TITLE' => "Iscrizioni per percorso",
    'LAN_RACEREG_RT_ID' => "ID Percorso",
    'LAN_RACEREG_RT_NAME' => "Nome percorso",
    'LAN_RACEREG_RT_ALL' => "Iscrizioni - Tutte",
    'LAN_RACEREG_RT_APPROVED' => "Approvate",
    'LAN_RACEREG_RT_REJECTED' => "Rifiutate",
    'LAN_RACEREG_RT_PENDING' => "In attesa",
    'LAN_RACEREG_RT_NOFEE' => "Nessuna quota",
    'LAN_RACEREG_RT_PAID' => "Pagate",
    'LAN_RACEREG_RT_STARTERS' => "Partenti",
    'LAN_RACEREG_RT_NOTRACKS' => "Nessun percorso trovato.",

    /* ---- Confirmation-page preview (admin-only, inert) ----------------------- */
    'LAN_RACEREG_PREVIEW_TITLE' => "Anteprima della pagina di conferma",
    'LAN_RACEREG_PREVIEW_INFO' => "Anteprima riservata all'amministratore. Mostra la pagina di conferma del richiedente con dati di esempio - non viene salvato nulla e non viene inviata alcuna email.",
    'LAN_RACEREG_PREVIEW_STARTLIST' => "In elenco di partenza",
    'LAN_RACEREG_PREVIEW_SUBSTITUTE' => "Riserva",
    'LAN_RACEREG_PREVIEW_PENDING' => "In attesa di pagamento",
    'LAN_RACEREG_PREVIEW_NOQR' => "I dettagli bancari del beneficiario non sono impostati nella configurazione dell'evento, pertanto il codice QR PAY-by-square non verrà mostrato nell'anteprima.",

    /* ---- Notify (Admin -> Notify, e_notify.php) ------------------------------ */
    'LAN_RACEREG_NT_SIGNUP' => "Nuova iscrizione inviata",
    'LAN_RACEREG_NT_SIGNUP_MSG' => "È stata inviata una nuova iscrizione.<br><br>Nome: [name]<br>Percorso: [track]<br>Simbolo variabile: [vs]<br>Importo: [amount]<br><br>Dettagli: [link]",
);
