<?php
/*
 * e107 website system
 *
 * racetiming plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('racetiming', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary) and the admin
 * dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Plugin display name (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACETIMING_001' => 'Race timing',
    // Summary (plugin.xml <summary lan="...">).
    'LAN_GLOBAL_RACETIMING_002' => 'Checkpoint timing engine',
    // Description (plugin.xml <description lan="...">).
    'LAN_GLOBAL_RACETIMING_003' => 'Timing engine of the race-timing suite. Records passing times at checkpoints - by hand on a mobile keypad, in bulk for a mass start, or fed in from RFID - and computes elapsed times and splits live during the race. Owns the race_time table; stores only raw passings, everything else is calculated on read.',
);
