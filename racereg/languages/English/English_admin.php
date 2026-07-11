<?php
/*
 * e107 website system
 *
 * racereg plugin - English admin strings.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_RACEREG_PLUGIN' => "Race registration",

    /* ---- Admin menu ---------------------------------------------------------- */
    'LAN_RACEREG_CONFIG' => "Configuration",
    'LAN_RACEREG_REG_LIST' => "Registrations",
    'LAN_RACEREG_REG_CREATE' => "Add registration",
    'LAN_RACEREG_REG_INFO' => "More info",
    'LAN_RACEREG_PAY_LIST' => "Payments",
    'LAN_RACEREG_PAY_CREATE' => "Add payment",

    /* ---- More info page ------------------------------------------------------ */
    'LAN_RACEREG_REG_FORM_LINK' => "Registration form",

    /* ---- Registration fields ------------------------------------------------- */
    'LAN_RACEREG_TRACK' => "Track",
    'LAN_RACEREG_FIRST_NAME' => "First name",
    'LAN_RACEREG_LAST_NAME' => "Last name",
    'LAN_RACEREG_BIRTH_DATE' => "Birth date",
    'LAN_RACEREG_STREET' => "Street",
    'LAN_RACEREG_CITY' => "City",
    'LAN_RACEREG_POSTAL' => "Postal code",
    'LAN_RACEREG_COUNTRY' => "Country",
    'LAN_RACEREG_EMAIL' => "Email",
    'LAN_RACEREG_PHONE' => "Phone",
    'LAN_RACEREG_CLUB' => "Club",
    'LAN_RACEREG_REG_DATE' => "Registration date",
    'LAN_RACEREG_START_LIST_AT' => "On start list (date)",
    'LAN_RACEREG_VS' => "Variable symbol",
    'LAN_RACEREG_VS_HELP' => "Auto-generated unique numeric symbol. Locked after creation.",
    'LAN_RACEREG_AMOUNT_DUE' => "Amount due",
    'LAN_RACEREG_AMOUNT_DUE_HELP' => "Entered manually in this version. Automatic price freeze comes later.",
    'LAN_RACEREG_APPROVAL' => "Approval status",
    'LAN_RACEREG_PRIVATE_NOTE' => "Private note",

    /* ---- Approval status labels ---------------------------------------------- */
    'LAN_RACEREG_APPROVAL_0' => "Pending",
    'LAN_RACEREG_APPROVAL_1' => "Approved",
    'LAN_RACEREG_APPROVAL_2' => "Rejected",

    /* ---- Payment fields ------------------------------------------------------- */
    'LAN_RACEREG_PAY_REGISTRATION' => "Registration",
    'LAN_RACEREG_PAY_AMOUNT' => "Amount",
    'LAN_RACEREG_PAY_STATUS' => "Status",
    'LAN_RACEREG_PAY_PAID_AT' => "Paid at",
    'LAN_RACEREG_PAY_NOTE' => "Note",
    'LAN_RACEREG_PAY_CREATED' => "Created",

    /* ---- Payment status labels ----------------------------------------------- */
    'LAN_RACEREG_PAYST_0' => "Pending",
    'LAN_RACEREG_PAYST_1' => "Valid",
    'LAN_RACEREG_PAYST_2' => "Erroneous",
    'LAN_RACEREG_PAYST_3' => "Refunded",

    /* ---- Messages / help ----------------------------------------------------- */
    'LAN_RACEREG_SOFT_DELETED' => "Registration marked as deleted.",
    'LAN_RACEREG_REG_HELP' => "Registrations hold personal data (PII): organizer-only, no front-end exposure. Deletes are soft (kept for audit / restore). The variable symbol is auto-generated and locked; amount due is entered manually in this version.",
    'LAN_RACEREG_PAY_HELP' => "Payments linked to a registration. A registration can hold multiple payment rows. Filter the list by registration using the filter box.",
    'LAN_RACEREG_CONFIG_DOC_HELP' => 
        "<strong>Configuration that drives sign-ups &amp; payments</strong><br>"
        . "These fields live in the related plugins; misconfiguring them silently breaks registration or the QR code."
        . "<br><br><strong>Payee (Event configuration &rarr; raceevent)</strong><br>"
        . "The payment QR code (PAY by square) needs BOTH the Payee IBAN and the Beneficiary name. The beneficiary name is mandatory - bysquare cannot encode the QR without it - so an IBAN saved without a name is rejected. SWIFT / BIC is optional."
        . "<br><br><strong>Registration window (raceevent)</strong><br>"
        . "Sign-ups are accepted only between \"Registration opens\" and \"Registration closes\". Either side may be left empty (0) for no bound; if both are set, opening must be strictly before closing."
        . "<br><br><strong>Per-track settings (Tracks &rarr; racetrack)</strong><br>"
        . "Capacity = maximum on the start list; Unlimited capacity ignores it. Requires approval = sign-ups wait for the organizer. Registration closed = the track cannot be signed up for. Price tiers set the fee by date; a track open for registration with no price tier is treated as free (\"bez poplatku\").",

    /* ---- Scaffold placeholder (kept for reference) --------------------------- */
    'LAN_RACEREG_SCAFFOLD_INFO' => "This is the racereg scaffold. Registration and payment features (sign-up flow, PAY by square QR, admin list / mark-paid) will appear here in later issues.",
    'LAN_RACEREG_CONFIG_HELP' => "Registration and payments for the race-timing suite. Depends on the raceevent (event) and race (tracks) plugins. This plugin will hold the heaviest personal data in the suite - keep its admin permission restricted.",

    /* ---- Organizer actions (issue #26) --------------------------------------- */
    'LAN_RACEREG_PAID_STATUS' => "Paid",
    'LAN_RACEREG_PAID_STATUS_HELP' => "Derived from valid payments vs amount due. Display only - not stored.",
    'LAN_RACEREG_PAID_NOFEE' => "No fee",
    'LAN_RACEREG_PAID_UNPAID' => "Unpaid",
    'LAN_RACEREG_PAID_PARTIAL' => "Partial",
    'LAN_RACEREG_PAID_PAID' => "Paid",

    'LAN_RACEREG_ACT_APPROVE' => "Approve",
    'LAN_RACEREG_ACT_REJECT' => "Reject",
    'LAN_RACEREG_ACT_PROMOTE' => "Promote",
    'LAN_RACEREG_ACT_MARKPAID' => "Mark paid",
    'LAN_RACEREG_ACT_PAYMENT' => "Show payment details",
    'LAN_RACEREG_ACT_BACK' => "Back to list",

    /* ---- Shared payment view + QR (issue #40) -------------------------------- */
    'LAN_RACEREG_PAY_PAYEE' => "Payee",
    'LAN_RACEREG_PAY_IBAN' => "IBAN",
    'LAN_RACEREG_PAY_SWIFT' => "SWIFT / BIC",
    'LAN_RACEREG_PAY_NO_IBAN' => "The payment account has not been configured yet.",
    'LAN_RACEREG_PAY_NOTE_TEXT' => "Use the variable symbol shown above as the payment reference.",
    'LAN_RACEREG_QR_TITLE' => "Pay by QR code",
    'LAN_RACEREG_QR_HINT' => "Scan this PAY by square code with your banking app to prefill the payment (IBAN, amount and variable symbol).",
    'LAN_RACEREG_PAY_LINK_NOTE' => "Public payment link for this registration (the applicant can use it to pay later):",
    'LAN_RACEREG_CONFIRM_REJECT' => "Reject this registration?",

    'LAN_RACEREG_MSG_PAID' => "Payment marked valid.",
    'LAN_RACEREG_MSG_RECORDED' => "Recorded a valid payment of [x] EUR.",
    'LAN_RACEREG_MSG_ALREADY_PAID' => "This registration is already fully paid.",
    'LAN_RACEREG_MSG_APPROVED_PLACED' => "Registration approved and placed on the start list.",
    'LAN_RACEREG_MSG_APPROVED_SUB' => "Registration approved; the track is full, kept as a substitute.",
    'LAN_RACEREG_MSG_REJECTED' => "Registration rejected.",
    'LAN_RACEREG_MSG_PROMOTED' => "Substitute promoted to the start list.",
    'LAN_RACEREG_MSG_FULL' => "The track is full - cannot place this registration.",
    'LAN_RACEREG_MSG_NOOP' => "No change was made.",
    'LAN_RACEREG_MSG_AUTOPROMOTED' => "A substitute was auto-promoted into the freed spot.",
    'LAN_RACEREG_MSG_CREATE_SUBSTITUTE' => "The track is full - this registration was added as a substitute.",

    'LAN_RACEREG_ERR_TOKEN' => "Invalid security token. Please try again.",
    'LAN_RACEREG_ERR_NOTFOUND' => "Registration or payment not found.",

    /* ---- Paid-status quick filter -------------------------------------------- */
    'LAN_RACEREG_PAIDFILTER' => "Paid status",
    'LAN_RACEREG_PAIDFILTER_ALL' => "All",

    /* ---- Registration-by-track overview -------------------------------------- */
    'LAN_RACEREG_RT_TITLE' => "Registrations by track",
    'LAN_RACEREG_RT_ID' => "Track ID",
    'LAN_RACEREG_RT_NAME' => "Track name",
    'LAN_RACEREG_RT_ALL' => "Registrations - all",
    'LAN_RACEREG_RT_APPROVED' => "Approved",
    'LAN_RACEREG_RT_REJECTED' => "Rejected",
    'LAN_RACEREG_RT_PENDING' => "Pending",
    'LAN_RACEREG_RT_NOFEE' => "No fee",
    'LAN_RACEREG_RT_PAID' => "Paid",
    'LAN_RACEREG_RT_STARTERS' => "Starters",
    'LAN_RACEREG_RT_NOTRACKS' => "No tracks found.",

    /* ---- Confirmation-page preview (admin-only, inert) ----------------------- */
    'LAN_RACEREG_PREVIEW_TITLE' => "Confirmation page preview",
    'LAN_RACEREG_PREVIEW_INFO' => "Admin-only preview. Shows the applicant's confirmation page with sample data - nothing is saved and no e-mail is sent.",
    'LAN_RACEREG_PREVIEW_STARTLIST' => "In start list",
    'LAN_RACEREG_PREVIEW_SUBSTITUTE' => "Substitute",
    'LAN_RACEREG_PREVIEW_PENDING' => "Pending payment",
    'LAN_RACEREG_PREVIEW_NOQR' => "Payee payment details are not set in the event configuration, so the PAY-by-square QR code will not be shown in the preview.",

    /* ---- Notify (Admin -> Notify, e_notify.php) ------------------------------ */
    'LAN_RACEREG_NT_SIGNUP' => "New registration submitted",
    'LAN_RACEREG_NT_SIGNUP_MSG' => "A new registration was submitted.<br><br>Name: [name]<br>Track: [track]<br>Variable symbol: [vs]<br>Amount: [amount]<br><br>Detail: [link]",
);
