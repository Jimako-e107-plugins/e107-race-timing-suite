<?php
/*
 * e107 website system
 *
 * racereports plugin - SETTINGS page (mode 'main', action 'prefs').
 *
 * This is the plugin's PREFERENCES screen and nothing else. It is the primary
 * admin link (plugin.xml adminLinks) and the dispatcher's default landing mode,
 * so a bare admin_config.php load (no ?mode=) routes to main/prefs - whose
 * controller IS defined here - and never to a report mode whose controller lives
 * in another file.
 *
 * The report areas do NOT live here. Each one is its own admin entry file
 * (one file per report area), sharing the dispatcher (admin_menu.php) and the
 * report-link base (admin_report_ui.php):
 *   - admin_overview.php (mode overview): info-only list of supported report types
 *   - admin_online.php   (mode online):   per race, the Online report links
 *   - admin_point.php    (mode point):    per race, the checkpoint-time links
 *   - admin_stu.php      (mode stu):      per-track finishers-only results links
 *
 * Native e107: the settings are a single e_admin_ui $prefs form (no DB table,
 * $table=''). The core renders the form, handles Save (PrefsSaveTrigger), the
 * e_token (CSRF) and storage into the plugin config; values are read back
 * everywhere via e107::getPlugConfig('racereports').
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle / field-title constants resolve at class-definition time), require
 * the shared dispatcher, then gate the whole screen on the plugin's OWN admin
 * permission with getperms('P').
 */

require_once("../../../class2.php");

// LAN first: admin_menu.php's $adminMenu/$menuTitle and the controller's
// $pluginTitle / field-title constants reference these at class-definition time.
e107::lan('racereports', true, true);

require_once("admin_menu.php"); // shared dispatcher / menu (pure class def)

if (!getperms('P'))
{
	exit;
}


/**
 * Settings area (mode 'main'): a native e_admin_ui $prefs form. No DB table
 * ($table=''); the core stores the posted values into the plugin config and
 * reads them back via e107::getPlugConfig('racereports').
 */
class racereports_main_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_ADMIN_RACEREPORTS_001;
	protected $pluginName  = 'racereports';

	// Prefs-only screen - no DB table.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'prefs';

	// Settings-screen prefs tabs (native e_admin_ui $preftabs - flat key => caption,
	// consumed via getPrefTabs() in the prefs form). The KEYS are STRING IDENTITIES
	// (dec/colors/refresh/custom), not numeric indexes: each pref binds to a tab by
	// its key ('tab' => '<key>' below), so the ORDER of the tabs is decided purely by
	// the order of entries HERE and reordering them never touches a single pref. This
	// is a pure visual grouping - no logic change. The tab order shown to the user is:
	// Desatiny | Podfarbenie | Obnovenie | Ostatné.
	protected $preftabs = array(
		'dec'     => LAN_ADMIN_RACEREPORTS_112, // Desatiny / Decimals
		'colors'  => LAN_ADMIN_RACEREPORTS_113, // Podfarbenie / Coloring
		'refresh' => LAN_ADMIN_RACEREPORTS_114, // Obnovenie / Refresh
		'custom'  => LAN_ADMIN_RACEREPORTS_115, // Ostatné / Other
	);

	// Plugin preferences. The core reads current values from
	// e107::getPlugConfig('racereports') and writes the posted values back on
	// submit; each value is sanitised through the 'data' type (int) before storage.
	protected $prefs = array(
		// CENTRAL result-time precision - displayed sub-second decimal places for the
		// RESULT reports (online, checkpoints/point, finish, start, segment/custom,
		// arrivals/dobeh). The underlying DATA stays at 3 decimals (ms); this pref is
		// DISPLAY-ONLY and TRUNCATES (never rounds), so a smaller value just cuts digits
		// off the rendered string - it never changes a sort key (DataTables data-order
		// uses the full-precision raw seconds). DEFAULT 2 is enforced at the consumer via
		// get('result_decimals', 2). SUT keeps its OWN stu_decimals (independent); the
		// Aktuálne overview is NOT a result list and is deliberately left out. min/max
		// clamp the count to the 0..3 the data carries.
		'result_decimals' => array(
			'tab'        => 'dec',
			'title'      => LAN_ADMIN_RACEREPORTS_110,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_111,
			'writeParms' => array('min' => 0, 'max' => 3),
		),
		// SUT (finishers) report - number of decimal places for the finish time.
		// 0 = whole seconds (HH:MM:SS), the legacy/default render. min=0 stops a
		// negative count reaching race_format::formatElapsed().
		'stu_decimals' => array(
			'tab'        => 'dec',
			'title'      => LAN_ADMIN_RACEREPORTS_070,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_071,
			'writeParms' => array('min' => 0),
		),
		// SUT (finishers) report - per-category row coloring toggle. 0/1.
		// OFF (the default, enforced at the consumer via get('stu_colors', 0))
		// renders a clean table with no category backgrounds; ON restores the
		// per-category background via the shared race_report_color() path.
		'stu_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_072,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_073,
		),
		// ONLINE report - default auto-refresh interval (seconds). 0 = no
		// auto-refresh (the default, enforced at the consumer via
		// get('online_refresh_interval', 0)), so an unset pref reproduces today's
		// no-refresh behaviour exactly. The ?refresh URL param always wins over this
		// pref (see report_online.php). min=0 stops a negative second count.
		'online_refresh_interval' => array(
			'tab'        => 'refresh',
			'title'      => LAN_ADMIN_RACEREPORTS_074,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_075,
			'writeParms' => array('min' => 0),
		),
 
		// FINISH (results) report - per-category row coloring toggle. 0/1.
		// DEFAULT 1 (enforced at the consumer via get('finish_colors', 1)): finish is
		// COLOURED by default - unlike stu, which defaults OFF. ON colours FINISHER
		// rows only via the shared race_report_color() path; ENDED rows (DNF/DSQ/DNS)
		// are never coloured. OFF renders a clean table with no category backgrounds.
		'finish_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_076,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_077,
		),
		// FINISH (results) report - category-name column toggle. 0/1. DEFAULT 0
		// (enforced at the consumer via get('finish_categ', 0)): OFF renders the
		// finish list exactly as today (no category column). ON adds ONE column with
		// the racer's category NAME (race_category.race_category_name) to BOTH the
		// on-screen results table AND the CSV/XLS export, built from the same cells.
		// This closes the only gap between the finish export and the legacy
		// timetracker_export (see NOTES.md), so that export can be decommissioned.
		'finish_categ' => array(
			'tab'   => 'custom',
			'title' => LAN_ADMIN_RACEREPORTS_095,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_096,
		),
		// START report - per-category row coloring toggle. 0/1. DEFAULT 1 (enforced
		// at the consumer via get('start_colors', 1)): start is COLOURED by default -
		// its OWN per-report pref, same as finish_colors. ON colours STARTER rows only
		// via the shared race_report_color() path; the not-started rows are never
		// coloured. OFF renders a clean table with no category backgrounds.
		'start_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_078,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_079,
		),
		// DOBEH (checkpoint arrivals board) report - default auto-refresh interval
		// (seconds). 0 = no auto-refresh (the default, enforced at the consumer via
		// get('dobeh_refresh_interval', 0)), so an unset pref reproduces a no-refresh
		// board exactly. The ?refresh URL param always wins over this pref (see
		// report_dobeh.php). This is the dobeh board's OWN refresh pref, separate from
		// online_refresh_interval (the two live boards are paced independently).
		// min=0 stops a negative second count.
		'dobeh_refresh_interval' => array(
			'tab'        => 'refresh',
			'title'      => LAN_ADMIN_RACEREPORTS_093,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_094,
			'writeParms' => array('min' => 0),
		),
	);
}


class racereports_main_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
