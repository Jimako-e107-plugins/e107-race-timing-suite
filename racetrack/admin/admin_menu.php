<?php
/*
 * e107 website system
 *
 * racetrack plugin - shared admin dispatcher / menu.
 *
 * Shared admin dispatcher for the racetrack plugin, mirroring the sibling
 * layout (racetiming): one admin file per entity area. Each $adminMenu entry
 * carries an explicit 'url' to its area's OWN entry file, and every mode's
 * 'path' is null - the dispatcher does NOT include the controller files. The
 * menu URL routes a mode to the file that defines its controller, and
 * e_admin_ui only instantiates the ACTIVE mode's controller, so listing a mode
 * whose class lives in another file is safe:
 *   - main    (tracks)      -> admin/admin_config.php   (also the admin entry point)
 *   - control (checkpoints) -> admin/admin_points.php
 *   - prices  (price tiers) -> admin/admin_prices.php
 *
 * LAN is loaded HERE (before the dispatcher class is declared, because the
 * class property defaults reference racetrack LAN constants). Every area entry
 * file require_once()s this dispatcher, so this is the single place the admin
 * LAN is loaded for all three areas - mirroring racetiming/admin/admin_menu.php.
 *
 * The init() override appends the centralized cross-plugin admin-menu shortcuts
 * from raceevent/includes/admin_links.php (guarded on raceevent being installed).
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
// _global is loaded automatically by the core on every page via the
// lan_global_list pref, so no manual 'global' load is needed here.
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
		// Checkpoints CRUD. Controller lives in admin/admin_points.php; only that
		// file loads the class, so listing the mode here is safe (e_admin_ui only
		// instantiates the active mode).
		'control'	=> array(
			'controller' 	=> 'racetrack_point_ui',
			'path' 			=> null,
			'ui' 			=> 'racetrack_point_form_ui',
			'uipath' 		=> null
		),
		// Price tiers CRUD. Controller lives in admin/admin_prices.php (active-mode
		// only, as above).
		'prices'	=> array(
			'controller' 	=> 'racetrack_price_ui',
			'path' 			=> null,
			'ui' 			=> 'racetrack_price_form_ui',
			'uipath' 		=> null
		),
		// Archive CRUD. Controller lives in admin/admin_archive.php, its OWN
		// self-contained entry file (like admin_points.php / admin_prices.php);
		// the 'archive/*' $adminMenu items below carry 'url' => 'admin_archive.php'
		// so the mode routes straight there. Only that file loads the class, so
		// listing the mode here is safe (e_admin_ui only instantiates the active
		// mode).
		'archive'	=> array(
			'controller' 	=> 'race_archive_ui',
			'path' 			=> null,
			'ui' 			=> 'race_archive_form_ui',
			'uipath' 		=> null
		),

	);

	// Every item carries an explicit 'url' to its area's own entry file so
	// cross-area navigation routes to the file that defines that mode's
	// controller (relative URL: all three files live in this admin/ folder).
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
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racetrack's own entries are excluded from the shortcut list.
	 * Guarded on raceevent being installed so racetrack degrades cleanly without it.
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
