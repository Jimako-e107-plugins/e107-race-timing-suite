<?php
/*
 * e107 website system
 *
 * racetiming plugin - English global language file.
 *
 * Array-style LAN file loaded via e107::lan('racetiming', 'global', true).
 * Canonical, complete set; the Slovak file (languages/Slovak/Slovak_global.php)
 * overrides per key and falls back here for any missing term. These globals back
 * the plugin.xml lan="" attributes (manifest name/summary) and the admin
 * dispatcher title.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	// Plugin display name (plugin.xml <e107Plugin lan="...">).
	'LAN_GLOBAL_RACETIMING_001' => 'Race timing',
	// Summary / dispatcher title (plugin.xml <summary>/<description> lan="...").
	'LAN_GLOBAL_RACETIMING_002' => 'Checkpoint timing engine',
);
