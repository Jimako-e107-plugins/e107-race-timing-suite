<?php
/*
 * e107 website system
 *
 * racetiming plugin - Italian front language file.
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
    // Notifiche operatore (inserimento manuale passaggi).
    'LAN_FRONT_RACETIMING_ALREADY_RECORDED' => 'Il corridore n. %1$s è già stato registrato a %2$s',
    'LAN_FRONT_RACETIMING_ALREADY_ENDED'    => 'Il corridore %1$s ha già terminato anticipatamente',
    // Didascalie intestazione.
    'LAN_FRONT_RACETIMING_CAPTION_ORDER'    => 'ordine',
    'LAN_FRONT_RACETIMING_CAPTION_PREV'     => 'numero precedente',
);
