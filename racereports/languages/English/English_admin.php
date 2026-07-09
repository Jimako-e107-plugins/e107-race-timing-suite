<?php
/*
 * e107 website system
 *
 * racereports plugin - English admin language file.
 *
 * Array-style LAN file loaded via e107::lan('racereports', true, true).
 * Array-style is used on purpose: e107's includeLan() only applies the per-key
 * English fallback for missing translations when the language file RETURNS an
 * array, so a partial Slovak file (languages/Slovak/Slovak_admin.php) degrades
 * cleanly to these English strings instead of leaving constants undefined. This
 * file is the canonical, complete set; the Slovak file overrides per key.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	// Admin landing screen (admin/admin_config.php).
	'LAN_ADMIN_RACEREPORTS_001' => 'Race reports',
	'LAN_ADMIN_RACEREPORTS_002' => 'Race reports skeleton - this plugin owns the race_result and race_archive tables. The result-report, ranking and archive snapshot/freeze logic is not implemented yet.',
	// Admin permission label.
	'LAN_ADMIN_RACEREPORTS_003' => 'Race reports administration',

	// Left admin-nav menu items + page captions.
	'LAN_ADMIN_RACEREPORTS_004' => 'Race overview',
	'LAN_ADMIN_RACEREPORTS_005' => 'Online results',
	'LAN_ADMIN_RACEREPORTS_006' => 'Checkpoint times',
	'LAN_ADMIN_RACEREPORTS_007' => 'Results (SUT)',
	'LAN_ADMIN_RACEREPORTS_008' => 'Results (finish)',
	'LAN_ADMIN_RACEREPORTS_009' => 'Start list',
	'LAN_ADMIN_RACEREPORTS_105' => 'Segment (Od-Do)',
	'LAN_ADMIN_RACEREPORTS_107' => 'Arrivals (dobeh)',
	'LAN_ADMIN_RACEREPORTS_116' => 'Racer progression',

	// Landing-page report links.
	'LAN_ADMIN_RACEREPORTS_010' => 'No races yet.',
	'LAN_ADMIN_RACEREPORTS_011' => 'No categories yet.',
	'LAN_ADMIN_RACEREPORTS_012' => 'No checkpoints yet.',
	'LAN_ADMIN_RACEREPORTS_020' => 'Online standings',
	'LAN_ADMIN_RACEREPORTS_021' => 'online - all categories',
	'LAN_ADMIN_RACEREPORTS_030' => 'Times after checkpoints',
	'LAN_ADMIN_RACEREPORTS_031' => 'online - all checkpoints',
	'LAN_ADMIN_RACEREPORTS_040' => 'Parity test',
	'LAN_ADMIN_RACEREPORTS_041' => 'Parity check (clean engine vs legacy comparator)',
	'LAN_ADMIN_RACEREPORTS_042' => 'The parity comparator stays admin-gated. The companion engine self-test (parity/engine_selftest.php) is CLI-only and is not linked into the web.',

	// Overview screen: info-only list of the supported report types.
	'LAN_ADMIN_RACEREPORTS_050' => 'Supported result types',
	'LAN_ADMIN_RACEREPORTS_051' => 'Online',
	'LAN_ADMIN_RACEREPORTS_052' => 'Checkpoint times',

	// SUT screen: per-track finishers-only results.
	'LAN_ADMIN_RACEREPORTS_060' => 'Per-track results (finishers)',

	// NUMBER screen: racer progression - every bib -> single-racer whole-course report.
	'LAN_ADMIN_RACEREPORTS_117' => 'Racers (by bib number)',

	// FINISH screen: post-race results list (finishers + DNF/DSQ/DNS).
	'LAN_ADMIN_RACEREPORTS_080' => 'Finish results',
	'LAN_ADMIN_RACEREPORTS_081' => 'finish - all categories',
	'LAN_ADMIN_RACEREPORTS_082' => 'finish - all tracks on one page',

	// START screen: start-point standings list (starters + not-started group).
	'LAN_ADMIN_RACEREPORTS_090' => 'Start list',
	'LAN_ADMIN_RACEREPORTS_091' => 'start - all categories',
	'LAN_ADMIN_RACEREPORTS_092' => 'start - all tracks on one page',

	// DOBEH screen: per-checkpoint arrivals board links.
	'LAN_ADMIN_RACEREPORTS_108' => 'Arrivals board',
	'LAN_ADMIN_RACEREPORTS_109' => 'arrivals - all checkpoints',

	// SETTINGS page (admin/admin_config.php) - plugin preferences.
	'LAN_ADMIN_RACEREPORTS_070' => 'SUT time - decimal places',
	'LAN_ADMIN_RACEREPORTS_071' => 'Decimal places for finish times on the SUT (finishers) report. 0 = whole seconds (HH:MM:SS), as before.',
	'LAN_ADMIN_RACEREPORTS_072' => 'SUT - colour categories',
	'LAN_ADMIN_RACEREPORTS_073' => 'Off = clean results list with no category background colours. On = per-category row background on the SUT (finishers) report.',
	'LAN_ADMIN_RACEREPORTS_074' => 'Online - auto-refresh interval (s)',
	'LAN_ADMIN_RACEREPORTS_075' => '0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence.',
	'LAN_ADMIN_RACEREPORTS_076' => 'Finish - colour categories',
	'LAN_ADMIN_RACEREPORTS_077' => 'On (default) = per-category row background on finisher rows of the finish (results) report. Off = clean results list with no category colours. Ended rows (DNF/DSQ/DNS) are never coloured.',
	'LAN_ADMIN_RACEREPORTS_078' => 'Start - colour categories',
	'LAN_ADMIN_RACEREPORTS_079' => 'On (default) = per-category row background on starter rows of the start (start list) report. Off = clean list with no category colours. Not-started rows are never coloured.',

	// AKTUALNE (full per-race results matrix) report area (admin/admin_aktualne.php).
	'LAN_ADMIN_RACEREPORTS_100' => 'Full results',
	'LAN_ADMIN_RACEREPORTS_101' => 'Full per-race results (all checkpoints)',
	// CUSTOM (segment) screen: per-race two-dropdown Od/Do picker (admin/admin_custom.php).
	'LAN_ADMIN_RACEREPORTS_106' => 'Segment report (between two points)',
	'LAN_ADMIN_RACEREPORTS_102' => 'From (Od)',
	'LAN_ADMIN_RACEREPORTS_103' => 'To (Do)',
	'LAN_ADMIN_RACEREPORTS_104' => 'Open segment report',
	'LAN_ADMIN_RACEREPORTS_093' => 'Arrivals - auto-refresh interval (s)',
	'LAN_ADMIN_RACEREPORTS_094' => '0 = no automatic refresh. Value in seconds. The ?refresh parameter in the address takes precedence. Separate from the online interval - the arrivals board is paced on its own.',
	'LAN_ADMIN_RACEREPORTS_095' => 'Finish - category column',
	'LAN_ADMIN_RACEREPORTS_096' => 'Off (default) = no category column. On = show a column with the category name in the finish (results) list and its CSV/XLS export.',
	'LAN_ADMIN_RACEREPORTS_110' => 'Results - sub-second decimal places',
	'LAN_ADMIN_RACEREPORTS_111' => '0-3. Applies to the result reports (online, checkpoints, finish, start, segment, arrivals). The data is always stored at 3 decimals (ms); this is display only (truncated, not rounded). SUT has its own setting; the Aktuálne (overview) report is not governed by this.',
	// Settings-screen prefs-tab captions (e_admin_ui $preftabs). String tab keys
	// (dec/colors/refresh/custom) bind each pref to a tab identity; reordering the
	// tab list never touches the prefs.
	'LAN_ADMIN_RACEREPORTS_112' => 'Decimals',
	'LAN_ADMIN_RACEREPORTS_113' => 'Coloring',
	'LAN_ADMIN_RACEREPORTS_114' => 'Refresh',
	'LAN_ADMIN_RACEREPORTS_115' => 'Other',
);
