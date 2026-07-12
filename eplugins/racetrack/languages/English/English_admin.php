<?php
/*
 * e107 website system
 *
 * race plugin - English admin language file (issue #37).
 *
 * Array-style LAN file (returns the terms) loaded via
 * e107::lan('racetrack', true, true). Array-style is used on purpose: e107's
 * includeLan() only applies the per-key English fallback for missing
 * translations when the language file RETURNS an array, so a partial Slovak
 * file (languages/Slovak/Slovak_admin.php) degrades cleanly to these English
 * strings instead of leaving constants undefined. This file is the canonical,
 * complete set; the Slovak file overrides per key.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Track edit form.
    'LAN_ADMIN_RACE_001'      => 'Track SEF URL',
    'LAN_ADMIN_RACE_001_HELP' => 'Part of the URL where the race data is shown. Current site + functionality + SEF URL.',
    'LAN_ADMIN_RACE_002'      => 'Archive SEF URL',
    'LAN_ADMIN_RACE_002_HELP' => 'Part of the URL where the race archive is shown. Current site + archive SEF URL. It can differ from the active race SEF URL and should contain the year because of races repeated in following years.',

    'LAN_ADMIN_RACE_003'      => 'Track list',
    'LAN_ADMIN_RACE_004'      => 'Add track',

    'LAN_ADMIN_POINTS'        => 'Checkpoints',
    'LAN_ADMIN_POINTS_ADD'    => 'Add checkpoint',

    // --- Archive admin (race_archive) + race-row "Archive" button ------------
    'LAN_ADMIN_ARCHIVE'            => 'Archive',
    'LAN_ADMIN_ARCHIVE_ADD'        => 'Add archive',
    'LAN_ADMIN_ARCHIVE_TRACK'      => 'Track',
    'LAN_ADMIN_ARCHIVE_UNLINKED'  => 'Unlinked archive',
    'LAN_ADMIN_ARCHIVE_CREATED'   => 'Created',
    'LAN_ADMIN_ARCHIVE_UPDATED'   => 'Updated',
    // Button captions (Slovak terms kept verbatim per the product wording).
    'LAN_ADMIN_ARCHIVE_ARCHIVOVAT' => 'Archive',          // race-row button
    'LAN_ADMIN_ARCHIVE_REGENERATE' => 'Regenerate',       // archive-row button (Pregenerovat)
    'LAN_ADMIN_ARCHIVE_VIEW'       => 'View',             // archive-row quick view (Zobrazit)
    // Generate result / guard messages.
    'LAN_ADMIN_ARCHIVE_MSG_CREATED'  => 'Archive created',
    'LAN_ADMIN_ARCHIVE_MSG_UPDATED'  => 'Archive updated',
    'LAN_ADMIN_ARCHIVE_MSG_FAIL'     => 'Archive generation failed',
    'LAN_ADMIN_ARCHIVE_MSG_NORACE'   => 'Race not found',
    'LAN_ADMIN_ARCHIVE_MSG_NO_RR'    => 'The Racereports plugin is not installed - the archive cannot be generated.',
    'LAN_ADMIN_ARCHIVE_MSG_BAD_TOKEN' => 'Invalid security token - the request was ignored.',
    'LAN_ADMIN_ARCHIVE_NOTE'         => 'A linked archive (Track set) can be regenerated with the Regenerate button. View always shows the frozen snapshot and never regenerates. Unlinking (Track = Unlinked archive) keeps the row but hides Regenerate.',

    // Track edit form tab label (issue #34).
    'LAN_ADMIN_RACE_TAB_TRACK' => 'Track',
    // Opt-in Registration tab label (shown only when racereg is installed).
    'LAN_ADMIN_RACE_TAB_REG'   => 'Registration',

    // --- Registration-config + price-tier admin strings (issue #30) ----------
    // Track registration flags + capacity.
    'LAN_ADMIN_RACE_CAPACITY'       => 'Capacity',
    'LAN_ADMIN_RACE_CAPACITY_HELP'  => 'Maximum number of racers on the start list. Ignored when unlimited capacity is on.',
    'LAN_ADMIN_RACE_UNLIMITED'      => 'Unlimited capacity',
    'LAN_ADMIN_RACE_UNLIMITED_HELP' => 'When on, capacity is not checked and everyone is placed on the start list.',
    'LAN_ADMIN_RACE_APPROVAL'       => 'Requires approval',
    'LAN_ADMIN_RACE_APPROVAL_HELP'  => 'When on, sign-ups wait for approval and are placed on the start list only when approved (not automatically by capacity).',
    'LAN_ADMIN_RACE_CLOSED'         => 'Registration closed',
    'LAN_ADMIN_RACE_CLOSED_HELP'    => 'When on, this track cannot be signed up for.',

    // Date-tiered price tiers (child table).
    'LAN_ADMIN_PRICES'              => 'Price tiers',
    'LAN_ADMIN_PRICES_ADD'          => 'Add price tier',
    'LAN_ADMIN_PRICE_TRACK'         => 'Track',
    'LAN_ADMIN_PRICE_VALUE'         => 'Price',
    'LAN_ADMIN_PRICE_VALUE_HELP'    => 'Amount in EUR (e.g. 15.00).',
    'LAN_ADMIN_PRICE_FROM'          => 'Valid from',
    'LAN_ADMIN_PRICE_FROM_HELP'     => 'Date and time from which this price applies. The tier with the latest date that is <= the current time applies to a sign-up.',

    // --- Save-time warnings for an open track (issue #47) --------------------
    'LAN_ADMIN_RACE_CAP_WARN'  => 'This track is open for registration but its capacity is 0 and it is not set to unlimited - nobody can be placed on the start list. Set a capacity or turn on unlimited capacity.',
    'LAN_ADMIN_RACE_FREE_WARN' => 'This track is open for registration but has no price tier - sign-ups will be treated as free ("bez poplatku"). Add a price tier if a fee should be charged.',

    // --- Cross-plugin admin-menu shortcuts + not-installed warnings ----------
 
    // --- Track-config help page (issue #47) ----------------------------------
    'LAN_ADMIN_RACE_TRACK_HELP' =>
        '<strong>Registration settings per track</strong><br>'
        . '<strong>Capacity</strong> - maximum number of racers on the start list. '
        . '<strong>Unlimited capacity</strong> - when on, capacity is ignored and everyone is placed. '
        . 'An open track (Registration closed = off) with capacity 0 and not unlimited can place nobody - you will be warned on save.<br>'
        . '<strong>Requires approval</strong> - sign-ups wait for the organizer and are placed only when approved (not automatically by capacity).<br>'
        . '<strong>Registration closed</strong> - when on, this track cannot be signed up for.<br>'
        . '<strong>Price tiers</strong> - date-tiered fees (Price tiers menu); the tier with the latest "Valid from" that is &lt;= now applies. '
        . 'An open track with no price tier is treated as free ("bez poplatku") - you will be warned on save.<br>'
        . '<em>Checkpoints for start and finish must use the codes start and finish, otherwise they show as ordinary checkpoints.</em>',
);
