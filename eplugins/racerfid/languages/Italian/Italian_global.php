<?php
/*
 * e107 website system
 *
 * racerfid plugin - Italian global language file.
 *
 * Array-style LAN file loaded via e107::lan('racerfid', 'global', true).
 * Overrides the English_global.php base per key; any key omitted here falls
 * back to the English value by design.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_GLOBAL_RACERFID_001' => "Importazione RFID",
    'LAN_GLOBAL_RACERFID_002' => "Importazione dei tempi di passaggio dai chip RFID",
    'LAN_GLOBAL_RACERFID_003' => "Plugin opzionale della suite di cronometraggio. Legge i record di passaggio da un database esterno di un lettore di chip RFID e li importa nel motore di cronometraggio (tabella race_time di racetiming), mappati sui punti di controllo tramite la tabella race_point di racetrack. Indipendente: si installa autonomamente e non dichiara dipendenze.",
);
