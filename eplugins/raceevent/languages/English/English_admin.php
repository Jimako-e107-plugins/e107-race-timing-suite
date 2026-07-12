<?php
/*
 * e107 website system
 *
 * raceevent base plugin - English admin strings.
 */

if (!defined('e107_INIT')) { exit; }

return array(

    /* ---- Admin menu ---------------------------------------------------------- */
    'LAN_RACEEVENT_CONFIG' => "Event configuration",

    /* ---- Cross-plugin admin-menu shortcuts (raceevent_admin_links helper) ----- */
    'LAN_RACEEVENT_LINK_EVENT' => "» Event",
    'LAN_RACEEVENT_LINK_TRACKS' => "» Tracks",
    'LAN_RACEEVENT_LINK_ARCHIVE' => "» Archive",
    'LAN_RACEEVENT_LINK_RACERS' => "» Racers",
    'LAN_RACEEVENT_LINK_CATEGORIES' => "» Categories",
    'LAN_RACEEVENT_LINK_TERMINOVKA' => "» Terminovka.sk",
    'LAN_RACEEVENT_LINK_REGISTRATION' => "» Registration",
    'LAN_RACEEVENT_LINK_RFID' => "» RFID import",
    'LAN_RACEEVENT_LINK_TIMING' => "» Timing",
    'LAN_RACEEVENT_LINK_REPORTS' => "» Reports",

    'LAN_RACEEVENT_TAB_EVENT' => "Event",
    'LAN_RACEEVENT_TAB_REGISTRATION' => "Registration",

    /* ---- Event fields (plugin prefs) ----------------------------------------- */
    'LAN_RACEEVENT_NAME' => "Event name",
    'LAN_RACEEVENT_DATE' => "Event date",
    'LAN_RACEEVENT_CITY' => "Town / village",
    'LAN_RACEEVENT_LOCATION' => "Venue / base",
    'LAN_RACEEVENT_DESCRIPTION' => "Description",
    'LAN_RACEEVENT_ORGANIZER' => "Organizer",

    /* ---- Event flags (plugin prefs) ------------------------------------------ */
    'LAN_RACEEVENT_IS_CHARITY' => "Charity event",
    'LAN_RACEEVENT_IS_CHILDREN_RUNS' => "Children runs included",
    'LAN_RACEEVENT_IS_DOG_ALLOWED' => "Participation with a dog allowed",

    /* ---- Registration window + payee (plugin prefs) -------------------------- */
    'LAN_RACEEVENT_REG_START' => "Registration opens",
    'LAN_RACEEVENT_REG_START_HELP' => "Sign-ups are accepted from this moment. Leave empty (0) for no lower bound. If both bounds are set, opening must be before closing.",
    'LAN_RACEEVENT_REG_END' => "Registration closes",
    'LAN_RACEEVENT_REG_END_HELP' => "Sign-ups are accepted until this moment. Leave empty (0) for no upper bound. If both bounds are set, closing must be after opening.",
    'LAN_RACEEVENT_PAYEE_IBAN' => "Payee IBAN",
    'LAN_RACEEVENT_PAYEE_IBAN_HELP' => "Bank account (IBAN) shown as the payment target on the registration confirmation page. Spaces are removed automatically. Required (together with the beneficiary name) for the payment QR code.",
    'LAN_RACEEVENT_PAYEE_NAME' => "Beneficiary name",
    'LAN_RACEEVENT_PAYEE_NAME_HELP' => "Account-holder / beneficiary name shown with the payment details. Required whenever an IBAN is set: PAY by square cannot generate the QR code without it.",
    'LAN_RACEEVENT_PAYEE_SWIFT' => "Payee SWIFT / BIC",
    'LAN_RACEEVENT_PAYEE_SWIFT_HELP' => "Optional bank identifier (BIC), 8 or 11 characters. Spaces are removed automatically.",
    'LAN_RACEEVENT_IBAN_WARN' => "The IBAN was saved but does not look valid (format / checksum). Please double-check it.",
    'LAN_RACEEVENT_SWIFT_WARN' => "The SWIFT / BIC was saved but does not look valid (expected 8 or 11 characters). Please double-check it.",
    'LAN_RACEEVENT_PAYEE_NAME_REQUIRED' => "A beneficiary name is required when an IBAN is set, otherwise the payment QR code cannot be generated. Your change was not saved - add the beneficiary name and save again.",
    'LAN_RACEEVENT_REG_WINDOW_INVALID' => "Registration cannot close before (or at the same time as) it opens. Your change was not saved - set the closing time after the opening time.",
    'LAN_RACEEVENT_PAYEE_INCOMPLETE_WARN' => "A registration window is set but the payee block is incomplete (IBAN or beneficiary name missing). The payment QR code and instructions will not work until both are filled in.",

    /* ---- Help ---------------------------------------------------------------- */
    'LAN_RACEEVENT_CONFIG_HELP' => "Configure the event. This is a single-event installation: the event is stored as plugin preferences (not database rows) and shared with the rest of the race-timing suite via e107::getPlugConfig('raceevent')."
        . "<hr><strong>Payment / QR (required fields)</strong><br>"
        . "The payment QR code (PAY by square) needs BOTH the Payee IBAN and the Beneficiary name. The beneficiary name is mandatory - bysquare cannot encode the QR without it, so an IBAN set without a name is rejected on save. The SWIFT / BIC is optional."
        . "<hr><strong>Registration window</strong><br>"
        . "Sign-ups are accepted only between Registration opens and Registration closes. Either side may be left empty (0) for no bound. If both are set, opening must be strictly before closing - otherwise the save is rejected. If a window is set but the payee block is incomplete, you will be warned that payments / the QR will not work.",

    /* --- Maintenance page (udrzba) --- */
    'LAN_TR_UDRZBA_ARCHIVE_WARN_TITLE' => "Check the archive before starting a new season!",
    'LAN_TR_UDRZBA_ARCHIVE_WARN_LINKED' => "[x] archive records are still linked to a track.",
    'LAN_TR_UDRZBA_ARCHIVE_WARN_ADVICE' => "We recommend unlinking all of them, otherwise they may be updated or prevent deleting races.",

    'LAN_TR_UDRZBA_EXECUTE' => "Execute",
    'LAN_TR_UDRZBA_EDIT_PREFS' => "Edit preferences",

    'LAN_TR_UDRZBA_ACTIVE_HEADING' => "Maintenance - active tables",
    'LAN_TR_UDRZBA_CLEAN' => "Clean",
    'LAN_TR_UDRZBA_ITEM' => "Item",
    'LAN_TR_UDRZBA_COUNT' => "Record count",

    'LAN_TR_UDRZBA_LEGACY_HEADING' => "Legacy / old tables",
    'LAN_TR_UDRZBA_DROP' => "DROP",
    'LAN_TR_UDRZBA_TABLE_DESC' => "Table / description",
    'LAN_TR_UDRZBA_STATUS' => "Status",
    'LAN_TR_UDRZBA_NOTEXIST' => "does not exist",
    'LAN_TR_UDRZBA_EMPTY' => "empty",
    'LAN_TR_UDRZBA_ROWS' => "rows",

    'LAN_TR_UDRZBA_PLUGINS_HEADING' => "Required plugins check",
    'LAN_TR_UDRZBA_PLUGIN' => "Plugin",
    'LAN_TR_UDRZBA_DESC' => "Description",
    'LAN_TR_UDRZBA_MANDATORY' => "Mandatory",
    'LAN_TR_UDRZBA_PREFS' => "Preferences",
    'LAN_TR_UDRZBA_PLUGIN_MISSING' => "Missing in eplugins",
    'LAN_TR_UDRZBA_PLUGIN_DISABLED' => "Disabled",
    'LAN_TR_UDRZBA_PLUGIN_ACTIVE' => "Active",
    'LAN_TR_UDRZBA_YES' => "YES",
    'LAN_TR_UDRZBA_NO' => "No",
    'LAN_TR_UDRZBA_NONE' => "none",

    /* --- Maintenance action messages --- */
    'LAN_TR_UDRZBA_MSG_BAD_TOKEN' => "Invalid security token. Action aborted.",
    'LAN_TR_UDRZBA_MSG_CLEANED' => "Table [x] cleaned ([y] rows).",
    'LAN_TR_UDRZBA_MSG_LEGACY_CLEANED' => "Legacy table [x] cleaned.",
    'LAN_TR_UDRZBA_MSG_LEGACY_DROPPED' => "Legacy table [x] removed (DROP).",
    'LAN_TR_UDRZBA_MSG_INVALID_TABLE' => "Invalid table for deletion.",
    'LAN_TR_UDRZBA_MSG_TRACKS_BLOCKED' => "Tracks cannot be cleared while other tables still contain data. Clear the other tables first.",
    'LAN_TR_UDRZBA_TRACKS_HINT' => "clear the other tables first",

    /* --- Maintenance table labels --- */
    'LAN_TR_TBL_RACES' => "Tracks",
    'LAN_TR_TBL_POINTS' => "Checkpoints",
    'LAN_TR_TBL_CATEGORIES' => "Categories",
    'LAN_TR_TBL_RACERS' => "Racers / start numbers",
    'LAN_TR_TBL_RESULTS' => "Results",
    'LAN_TR_TBL_TIMES' => "Checkpoint times",
    'LAN_TR_TBL_READER' => "Reader",
    'LAN_TR_TBL_OLD_RACERS' => "Old racer list",

    /* --- Required plugins (titles / descriptions) --- */
    'LAN_TR_PLUG_RACEREPORTS' => "Racereports (finish lists)",
    'LAN_TR_PLUG_RACEREPORTS_DESC' => "Main plugin for time measurement, checkpoints and results.",
    'LAN_TR_PLUG_RACE' => "Race (basic races)",
    'LAN_TR_PLUG_RACE_DESC' => "Base plugin for defining races and tracks.",
    'LAN_TR_PLUG_RACERS' => "Racers (competitors)",
    'LAN_TR_PLUG_RACERS_DESC' => "Management of racers, categories and start numbers.",
    'LAN_TR_PLUG_RACETRACKING' => "RFID import (reader)",
    'LAN_TR_PLUG_RACETRACKING_DESC' => "Imports times from the RFID reader.",
    'LAN_TR_PLUG_TERMINOVKA' => "Terminovka.sk export",
    'LAN_TR_PLUG_TERMINOVKA_DESC' => "Optional export of results to terminovka.sk.",
    'LAN_TR_PLUG_REGISTRACIA' => "Registration",
    'LAN_TR_PLUG_REGISTRACIA_DESC' => "Race sign-up (planned).",

    /* --- Navigation check (checklinks) --- */
    'LAN_RACEEVENT_CHECKLINKS' => "Navigation check",
    'LAN_RACEEVENT_CHECKLINKS_HELP' => "Lists navigation links that call a plugin function (link_function = plugin::method). It flags links whose plugin or function no longer exists, and links whose owner does not match the called plugin. Broken links can be hidden (set to the \"nobody\" userclass); edit or delete them in Site Links.",
    'LAN_RACEEVENT_CL_HEADING' => "Function-driven navigation links",
    'LAN_RACEEVENT_CL_COL_LINK' => "Link",
    'LAN_RACEEVENT_CL_COL_FUNCTION' => "Function",
    'LAN_RACEEVENT_CL_COL_PLUGIN' => "Plugin",
    'LAN_RACEEVENT_CL_COL_METHOD' => "Method",
    'LAN_RACEEVENT_CL_COL_OWNER' => "Owner",
    'LAN_RACEEVENT_CL_COL_STATUS' => "Status",
    'LAN_RACEEVENT_CL_COL_ACTION' => "Action",
    'LAN_RACEEVENT_CL_OK' => "OK",
    'LAN_RACEEVENT_CL_BROKEN_PLUGIN' => "Plugin missing",
    'LAN_RACEEVENT_CL_BROKEN_METHOD' => "Function missing",
    'LAN_RACEEVENT_CL_MALFORMED' => "Malformed function",
    'LAN_RACEEVENT_CL_OWNER_MISMATCH' => "Owner / function mismatch",
    'LAN_RACEEVENT_CL_HIDE' => "Hide",
    'LAN_RACEEVENT_CL_EDIT' => "Edit",
    'LAN_RACEEVENT_CL_EXECUTE' => "Hide selected",
    'LAN_RACEEVENT_CL_ALREADY_HIDDEN' => "already hidden",
    'LAN_RACEEVENT_CL_NONE' => "No function-driven navigation links found.",
    'LAN_RACEEVENT_CL_MSG_BAD_TOKEN' => "Invalid security token. Action aborted.",
    'LAN_RACEEVENT_CL_MSG_HIDDEN' => "Link [x] hidden (set to nobody).",

    /* --- Event overview (Prehľad preteku) admin screen --- */
    'LAN_RACEEVENT_OV_MENU' => "Event overview",
    'LAN_RACEEVENT_OV_HELP' => "A cross-suite directory of links to every race report (the same output as the public \"preteky\" page). For each link it shows whether the report's target file exists on disk: green = done, red = missing or the route is not registered (e.g. the start report until it is built). Read-only.",
);
