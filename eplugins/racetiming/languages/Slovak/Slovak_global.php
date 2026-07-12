<?php
/*
 * e107 website system
 *
 * racetiming plugin - Slovak global language file.
 *
 * Array-style LAN file loaded via e107::lan('racetiming', 'global', true).
 * Overrides the English_global.php base per key; missing terms fall back to
 * English.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	'LAN_GLOBAL_RACETIMING_001' => 'Meranie časov',
	'LAN_GLOBAL_RACETIMING_002' => 'Merací engine pre kontrolné body',
	'LAN_GLOBAL_RACETIMING_003' => 'Merací engine sady na časomieru. Zaznamenáva prejazdy na kontrolných bodoch - ručne cez mobilnú klávesnicu, hromadne pri spoločnom štarte alebo importom z RFID - a počas pretekov počíta dosiahnuté časy a medzičasy. Vlastní tabuľku race_time; ukladá len surové prejazdy, všetko ostatné sa dopočítava pri čítaní.',
);
