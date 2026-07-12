<?php
/*
 * e107 website system
 *
 * race plugin - English global language file (issue #37).
 *
 * Array-style LAN file loaded via e107::lan('racetrack', 'global', true). Canonical,
 * complete set; the Slovak file (languages/Slovak/Slovak_global.php) overrides
 * per key and falls back here for any missing term.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_GLOBAL_RACE_001' => 'Tracks',
    'LAN_GLOBAL_RACE_002' => 'Overview and track settings',
    'LAN_GLOBAL_RACE_003' => 'Tracks, checkpoints and entry prices for the race-timing suite. Defines the courses of the event, the checkpoints along each course (start, intermediate points, finish), date-tiered entry prices, and the archive of finished editions. Requires raceevent.',
);
