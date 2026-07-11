<?php
/*
 * e107 website system
 *
 * racereg plugin - English front strings (issue #24).
 *
 * Loaded on the front-end via e107::lan('racereg', '', true) (languages/<Language>/<Language>_front.php).
 * Field labels are redefined here (independent of the admin LAN folder) because
 * the public page loads only the front language file.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // ---- Page ----
    'LAN_RACEREG_SIGNUP_TITLE'  => 'Race registration',
    'LAN_RACEREG_CONFIRM_TITLE' => 'Registration received',
    'LAN_RACEREG_INTRO'         => 'Fill in the form below to sign up for a track. Fields marked with * are required.',
    'LAN_RACEREG_SUBMIT'        => 'Submit registration',
    'LAN_RACEREG_SELECT_TRACK'  => '— choose a track —',
    'LAN_RACEREG_GDPR_LABEL'    => 'I agree to the processing of my personal data for the purpose of this registration (GDPR). See the privacy / retention notice.',

    // ---- Field labels (front) ----
    'LAN_RACEREG_TRACK'         => 'Track',
    'LAN_RACEREG_CATEGORY'      => 'Category',
    'LAN_RACEREG_CATEGORY_NONE' => 'Not specified',
    'LAN_RACEREG_NATIONALITY'   => 'Nationality',
    'LAN_RACEREG_LOCAL'         => 'Local racer',
    'LAN_RACEREG_FIRST_NAME'    => 'First name',
    'LAN_RACEREG_LAST_NAME'     => 'Last name',
    'LAN_RACEREG_BIRTH_DATE'    => 'Birth date',
    'LAN_RACEREG_STREET'        => 'Street',
    'LAN_RACEREG_CITY'          => 'City',
    'LAN_RACEREG_POSTAL'        => 'Postal code',
    'LAN_RACEREG_COUNTRY'       => 'Country',
    'LAN_RACEREG_EMAIL'         => 'Email',
    'LAN_RACEREG_PHONE'         => 'Phone',
    'LAN_RACEREG_CLUB'          => 'Club',
    'LAN_RACEREG_VS'            => 'Variable symbol',
    'LAN_RACEREG_AMOUNT_DUE'    => 'Amount due',

    // ---- Confirmation ----
    'LAN_RACEREG_CONFIRM_SUMMARY'  => 'Summary',
    'LAN_RACEREG_CONFIRM_PAYMENT'  => 'Payment details',
    'LAN_RACEREG_STATE_STARTLIST'  => 'You are confirmed on the start list.',
    'LAN_RACEREG_STATE_SUBSTITUTE' => 'The track is full — you have been placed on the substitute list. You will be moved up if a spot frees up.',
    'LAN_RACEREG_STATE_PENDING'    => 'Your registration was received and is awaiting approval by the organizer.',
    'LAN_RACEREG_PAY_PAYEE'        => 'Payee',
    'LAN_RACEREG_PAY_IBAN'         => 'IBAN',
    'LAN_RACEREG_PAY_SWIFT'        => 'SWIFT / BIC',
    'LAN_RACEREG_QR_TITLE'         => 'Pay by QR code',
    'LAN_RACEREG_QR_HINT'          => 'Scan this PAY by square code with your banking app to prefill the payment (IBAN, amount and variable symbol).',
    'LAN_RACEREG_PAY_NO_IBAN'      => 'The payment account has not been configured yet. The organizer will provide payment details.',
    'LAN_RACEREG_PAY_NOTE_TEXT'    => 'Please use the variable symbol shown above as the payment reference.',

    // ---- Payment link + tokenized pay page (issue #40) ----
    'LAN_RACEREG_PAY_LINK_NOTE'     => 'Save this link to return to your payment details and QR code later:',
    'LAN_RACEREG_PAY_DETAILS_TITLE' => 'Payment details',
    'LAN_RACEREG_PAY_NOT_FOUND'     => 'This payment link is invalid or is no longer available.',
    'LAN_RACEREG_PAY_GREETING'      => 'Payment details for [x]',
    'LAN_RACEREG_PAID_STATUS'       => 'Payment status',
    'LAN_RACEREG_PAID_NOFEE'        => 'No fee',
    'LAN_RACEREG_PAID_UNPAID'       => 'Unpaid',
    'LAN_RACEREG_PAID_PARTIAL'      => 'Partially paid',
    'LAN_RACEREG_PAID_PAID'         => 'Paid',

    // ---- Errors / messages ----
    'LAN_RACEREG_ERR_FORM'         => 'Please correct the highlighted fields and try again.',
    'LAN_RACEREG_ERR_CSRF'         => 'Security token check failed. Please reload the page and try again.',
    'LAN_RACEREG_ERR_SPAM'         => 'Your submission could not be processed.',
    'LAN_RACEREG_ERR_WINDOW'       => 'Registration is currently closed for this event.',
    'LAN_RACEREG_ERR_NOTRACKS'     => 'There are no tracks open for registration at the moment.',
    'LAN_RACEREG_ERR_TRACK'        => 'Please select a valid track.',
    'LAN_RACEREG_ERR_TRACK_CLOSED' => 'Registration for the selected track is closed.',
    'LAN_RACEREG_ERR_REQUIRED'     => 'This field is required.',
    'LAN_RACEREG_ERR_EMAIL'        => 'Please enter a valid email address.',
    'LAN_RACEREG_ERR_BIRTH'        => 'Please enter a valid birth date.',
    'LAN_RACEREG_ERR_GDPR'         => 'Consent is required to register.',
    'LAN_RACEREG_ERR_SAVE'         => 'The registration could not be saved. Please try again.',
);
