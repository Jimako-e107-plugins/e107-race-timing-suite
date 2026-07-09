<?php
/*
 * e107 website system
 *
 * racetiming plugin - Slovak front language file.
 *
 * Array-style LAN file loaded on the front-end via e107::lan('racetiming', '',
 * true). Overrides the English front strings per key; any key missing here falls
 * back to languages/English/English_front.php.
 *
 * NOTE: per the faithful 1:1 relocation, the keypad glyphs, header caption and
 * operator notices in vstup.php are intentionally LEFT HARDCODED (Slovak) and
 * are NOT wired to these keys (see NOTES.md). The values below mirror the
 * current hardcoded Slovak wording.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	'LAN_FRONT_RACETIMING_ALREADY_RECORDED' => 'Pretekár č. %1$s je už na %2$s zapísaný',
	'LAN_FRONT_RACETIMING_ALREADY_ENDED'    => 'Pretekár %1$s už predčasne skončil',
	'LAN_FRONT_RACETIMING_CAPTION_ORDER'    => 'poradie',
	'LAN_FRONT_RACETIMING_CAPTION_PREV'     => 'predošlé číslo',
);
