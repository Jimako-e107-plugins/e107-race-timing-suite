<?php
/*
 * e107 website system
 *
 * racetiming plugin - English front language file.
 *
 * racetiming's FIRST front LAN, added with the manual passing-entry "keypad app"
 * (vstup.php) + {TIMETRACKER_VSTUP} shortcode + `kontrola` route relocated here
 * from timetracker.
 *
 * Array-style LAN file loaded on the front-end via e107::lan('racetiming', '',
 * true). Canonical, complete set; the Slovak file
 * (languages/Slovak/Slovak_front.php) overrides per key and falls back here for
 * any missing term. LAN key pattern: LAN_FRONT_RACETIMING_<NAME>.
 *
 * NOTE: per the faithful 1:1 relocation, the keypad glyphs, header caption and
 * operator notices in vstup.php are intentionally LEFT HARDCODED (Slovak) and
 * are NOT wired to these keys (see NOTES.md). These strings are the canonical
 * EN/SK set for racetiming's first front route, ready for a later i18n pass.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Operator notices (manual passing entry).
    'LAN_FRONT_RACETIMING_ALREADY_RECORDED' => 'Racer no. %1$s is already recorded at %2$s',
    'LAN_FRONT_RACETIMING_ALREADY_ENDED'    => 'Racer %1$s has already ended early',
    // Header caption.
    'LAN_FRONT_RACETIMING_CAPTION_ORDER'    => 'order',
    'LAN_FRONT_RACETIMING_CAPTION_PREV'     => 'previous number',
);
