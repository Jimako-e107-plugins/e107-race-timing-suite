<?php
/*
 * e107 website system
 *
 * racetiming plugin - English admin language file.
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
    // Admin placeholder screen (admin/admin_config.php).
    // Admin permission label.
    'LAN_ADMIN_RACETIMING_003' => 'Race timing administration',

    // --- race_time CRUD admin (admin/admin_config.php) ---
    // Dispatcher menu captions.
    'LAN_ADMIN_RACETIMING_010' => 'Time Entries',
    'LAN_ADMIN_RACETIMING_011' => 'Add Time Entry',
    // race_time field labels (titles + help).
    'LAN_ADMIN_RACETIMING_020' => 'Control Point / Checkpoint',
    'LAN_ADMIN_RACETIMING_021' => 'Select control point / checkpoint',
    'LAN_ADMIN_RACETIMING_022' => 'Bib / Start Number',
    'LAN_ADMIN_RACETIMING_023' => 'Enter bib/start number (including leading zeros)',
    'LAN_ADMIN_RACETIMING_024' => 'Measured Time',
    'LAN_ADMIN_RACETIMING_025' => 'Measured time at control point',
    'LAN_ADMIN_RACETIMING_026' => 'Status / Finish Status',
    'LAN_ADMIN_RACETIMING_027' => 'DNF = Did Not Finish, DSQ = Disqualified, DNS = Did Not Start',
    'LAN_ADMIN_RACETIMING_028' => 'Created',
    'LAN_ADMIN_RACETIMING_029' => 'Updated',

    // --- bulk start-generation (admin/admin_generujstart.php) ---
    // Relocated from timetracker (LAN_TR_* there). 040 = prefs tab label,
    // 041/042 = the `starttime` pref title/help, 043 = generation page menu,
    // 044-051 = the generation form + result strings, 052 = prefs page menu,
    // 053 = warning shown when the form is submitted with no track selected.
    'LAN_ADMIN_RACETIMING_040' => 'Start generation',
    'LAN_ADMIN_RACETIMING_041' => 'Default start time',
    'LAN_ADMIN_RACETIMING_042' => 'Used as the default value when generating the start.',
    'LAN_ADMIN_RACETIMING_043' => 'Generate Start',
    'LAN_ADMIN_RACETIMING_044' => 'Select Races and Time',
    'LAN_ADMIN_RACETIMING_045' => 'Measured Time',
    'LAN_ADMIN_RACETIMING_046' => 'Set Current Time',
    'LAN_ADMIN_RACETIMING_047' => 'Generate Start Times',
    'LAN_ADMIN_RACETIMING_048' => 'Generating race ID {ID}',
    'LAN_ADMIN_RACETIMING_049' => 'This racer already has generated start',
    'LAN_ADMIN_RACETIMING_050' => 'Start generated',
    'LAN_ADMIN_RACETIMING_051' => 'Racer did not start',
    'LAN_ADMIN_RACETIMING_052' => 'Start time setting',
    'LAN_ADMIN_RACETIMING_053' => 'No track selected',
);
