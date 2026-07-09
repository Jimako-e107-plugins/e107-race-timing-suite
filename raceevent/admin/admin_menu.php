<?php
/*
 * e107 website system
 *
 * raceevent base plugin - shared admin dispatcher / menu.
 *
 * This holds the SHARED admin menu for the plugin. Each admin area is its own
 * mode pointing at its own controller/UI; adding a feature = adding a new file
 * plus a new entry in $modes and $adminMenu here, with no change to existing
 * modes. Structure mirrors timetracker/admin/admin_menu.php.
 *
 * Single-event install: there is exactly ONE event, so its data is plugin
 * configuration (prefs), not table rows. The default mode is the native
 * e_admin_ui $prefs configuration page; the dispatcher is kept so future modes
 * can still be bolted on.
 */

if (!defined('e107_INIT')) { exit; }

require_once("../../../class2.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// Admin LAN, flat-language layout: languages/<lang>/<lang>_admin.php.
e107::lan('raceevent', true, true);


class raceevent_adminArea extends e_admin_dispatcher
{
	protected $defaultMode   = 'main';
	protected $defaultAction = 'prefs';

	protected $modes = array(
		// Event configuration page. The single event's fields are stored as
		// plugin prefs (no DB table); served by a native e_admin_ui $prefs form.
		// Controller/UI classes live in admin/admin_config.php.
		'main' => array(
			'controller' => 'raceevent_config_ui',
			'path'       => null,
			'ui'         => 'raceevent_config_form_ui',
			'uipath'     => null,
		),
		// Season maintenance page (udrzba): cleaning the previous season's data
		// when a new event/season is set up. Cross-plugin, season-lifecycle
		// operation - belongs in the base plugin. Served by its own controller/UI
		// in admin/admin_maintenance.php (custom 'udrzba' action).
		'maintenance' => array(
			'controller' => 'raceevent_maintenance_ui',
			'path'       => null,
			'ui'         => 'raceevent_maintenance_form_ui',
			'uipath'     => null,
		),
		// Navigation (sitelink) check page: detects function-driven sitelinks
		// (link_function = plugin::method) whose plugin or method no longer
		// exists and lets the organizer hide the broken ones. Self-contained,
		// served by its own controller/UI in admin/admin_checklinks.php (custom
		// 'check' action). Independent of the maintenance (udrzba) code.
		'checklinks' => array(
			'controller' => 'raceevent_checklinks_ui',
			'path'       => null,
			'ui'         => 'raceevent_checklinks_form_ui',
			'uipath'     => null,
		),
		// Event overview ("Prehľad preteku"): the cross-suite link directory with
		// the per-link alive-check. Renders the SAME shared include as the public
		// front page (raceevent/page_overview.php). Served by its own controller
		// /UI in admin/admin_overview.php (custom 'view' action).
		'overview' => array(
			'controller' => 'raceevent_overview_ui',
			'path'       => null,
			'ui'         => 'raceevent_overview_form_ui',
			'uipath'     => null,
		),
	);

	// Every item carries an explicit 'url' so cross-mode navigation works no
	// matter which entry script is currently loaded - each mode is served by
	// its own file (mode-per-file), as in timetracker/githubSync.
	protected $adminMenu = array(
		'main/prefs' => array('caption' => LAN_RACEEVENT_CONFIG, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_config.php'),
		'divider1'          => array('divider' => true),
		'maintenance/udrzba' => array('caption' => LAN_MAINTENANCE, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_maintenance.php'),
		'checklinks/check' => array('caption' => LAN_RACEEVENT_CHECKLINKS, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_checklinks.php'),
		'overview/view' => array('caption' => LAN_RACEEVENT_OV_MENU, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_overview.php'),
	);

	protected $adminMenuAliases = array();

	protected $menuTitle = LAN_RACEEVENT_PLUGIN;

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so raceevent's own entry is excluded from the shortcut list.
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('raceevent'))
			);
		}
	}
}
