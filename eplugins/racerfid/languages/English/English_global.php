<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racerfid plugin - languages/English/English_global.php
 *
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
