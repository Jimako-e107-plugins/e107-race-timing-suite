<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_menu.php
 *
*/

/*
 * Shared admin dispatcher / menu. Each admin screen is its own mode served by its
 * own entry file; a new screen = a new file plus an entry in $modes and $adminMenu.
 * Pure class definition (no bootstrap, LAN load or redirect at include time): the
 * entry files load the LAN before requiring it, because the captions are constants.
 */

if (!defined('e107_INIT')) { exit; }


class racereports_adminArea extends e_admin_dispatcher
{
	// Default = Settings: its controller lives in admin_config.php (the primary
	// admin link), so a bare admin_config.php load resolves.
	protected $defaultMode   = 'main';
	protected $defaultAction = 'prefs';

	protected $modes = array(
		// Settings - admin/admin_config.php.
		'main' => array(
			'controller' => 'racereports_main_ui',
			'path'       => null,
			'ui'         => 'racereports_main_form_ui',
			'uipath'     => null,
		),
		// Info-only list of report types - served only by the old
		// admin/admin_reports.php; not in $adminMenu.
		'overview' => array(
			'controller' => 'racereports_overview_ui',
			'path'       => null,
			'ui'         => 'racereports_overview_form_ui',
			'uipath'     => null,
		),
		// Online results - admin/admin_online.php.
		'online' => array(
			'controller' => 'racereports_online_ui',
			'path'       => null,
			'ui'         => 'racereports_online_form_ui',
			'uipath'     => null,
		),
		// Checkpoint times - admin/admin_point.php.
		'point' => array(
			'controller' => 'racereports_point_ui',
			'path'       => null,
			'ui'         => 'racereports_point_form_ui',
			'uipath'     => null,
		),
		// Results (SUT) - admin/admin_stu.php.
		'stu' => array(
			'controller' => 'racereports_stu_ui',
			'path'       => null,
			'ui'         => 'racereports_stu_form_ui',
			'uipath'     => null,
		),
		// Results (finish) - admin/admin_finish.php.
		'finish' => array(
			'controller' => 'racereports_finish_ui',
			'path'       => null,
			'ui'         => 'racereports_finish_form_ui',
			'uipath'     => null,
		),
		// Start list - admin/admin_start.php.
		'start' => array(
			'controller' => 'racereports_start_ui',
			'path'       => null,
			'ui'         => 'racereports_start_form_ui',
			'uipath'     => null,
		),
		// Full results - admin/admin_aktualne.php.
		'aktualne' => array(
			'controller' => 'racereports_aktualne_ui',
			'path'       => null,
			'ui'         => 'racereports_aktualne_form_ui',
		),
		// Segment (Od-Do) picker - admin/admin_custom.php.
		'custom' => array(
			'controller' => 'racereports_custom_ui',
			'path'       => null,
			'ui'         => 'racereports_custom_form_ui',

		),
		// Arrivals board - admin/admin_dobeh.php.
		'dobeh' => array(
			'controller' => 'racereports_dobeh_ui',
			'path'       => null,
			'ui'         => 'racereports_dobeh_form_ui',
			'uipath'     => null,
		),
		// Racer progression - admin/admin_number.php.
		'number' => array(
			'controller' => 'racereports_number_ui',
			'path'       => null,
			'ui'         => 'racereports_number_form_ui',
			'uipath'     => null,
		),
	);

	// Every item carries an explicit 'url' so cross-mode navigation works no
	// matter which entry script is currently loaded (one file per mode). perm 'P'
	// = the plugin's own admin permission.
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
