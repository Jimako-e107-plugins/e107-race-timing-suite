<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - admin/admin_menu.php
 *
*/

/*
 * Shared admin dispatcher for all four areas. Each area has its own entry file
 * (admin_config.php = tracks, admin_points.php, admin_prices.php,
 * admin_archive.php); every mode's 'path' is null and each $adminMenu item
 * carries 'url' to that file. This is safe because e_admin_ui only instantiates
 * the ACTIVE mode's controller.
 *
 * The admin LAN is loaded here, before the class is declared, because the class
 * property defaults use racetrack LAN constants.
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

e107::lan('racetrack', true, true);
// _global is loaded by the core on every page (lan_global_list pref).
e107::coreLan('user');
e107::coreLan('users', true);
e107::coreLan('date');


class racetrack_adminArea extends e_admin_dispatcher
{

	protected $modes = array(

		'main'	=> array(
			'controller' 	=> 'racetrack_ui',
			'path' 			=> null,
			'ui' 			=> 'racetrack_form_ui',
			'uipath' 		=> null
		),
		// Controller class in admin/admin_points.php.
		'control'	=> array(
			'controller' 	=> 'racetrack_point_ui',
			'path' 			=> null,
			'ui' 			=> 'racetrack_point_form_ui',
			'uipath' 		=> null
		),
		// Controller class in admin/admin_prices.php.
		'prices'	=> array(
			'controller' 	=> 'racetrack_price_ui',
			'path' 			=> null,
			'ui' 			=> 'racetrack_price_form_ui',
			'uipath' 		=> null
		),
		// Controller class in admin/admin_archive.php.
		'archive'	=> array(
			'controller' 	=> 'race_archive_ui',
			'path' 			=> null,
			'ui' 			=> 'race_archive_form_ui',
			'uipath' 		=> null
		),

	);

	// Every item needs 'url' to the entry file that defines the mode's
	// controller (relative: all entry files are in this admin/ folder).
	protected $adminMenu = array(

		'main/list'			=> array('caption' => LAN_ADMIN_RACE_003, 'perm' => 'P', 'url' => 'admin_config.php'),
		'main/create'		=> array('caption' => LAN_ADMIN_RACE_004, 'perm' => 'P', 'url' => 'admin_config.php'),
		'divider1'          => array('divider' => true),
		//	'main/prefs' 		=> array('caption' => LAN_PREFS, 'perm' => 'P' ),
		'control/list'			=> array('caption' => LAN_ADMIN_POINTS, 'perm' => 'P', 'url' => 'admin_points.php'),
		'control/create'		=> array('caption' => LAN_ADMIN_POINTS_ADD, 'perm' => 'P', 'url' => 'admin_points.php'),
		'divider2'          => array('divider' => true),
		'prices/list'			=> array('caption' => LAN_ADMIN_PRICES, 'perm' => 'P', 'url' => 'admin_prices.php'),
		'prices/create'			=> array('caption' => LAN_ADMIN_PRICES_ADD, 'perm' => 'P', 'url' => 'admin_prices.php'),
		'divider3'          => array('divider' => true),
		'archive/list'			=> array('caption' => LAN_ADMIN_ARCHIVE, 'perm' => 'P', 'url' => 'admin_archive.php'),
		'archive/create'		=> array('caption' => LAN_ADMIN_ARCHIVE_ADD, 'perm' => 'P', 'url' => 'admin_archive.php'),


	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'
	);

	protected $menuTitle = LAN_GLOBAL_RACE_001;

	/**
	 * Append the cross-plugin admin-menu shortcuts from
	 * raceevent/includes/admin_links.php (racetrack's own entries excluded).
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racetrack'))
			);
		}
	}
}
