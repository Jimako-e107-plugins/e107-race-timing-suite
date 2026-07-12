<?php
/*
 * e107 website system
 *
 * racereports plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('racereports', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary) and the admin
 * dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Plugin display name (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACEREPORTS_001' => 'Race reports',
    // Summary / dispatcher title (plugin.xml <summary>/<description> lan="...").
    'LAN_GLOBAL_RACEREPORTS_002' => 'Result reports and archive',
);
