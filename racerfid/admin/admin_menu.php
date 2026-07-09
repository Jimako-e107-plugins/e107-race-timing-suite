<?php
/*
 * e107 website system
 *
 * racerfid plugin (RFID import) - shared admin dispatcher / menu.
 *
 * Holds the admin dispatcher (racerfid_adminArea). Each admin area is its own
 * mode pointing at its own controller/UI defined in admin/admin_config.php.
 * Structure mirrors raceevent/admin/admin_menu.php.
 *
 * Bootstrap: load the framework, check the plugin's OWN admin permission
 * (getperms('P')), load the LAN file, then declare the dispatcher.
 */

if (!defined('e107_INIT')) { exit; }

require_once("../../../class2.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

e107::lan('racerfid', true, true);

class racerfid_adminArea extends e_admin_dispatcher
{

	protected $modes = array(

		'main'	=> array(
			'controller' 	=> 'racerfid_ui',
			'path' 			=> null,
			'ui' 			=> 'racerfid_form_ui',
			'uipath' 		=> null
		),

		'dbsetting'	=> array(
			'controller' 	=> 'dbsetting_ui',
			'path' 			=> null,
			'ui' 			=> 'dbsetting_form_ui',
			'uipath' 		=> null
		),
	);


	protected $adminMenu = array(

		'main/prefs' 		=> array('caption' => LAN_PREFS, 'perm' => 'P'),

		'main/div0'      => array('divider' => true),
		'dbsetting/prefs'  => array('caption' => LAN_RACETRACKING_CONFIG, 'perm' => '0'),
		'dbsetting/testconnection'	 => array('caption' => LAN_RACETRACKING_TESTACCESS, 'perm' => '0'),

	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'
	);

	protected $menuTitle = 'RFID import';

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racerfid's own entries are excluded. The isInstalled guard
	 * keeps racerfid installable as an independent leaf - links simply don't
	 * appear when raceevent is absent.
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racerfid'))
			);
		}
	}
}
