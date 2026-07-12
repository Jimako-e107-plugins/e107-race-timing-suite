<?php
/*
 * e107 website system
 *
 * racerfid plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('racerfid', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary/description).
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Plugin display name (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACERFID_001' => "RFID import",
    // Summary (plugin.xml <summary lan="...">).
    'LAN_GLOBAL_RACERFID_002' => "Import of passing times from RFID chips",
    // Description (plugin.xml <description lan="...">).
    'LAN_GLOBAL_RACERFID_003' => "Optional plugin of the race-timing suite. Reads passing records from an external RFID chip-reader database and imports them into the timing engine (racetiming's race_time table), mapped onto checkpoints via racetrack's race_point table. Independent: it installs on its own and declares no dependencies.",
);
