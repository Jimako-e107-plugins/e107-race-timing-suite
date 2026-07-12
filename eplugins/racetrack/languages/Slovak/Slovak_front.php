<?php
/*
 * e107 website system
 *
 * race plugin - Slovak front language file (issue #37).
 *
 * Array-style LAN file loaded via e107::lan('racetrack', '', true). Overrides the
 * English_front.php base per key; missing terms fall back to English.
 */

if (!defined('e107_INIT')) { exit; }

// LAN_TC_ALL was used only by the deleted page_start.php (start-list copy);
// removed when that page moved to the canonical timetracker start list.
return array(
	// pretek.php (race landing) - heading above the track's category list.
	'LAN_RT_PRETEK_CATEGORIES' => 'Zoznam kategórií',

	// page_archive.php (frozen archive front view).
	'LAN_RT_ARCHIVE_TITLE'     => 'Archív',
	'LAN_RT_ARCHIVE_NOT_FOUND' => 'Archív sa nenašiel.',
);
