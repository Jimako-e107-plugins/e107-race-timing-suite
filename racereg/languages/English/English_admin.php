<?php
/*
 * e107 website system
 *
 * racereg plugin - English admin strings.
 */

define('LAN_RACEREG_PLUGIN', 'Race registration');

/* ---- Admin menu ---------------------------------------------------------- */
define('LAN_RACEREG_CONFIG',     'Configuration');
define('LAN_RACEREG_REG_LIST',   'Registrations');
define('LAN_RACEREG_REG_CREATE', 'Add registration');
define('LAN_RACEREG_REG_INFO',   'More info');
define('LAN_RACEREG_PAY_LIST',   'Payments');
define('LAN_RACEREG_PAY_CREATE', 'Add payment');

/* ---- More info page ------------------------------------------------------ */
define('LAN_RACEREG_REG_FORM_LINK', 'Registration form');

/* ---- Registration fields ------------------------------------------------- */
define('LAN_RACEREG_TRACK',         'Track');
define('LAN_RACEREG_FIRST_NAME',    'First name');
define('LAN_RACEREG_LAST_NAME',     'Last name');
define('LAN_RACEREG_BIRTH_DATE',    'Birth date');
define('LAN_RACEREG_STREET',        'Street');
define('LAN_RACEREG_CITY',          'City');
define('LAN_RACEREG_POSTAL',        'Postal code');
define('LAN_RACEREG_COUNTRY',       'Country');
define('LAN_RACEREG_EMAIL',         'Email');
define('LAN_RACEREG_PHONE',         'Phone');
define('LAN_RACEREG_CLUB',          'Club');
define('LAN_RACEREG_REG_DATE',      'Registration date');
define('LAN_RACEREG_START_LIST_AT', 'On start list (date)');
define('LAN_RACEREG_VS',            'Variable symbol');
define('LAN_RACEREG_VS_HELP',       'Auto-generated unique numeric symbol. Locked after creation.');
define('LAN_RACEREG_AMOUNT_DUE',    'Amount due');
define('LAN_RACEREG_AMOUNT_DUE_HELP','Entered manually in this version. Automatic price freeze comes later.');
define('LAN_RACEREG_APPROVAL',      'Approval status');
define('LAN_RACEREG_PRIVATE_NOTE',  'Private note');

/* ---- Approval status labels ---------------------------------------------- */
define('LAN_RACEREG_APPROVAL_0', 'Pending');
define('LAN_RACEREG_APPROVAL_1', 'Approved');
define('LAN_RACEREG_APPROVAL_2', 'Rejected');

/* ---- Payment fields ------------------------------------------------------- */
define('LAN_RACEREG_PAY_REGISTRATION', 'Registration');
define('LAN_RACEREG_PAY_AMOUNT',       'Amount');
define('LAN_RACEREG_PAY_STATUS',       'Status');
define('LAN_RACEREG_PAY_PAID_AT',      'Paid at');
define('LAN_RACEREG_PAY_NOTE',         'Note');
define('LAN_RACEREG_PAY_CREATED',      'Created');

/* ---- Payment status labels ----------------------------------------------- */
define('LAN_RACEREG_PAYST_0', 'Pending');
define('LAN_RACEREG_PAYST_1', 'Valid');
define('LAN_RACEREG_PAYST_2', 'Erroneous');
define('LAN_RACEREG_PAYST_3', 'Refunded');

/* ---- Messages / help ----------------------------------------------------- */
define('LAN_RACEREG_SOFT_DELETED', 'Registration marked as deleted.');
define('LAN_RACEREG_REG_HELP', 'Registrations hold personal data (PII): organizer-only, no front-end exposure. Deletes are soft (kept for audit / restore). The variable symbol is auto-generated and locked; amount due is entered manually in this version.');
define('LAN_RACEREG_PAY_HELP', 'Payments linked to a registration. A registration can hold multiple payment rows. Filter the list by registration using the filter box.');
define('LAN_RACEREG_CONFIG_DOC_HELP',
	'<strong>Configuration that drives sign-ups &amp; payments</strong><br>'
	. 'These fields live in the related plugins; misconfiguring them silently breaks registration or the QR code.'
	. '<br><br><strong>Payee (Event configuration &rarr; raceevent)</strong><br>'
	. 'The payment QR code (PAY by square) needs BOTH the Payee IBAN and the Beneficiary name. The beneficiary name is mandatory - bysquare cannot encode the QR without it - so an IBAN saved without a name is rejected. SWIFT / BIC is optional.'
	. '<br><br><strong>Registration window (raceevent)</strong><br>'
	. 'Sign-ups are accepted only between "Registration opens" and "Registration closes". Either side may be left empty (0) for no bound; if both are set, opening must be strictly before closing.'
	. '<br><br><strong>Per-track settings (Tracks &rarr; racetrack)</strong><br>'
	. 'Capacity = maximum on the start list; Unlimited capacity ignores it. Requires approval = sign-ups wait for the organizer. Registration closed = the track cannot be signed up for. Price tiers set the fee by date; a track open for registration with no price tier is treated as free ("bez poplatku").');

/* ---- Scaffold placeholder (kept for reference) --------------------------- */
define('LAN_RACEREG_SCAFFOLD_INFO', 'This is the racereg scaffold. Registration and payment features (sign-up flow, PAY by square QR, admin list / mark-paid) will appear here in later issues.');
define('LAN_RACEREG_CONFIG_HELP', 'Registration and payments for the race-timing suite. Depends on the raceevent (event) and race (tracks) plugins. This plugin will hold the heaviest personal data in the suite - keep its admin permission restricted.');

/* ---- Organizer actions (issue #26) --------------------------------------- */
define('LAN_RACEREG_PAID_STATUS',      'Paid');
define('LAN_RACEREG_PAID_STATUS_HELP', 'Derived from valid payments vs amount due. Display only - not stored.');
define('LAN_RACEREG_PAID_NOFEE',       'No fee');
define('LAN_RACEREG_PAID_UNPAID',      'Unpaid');
define('LAN_RACEREG_PAID_PARTIAL',     'Partial');
define('LAN_RACEREG_PAID_PAID',        'Paid');

define('LAN_RACEREG_ACT_APPROVE',  'Approve');
define('LAN_RACEREG_ACT_REJECT',   'Reject');
define('LAN_RACEREG_ACT_PROMOTE',  'Promote');
define('LAN_RACEREG_ACT_MARKPAID', 'Mark paid');
define('LAN_RACEREG_ACT_PAYMENT',  'Show payment details');
define('LAN_RACEREG_ACT_BACK',     'Back to list');

/* ---- Shared payment view + QR (issue #40) -------------------------------- */
define('LAN_RACEREG_PAY_PAYEE',     'Payee');
define('LAN_RACEREG_PAY_IBAN',      'IBAN');
define('LAN_RACEREG_PAY_SWIFT',     'SWIFT / BIC');
define('LAN_RACEREG_PAY_NO_IBAN',   'The payment account has not been configured yet.');
define('LAN_RACEREG_PAY_NOTE_TEXT', 'Use the variable symbol shown above as the payment reference.');
define('LAN_RACEREG_QR_TITLE',      'Pay by QR code');
define('LAN_RACEREG_QR_HINT',       'Scan this PAY by square code with your banking app to prefill the payment (IBAN, amount and variable symbol).');
define('LAN_RACEREG_PAY_LINK_NOTE', 'Public payment link for this registration (the applicant can use it to pay later):');
define('LAN_RACEREG_CONFIRM_REJECT', 'Reject this registration?');

define('LAN_RACEREG_MSG_PAID',            'Payment marked valid.');
define('LAN_RACEREG_MSG_RECORDED',        'Recorded a valid payment of [x] EUR.');
define('LAN_RACEREG_MSG_ALREADY_PAID',    'This registration is already fully paid.');
define('LAN_RACEREG_MSG_APPROVED_PLACED', 'Registration approved and placed on the start list.');
define('LAN_RACEREG_MSG_APPROVED_SUB',    'Registration approved; the track is full, kept as a substitute.');
define('LAN_RACEREG_MSG_REJECTED',        'Registration rejected.');
define('LAN_RACEREG_MSG_PROMOTED',        'Substitute promoted to the start list.');
define('LAN_RACEREG_MSG_FULL',            'The track is full - cannot place this registration.');
define('LAN_RACEREG_MSG_NOOP',            'No change was made.');
define('LAN_RACEREG_MSG_AUTOPROMOTED',    'A substitute was auto-promoted into the freed spot.');
define('LAN_RACEREG_MSG_CREATE_SUBSTITUTE', 'The track is full - this registration was added as a substitute.');

define('LAN_RACEREG_ERR_TOKEN',    'Invalid security token. Please try again.');
define('LAN_RACEREG_ERR_NOTFOUND', 'Registration or payment not found.');

/* ---- Paid-status quick filter -------------------------------------------- */
define('LAN_RACEREG_PAIDFILTER',     'Paid status');
define('LAN_RACEREG_PAIDFILTER_ALL', 'All');

/* ---- Registration-by-track overview -------------------------------------- */
define('LAN_RACEREG_RT_TITLE',    'Registrations by track');
define('LAN_RACEREG_RT_ID',       'Track ID');
define('LAN_RACEREG_RT_NAME',     'Track name');
define('LAN_RACEREG_RT_ALL',      'Registrations - all');
define('LAN_RACEREG_RT_APPROVED', 'Approved');
define('LAN_RACEREG_RT_REJECTED', 'Rejected');
define('LAN_RACEREG_RT_PENDING',  'Pending');
define('LAN_RACEREG_RT_NOFEE',    'No fee');
define('LAN_RACEREG_RT_PAID',     'Paid');
define('LAN_RACEREG_RT_STARTERS', 'Starters');
define('LAN_RACEREG_RT_NOTRACKS', 'No tracks found.');

/* ---- More info page ------------------------------------------------------ */
define('LAN_RACEREG_REG_FORM_LINK', 'Registration form');

/* ---- Confirmation-page preview (admin-only, inert) ----------------------- */
define('LAN_RACEREG_PREVIEW_TITLE',      'Confirmation page preview');
define('LAN_RACEREG_PREVIEW_INFO',       "Admin-only preview. Shows the applicant's confirmation page with sample data - nothing is saved and no e-mail is sent.");
define('LAN_RACEREG_PREVIEW_STARTLIST',  'In start list');
define('LAN_RACEREG_PREVIEW_SUBSTITUTE', 'Substitute');
define('LAN_RACEREG_PREVIEW_PENDING',    'Pending payment');
define('LAN_RACEREG_PREVIEW_NOQR',       "Payee payment details are not set in the event configuration, so the PAY-by-square QR code will not be shown in the preview.");

/* ---- Notify (Admin -> Notify, e_notify.php) ------------------------------ */
define('LAN_RACEREG_NT_SIGNUP', 'New registration submitted');
define('LAN_RACEREG_NT_SIGNUP_MSG', 'A new registration was submitted.<br><br>Name: [name]<br>Track: [track]<br>Variable symbol: [vs]<br>Amount: [amount]<br><br>Detail: [link]');
