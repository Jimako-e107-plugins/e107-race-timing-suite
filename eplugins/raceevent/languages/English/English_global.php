<?php
/*
 * e107 website system
 *
 * raceevent plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('raceevent', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary/description) and the
 * admin dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Plugin display name (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACEEVENT_001' => "Race event",
    // Summary (plugin.xml <summary lan="...">).
    'LAN_GLOBAL_RACEEVENT_002' => "Event configuration",
    // Description (plugin.xml <description lan="...">).
    'LAN_GLOBAL_RACEEVENT_003' => "Base plugin of the race-timing suite. Holds the event configuration (single-event model: one website = one event) as plugin preferences, plus season maintenance and cross-suite diagnostics. Every other plugin depends on it; declares no tables of its own.",
);
