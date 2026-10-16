<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - admin/admin_menu.php
 *
*/

/*
 * Shared admin dispatcher / menu. Each admin screen is its own mode served by its
 * own file; a new screen = a new file plus an entry in $modes and $adminMenu.
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
		// Event configuration - admin/admin_config.php.
		'main' => array(
			'controller' => 'raceevent_config_ui',
			'path'       => null,
			'ui'         => 'raceevent_config_form_ui',
			'uipath'     => null,
		),
		// Season maintenance - admin/admin_maintenance.php (custom 'udrzba'
		// action). Cross-plugin, so it lives in the base plugin.
		'maintenance' => array(
			'controller' => 'raceevent_maintenance_ui',
			'path'       => null,
			'ui'         => 'raceevent_maintenance_form_ui',
			'uipath'     => null,
		),
		// Navigation check - admin/admin_checklinks.php (custom 'check' action).
		'checklinks' => array(
			'controller' => 'raceevent_checklinks_ui',
			'path'       => null,
			'ui'         => 'raceevent_checklinks_form_ui',
			'uipath'     => null,
		),
		// Event overview - admin/admin_overview.php (custom 'view' action).
		'overview' => array(
			'controller' => 'raceevent_overview_ui',
			'path'       => null,
			'ui'         => 'raceevent_overview_form_ui',
			'uipath'     => null,
		),
	);

	// Every item carries an explicit 'url' so cross-mode navigation works no
	// matter which entry script is currently loaded (one file per mode).
	protected $adminMenu = array(
		'main/prefs' => array('caption' => LAN_RACEEVENT_CONFIG, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_config.php'),
		'divider1'          => array('divider' => true),
		'maintenance/udrzba' => array('caption' => LAN_MAINTENANCE, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_maintenance.php'),
		'checklinks/check' => array('caption' => LAN_RACEEVENT_CHECKLINKS, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_checklinks.php'),
		'overview/view' => array('caption' => LAN_RACEEVENT_OV_MENU, 'perm' => 'P', 'url' => '{e_PLUGIN}raceevent/admin/admin_overview.php'),
	);

	protected $adminMenuAliases = array();

	protected $menuTitle = LAN_GLOBAL_RACEEVENT_001;

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
