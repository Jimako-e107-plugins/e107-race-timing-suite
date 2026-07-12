<?php
/*
 * e107 website system
 *
 * racerfid plugin - Slovak global language file.
 *
 * Array-style LAN file loaded via e107::lan('racerfid', 'global', true).
 * Overrides the English_global.php base per key; missing terms fall back to
 * English.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_GLOBAL_RACERFID_001' => "Import z RFID čipov",
    'LAN_GLOBAL_RACERFID_002' => "Import časov z RFID čipov",
    'LAN_GLOBAL_RACERFID_003' => "Voliteľný plugin sady na časomieru. Číta záznamy prejazdov z externej databázy čítačky RFID čipov a importuje ich do meracieho enginu (tabuľka race_time pluginu racetiming), namapované na kontrolné body cez tabuľku race_point pluginu racetrack. Nezávislý: inštaluje sa samostatne a nedeklaruje žiadne závislosti.",
);
