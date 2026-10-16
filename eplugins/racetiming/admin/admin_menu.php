<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - admin/admin_menu.php
 *
*/

/*
 * Admin dispatcher: 'main' = race_time CRUD (admin_config.php), 'generate' =
 * start time setting + start generation (admin_generujstart.php).
 *
 * getperms('P') resolves to this plugin's own admin permission because the file
 * lives under the plugins folder. Input goes through $tp->toDB(), output through
 * $tp->toHTML(); e_admin_ui handles the e_token (CSRF).
 */

if (!defined('e107_INIT'))
{
	require_once("../../../class2.php");
}

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// raceevent admin terms first, then racetiming's. Array-return LAN files, so a
// missing translation falls back to English per key. Must run before the class
// below (its property defaults use LAN constants). _global is loaded by the core
// for every installed plugin (lan_global_list pref), so it is not loaded here.
e107::lan('raceevent', true, true);
e107::lan('racetiming', true, true);

class racetiming_adminArea extends e_admin_dispatcher
{
	protected $modes = [
		'main' => [
			'controller' => 'race_time_ui',
			'path'       => null,
			'ui'         => 'race_time_form_ui',
			'uipath'     => null
		],
		// Start generation - admin/admin_generujstart.php. Only that file defines
		// the controller; e_admin_ui only instantiates the active mode.
		'generate' => [
			'controller' => 'race_generate_ui',
			'path'       => null,
			'ui'         => 'race_generate_form_ui',
			'uipath'     => null
		],
	];

	protected $adminMenu = [
		'main/list' => [
			'caption' => LAN_ADMIN_RACETIMING_010,
			'perm'    => 'P',
			'url'     => 'admin_config.php'
		],
		'main/create' => [
			'caption' => LAN_ADMIN_RACETIMING_011,
			'perm'    => 'P',
			'url'     => 'admin_config.php'
		],

		'divider1' => ['divider' => true],

		// Start-generation: the default-time pref, then the generation page.
		'generate/prefs' => [
			'caption' => LAN_ADMIN_RACETIMING_052,
			'perm'    => 'P',
			'url'     => 'admin_generujstart.php'
		],
		'generate/generujStart' => [
			'caption' => LAN_ADMIN_RACETIMING_043,
			'perm'    => 'P',
			'url'     => 'admin_generujstart.php'
		],
	];

	protected $adminMenuAliases = [];

	protected $menuTitle = LAN_GLOBAL_RACETIMING_001;

	/**
	 * Append the shared cross-plugin admin-menu shortcuts from
	 * raceevent/includes/admin_links.php, excluding racetiming's own entries.
	 */
	public function init()
	{
		if (e107::isInstalled('racetiming'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racetiming'))
			);
		}
	}
}
