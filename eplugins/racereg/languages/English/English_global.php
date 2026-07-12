<?php
/*
 * e107 website system
 *
 * racereg plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('racereg', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary/description) and the
 * admin dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Plugin display name (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACEREG_001' => "Race registration",
    // Summary (plugin.xml <summary lan="...">).
    'LAN_GLOBAL_RACEREG_002' => "Online and on-site registration",
    // Description (plugin.xml <description lan="...">).
    'LAN_GLOBAL_RACEREG_003' => "Registration for the race-timing suite: online sign-up through the event website, track and category selection, and PAY by square QR payment. WORK IN PROGRESS - notifications and e-mail sending are not implemented; published as a reference implementation, not for production use.",
);
