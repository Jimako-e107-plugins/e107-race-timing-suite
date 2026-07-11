<?php
/*
 * e107 website system
 *
 * racereg plugin - Italian front strings (issue #24).
 *
 * Loaded on the front-end via e107::lan('racereg', '', true) (languages/<Language>/<Language>_front.php).
 * Field labels are redefined here (independent of the admin LAN folder) because
 * the public page loads only the front language file.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // ---- Page ----
    'LAN_RACEREG_SIGNUP_TITLE'  => 'Registrazione alla gara',
    'LAN_RACEREG_CONFIRM_TITLE' => 'Iscrizione ricevuta',
    'LAN_RACEREG_INTRO'         => 'Compila il modulo sottostante per iscriverti a un percorso. I campi contrassegnati con * sono obbligatori.',
    'LAN_RACEREG_SUBMIT'        => 'Invia iscrizione',
    'LAN_RACEREG_SELECT_TRACK'  => '— scegli un percorso —',
    'LAN_RACEREG_GDPR_LABEL'    => 'Acconsento al trattamento dei miei dati personali ai fini della presente iscrizione (GDPR). Consulta l\'informativa sulla privacy / conservazione dei dati.',

    // ---- Field labels (front) ----
    'LAN_RACEREG_TRACK'         => 'Percorso',
    'LAN_RACEREG_CATEGORY'      => 'Categoria',
    'LAN_RACEREG_CATEGORY_NONE' => 'Non specificata',
    'LAN_RACEREG_NATIONALITY'   => 'Nazionalità',
    'LAN_RACEREG_LOCAL'         => 'Corridore locale',
    'LAN_RACEREG_FIRST_NAME'    => 'Nome',
    'LAN_RACEREG_LAST_NAME'     => 'Cognome',
    'LAN_RACEREG_BIRTH_DATE'    => 'Data di nascita',
    'LAN_RACEREG_STREET'        => 'Via / Indirizzo',
    'LAN_RACEREG_CITY'          => 'Città',
    'LAN_RACEREG_POSTAL'        => 'Codice postale',
    'LAN_RACEREG_COUNTRY'       => 'Paese',
    'LAN_RACEREG_EMAIL'         => 'Email',
    'LAN_RACEREG_PHONE'         => 'Telefono',
    'LAN_RACEREG_CLUB'          => 'Club / Società',
    'LAN_RACEREG_VS'            => 'Simbolo variabile',
    'LAN_RACEREG_AMOUNT_DUE'    => 'Importo dovuto',

    // ---- Confirmation ----
    'LAN_RACEREG_CONFIRM_SUMMARY'  => 'Riepilogo',
    'LAN_RACEREG_CONFIRM_PAYMENT'  => 'Dettagli di pagamento',
    'LAN_RACEREG_STATE_STARTLIST'  => 'La tua presenza nell\'elenco di partenza è confermata.',
    'LAN_RACEREG_STATE_SUBSTITUTE' => 'Il percorso è completo — sei stato inserito nell\'elenco delle riserve. Verrai promosso se si libererà un posto.',
    'LAN_RACEREG_STATE_PENDING'    => 'La tua iscrizione è stata ricevuta ed è in attesa di approvazione da parte dell\'organizzatore.',
    'LAN_RACEREG_PAY_PAYEE'        => 'Beneficiario',
    'LAN_RACEREG_PAY_IBAN'         => 'IBAN',
    'LAN_RACEREG_PAY_SWIFT'        => 'SWIFT / BIC',
    'LAN_RACEREG_QR_TITLE'         => 'Paga tramite codice QR',
    'LAN_RACEREG_QR_HINT'          => 'Scansiona questo codice PAY by square con l\'app della tua banca per precompilare il pagamento (IBAN, importo e simbolo variabile).',
    'LAN_RACEREG_PAY_NO_IBAN'      => 'Il conto di pagamento non è ancora stato configurato. L\'organizzatore fornirà i dettagli di pagamento.',
    'LAN_RACEREG_PAY_NOTE_TEXT'    => 'Utilizza il simbolo variabile mostrato sopra come causale/riferimento del pagamento.',

    // ---- Payment link + tokenized pay page (issue #40) ----
    'LAN_RACEREG_PAY_LINK_NOTE'     => 'Salva questo link per tornare in seguito ai tuoi dettagli di pagamento e al codice QR:',
    'LAN_RACEREG_PAY_DETAILS_TITLE' => 'Dettagli di pagamento',
    'LAN_RACEREG_PAY_NOT_FOUND'     => 'Questo link di pagamento non è valido o non è più disponibile.',
    'LAN_RACEREG_PAY_GREETING'      => 'Dettagli di pagamento per [x]',
    'LAN_RACEREG_PAID_STATUS'       => 'Stato del pagamento',
    'LAN_RACEREG_PAID_NOFEE'        => 'Nessuna quota',
    'LAN_RACEREG_PAID_UNPAID'       => 'Non pagato',
    'LAN_RACEREG_PAID_PARTIAL'      => 'Parzialmente pagato',
    'LAN_RACEREG_PAID_PAID'         => 'Pagato',

    // ---- Errors / messages ----
    'LAN_RACEREG_ERR_FORM'         => 'Correggi i campi evidenziati e riprova.',
    'LAN_RACEREG_ERR_CSRF'         => 'Controllo del token di sicurezza fallito. Ricarica la pagina e riprova.',
    'LAN_RACEREG_ERR_SPAM'         => 'Impossibile elaborare l\'invio del modulo.',
    'LAN_RACEREG_ERR_WINDOW'       => 'Le iscrizioni per questo evento sono attualmente chiuse.',
    'LAN_RACEREG_ERR_NOTRACKS'     => 'Al momento non ci sono percorsi aperti alle iscrizioni.',
    'LAN_RACEREG_ERR_TRACK'        => 'Seleziona un percorso valido.',
    'LAN_RACEREG_ERR_TRACK_CLOSED' => 'Le iscrizioni per il percorso selezionato sono chiuse.',
    'LAN_RACEREG_ERR_REQUIRED'     => 'Questo campo è obbligatorio.',
    'LAN_RACEREG_ERR_EMAIL'        => 'Inserisci un indirizzo email valido.',
    'LAN_RACEREG_ERR_BIRTH'        => 'Inserisci una data di nascita valida.',
    'LAN_RACEREG_ERR_GDPR'         => 'Il consenso è obbligatorio per potersi registrare.',
    'LAN_RACEREG_ERR_SAVE'         => 'Impossibile salvare l\'iscrizione. Riprova.',
);
