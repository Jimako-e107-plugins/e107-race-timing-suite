<?php
/*
 * e107 website system
 *
 * racereports plugin - shared admin dispatcher / menu.
 *
 * This holds the plugin's admin dispatcher, modelled on
 * raceevent/admin/admin_menu.php: a class extending e_admin_dispatcher with
 * $modes (mode => controller/ui) and $adminMenu (mode/action => caption/perm/
 * url). Each admin area is its own mode served by its OWN entry file; adding an
 * area = adding a $modes + $adminMenu entry + a file, no change to existing ones.
 *
 * ONE admin file per area (mode-per-file):
 *   - main       (prefs)    -> admin/admin_config.php   (the SETTINGS page)
 *   - overview   (overview) -> admin/admin_overview.php
 *   - online     (online)   -> admin/admin_online.php
 *   - point      (point)    -> admin/admin_point.php
 *   - stu        (stu)      -> admin/admin_stu.php
 *   - finish     (finish)   -> admin/admin_finish.php
 *   - start      (start)    -> admin/admin_start.php
 *   - dobeh      (dobeh)    -> admin/admin_dobeh.php
 * Every $adminMenu entry carries an explicit 'url' to its area's own file;
 * renderMenu() appends ?mode=..&action=.. so cross-mode navigation routes to the
 * right file no matter which entry script is currently loaded.
 *
 * Default mode/action is main/prefs (the SETTINGS page) - whose controller is
 * defined in admin_config.php, the primary admin link. A bare admin_config.php
 * load (no ?mode=) therefore lands on a controller that file actually defines,
 * avoiding the "class not found" fatal a report default would cause.
 *
 * SIDE-EFFECT FREE: this file ONLY defines the dispatcher class (guarded by
 * e107_INIT). It performs NO class2 bootstrap, NO LAN load and NO redirect at
 * include time. The core renders the menu via getAdminUI()->renderMenu() and
 * never auto-includes this file, so it is only ever pulled in by an area entry
 * file, which loads the LAN (the captions below resolve to constants) before
 * requiring it.
 */

if (!defined('e107_INIT')) { exit; }


class racereports_adminArea extends e_admin_dispatcher
{
	// Land on the SETTINGS page by default: its controller (racereports_main_ui)
	// lives in admin_config.php, the primary admin link, so a bare load resolves.
	protected $defaultMode   = 'main';
	protected $defaultAction = 'prefs';

	protected $modes = array(
		// SETTINGS page: a native e_admin_ui $prefs form (no DB table). Controller/
		// UI defined in admin/admin_config.php.
		'main' => array(
			'controller' => 'racereports_main_ui',
			'path'       => null,
			'ui'         => 'racereports_main_form_ui',
			'uipath'     => null,
		),
		// Race overview: an info-only list of the supported report types (no live
		// per-race/category/checkpoint links — those live on the online/point
		// screens). Controller/UI defined in admin/admin_overview.php.
		'overview' => array(
			'controller' => 'racereports_overview_ui',
			'path'       => null,
			'ui'         => 'racereports_overview_form_ui',
			'uipath'     => null,
		),
		// Online results: per race, the Online report links only. Controller/UI
		// defined in admin/admin_online.php.
		'online' => array(
			'controller' => 'racereports_online_ui',
			'path'       => null,
			'ui'         => 'racereports_online_form_ui',
			'uipath'     => null,
		),
		// Checkpoint times: per race, the per-checkpoint report links only —
		// exposed natively the SAME way as the online screen. Controller/UI defined
		// in admin/admin_point.php.
		'point' => array(
			'controller' => 'racereports_point_ui',
			'path'       => null,
			'ui'         => 'racereports_point_form_ui',
			'uipath'     => null,
		),
		// SUT results: per-track finishers-only results list. ONE screen listing one
		// link per race(track) to report_stu.php, exposed natively the SAME way as
		// the online/point screens. Controller/UI defined in admin/admin_stu.php.
		'stu' => array(
			'controller' => 'racereports_stu_ui',
			'path'       => null,
			'ui'         => 'racereports_stu_form_ui',
			'uipath'     => null,
		),
		// Finish results: per race, the finish report links (komplet + all-categories
		// + one per category). ONE screen exposed natively the SAME way as the
		// online/point/stu screens. Controller/UI defined in admin/admin_finish.php.
		'finish' => array(
			'controller' => 'racereports_finish_ui',
			'path'       => null,
			'ui'         => 'racereports_finish_form_ui',
			'uipath'     => null,
		),
		// Start results: per race, the start report links (komplet + all-categories
		// + one per category). ONE screen exposed natively the SAME way as the
		// online/point/stu/finish screens. Controller/UI defined in admin/admin_start.php.
		'start' => array(
			'controller' => 'racereports_start_ui',
			'path'       => null,
			'ui'         => 'racereports_start_form_ui',
			'uipath'     => null,
		),
		// Full results matrix (aktualne): per race, ONE link to the FULL per-race
		// results matrix (report_aktualne.php?p=race_id). ONE screen exposed natively
		// the SAME way as the stu screen. Controller/UI defined in admin/admin_aktualne.php.
		'aktualne' => array(
			'controller' => 'racereports_aktualne_ui',
			'path'       => null,
			'ui'         => 'racereports_aktualne_form_ui',
		),
		// Custom (segment) report: per race, a two-dropdown Od/Do picker that opens
		// the between-two-points split report. Arbitrary point pairs make a fixed link
		// list impractical, so this screen uses a picker instead. Controller/UI defined
		// in admin/admin_custom.php.
		'custom' => array(
			'controller' => 'racereports_custom_ui',
			'path'       => null,
			'ui'         => 'racereports_custom_form_ui',

		),
		// Dobeh (checkpoint arrivals board): per race, the per-checkpoint arrivals
		// board links. ONE screen exposed natively the SAME way as the
		// online/point/stu/finish/start screens. Controller/UI defined in
		// admin/admin_dobeh.php.
		'dobeh' => array(
			'controller' => 'racereports_dobeh_ui',
			'path'       => null,
			'ui'         => 'racereports_dobeh_form_ui',
			'uipath'     => null,
		),
		// Racer progression (number): ONE screen listing every bib -> a link to the
		// single-racer whole-course report (report_number.php?n=<bib>), opened in a
		// new tab. Exposed natively the SAME way as the stu screen. Controller/UI
		// defined in admin/admin_number.php.
		'number' => array(
			'controller' => 'racereports_number_ui',
			'path'       => null,
			'ui'         => 'racereports_number_form_ui',
			'uipath'     => null,
		),
	);

	// Every item carries an explicit 'url' to its area's own entry file (mode-per-
	// file); renderMenu() appends ?mode=..&action=.. so cross-mode navigation
	// routes correctly. main/prefs is FIRST (the SETTINGS page). perm 'P' = the
	// plugin's own admin permission (registered by the adminLinks block in
	// plugin.xml).
	protected $adminMenu = array(
		'main/prefs'        => array('caption' => LAN_ADMIN_RACEREPORTS_003, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_config.php'),
		'online/online'     => array('caption' => LAN_ADMIN_RACEREPORTS_005, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_online.php'),
		'point/point'       => array('caption' => LAN_ADMIN_RACEREPORTS_006, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_point.php'),
		'stu/stu'           => array('caption' => LAN_ADMIN_RACEREPORTS_007, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_stu.php'),
		'finish/finish'     => array('caption' => LAN_ADMIN_RACEREPORTS_008, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_finish.php'),
		'start/start'       => array('caption' => LAN_ADMIN_RACEREPORTS_009, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_start.php'),
		'aktualne/aktualne' => array('caption' => LAN_ADMIN_RACEREPORTS_100, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_aktualne.php'),
		'custom/custom'     => array('caption' => LAN_ADMIN_RACEREPORTS_105, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_custom.php'),
		'dobeh/dobeh'       => array('caption' => LAN_ADMIN_RACEREPORTS_107, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_dobeh.php'),
		'number/number'     => array('caption' => LAN_ADMIN_RACEREPORTS_116, 'perm' => 'P', 'url' => '{e_PLUGIN}racereports/admin/admin_number.php'),
	);

	protected $adminMenuAliases = array();

	protected $menuTitle = LAN_GLOBAL_RACEREPORTS_001;

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racereports' own entry is excluded from the shortcut list.
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racereports'))
			);
		}
	}
}
