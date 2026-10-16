<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - languages/English/English_global.php
 *
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
