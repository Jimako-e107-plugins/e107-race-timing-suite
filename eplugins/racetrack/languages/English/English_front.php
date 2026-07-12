<?php
/*
 * e107 website system
 *
 * race plugin - English front language file (issue #37).
 *
 * Array-style LAN file loaded via e107::lan('racetrack', '', true) by
 * race_points_menu.php. LAN_TC_ALL was used only by the deleted page_start.php
 * (start-list copy) and was removed when that page moved to the canonical
 * timetracker start list; the file is kept (still loaded) for future front terms.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    // pretek.php (race landing) - heading above the track's category list.
    'LAN_RT_PRETEK_CATEGORIES' => 'List of categories',

    // page_archive.php (frozen archive front view).
    'LAN_RT_ARCHIVE_TITLE'     => 'Archive',
    'LAN_RT_ARCHIVE_NOT_FOUND' => 'Archive not found.',
);
