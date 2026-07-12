<?php
/*
 * e107 website system
 *
 * racereports plugin - Italian global language file.
 *
 * Array-style LAN file loaded via e107::lan('racereports', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary) and the admin
 * dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // Nome visualizzato del plugin (plugin.xml <e107Plugin lan="...">).
    'LAN_GLOBAL_RACEREPORTS_001' => 'Resoconti di gara',
    // Titolo del sommario / dispatcher (plugin.xml <summary>/<description> lan="...").
    'LAN_GLOBAL_RACEREPORTS_002' => 'Resoconti dei risultati e archivio',
);
