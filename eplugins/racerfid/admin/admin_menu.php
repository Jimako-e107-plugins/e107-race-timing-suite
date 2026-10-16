<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racerfid plugin - admin/admin_menu.php
 *
*/

/*
 * Shared admin dispatcher / menu; the controllers live in admin/admin_config.php.
 * The database screens are main-admin only (perm '0').
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
	 * Append the cross-plugin shortcuts (docs/getting-started/admin-area.md,
	 * "Cross-plugin shortcuts") from raceevent/includes/admin_links.php,
	 * excluding our own. The isInstalled guard keeps racerfid independent.
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
