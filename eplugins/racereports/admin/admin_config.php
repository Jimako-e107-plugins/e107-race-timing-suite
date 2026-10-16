<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_config.php
 *
*/

/*
 * Settings screen (fields and defaults: docs/plugins/racereports.md, "Settings").
 * Native e_admin_ui $prefs form - the core renders it and handles Save, the e_token
 * (CSRF) and storage; the reports read the values via
 * e107::getPlugConfig('racereports'). It is the dispatcher's default mode, so a bare
 * admin_config.php load lands on a controller this file defines. The report screens
 * live in their own admin_*.php files.
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
 * Settings (mode 'main'): native e_admin_ui $prefs form, no DB table.
 */
class racereports_main_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEREPORTS_001;
	protected $pluginName  = 'racereports';

	// Prefs-only screen - no DB table.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'prefs';

	// String tab keys: each pref binds to a tab by key ('tab' => '<key>'), so the
	// tab order is just the order of the entries here.
	protected $preftabs = array(
		'dec'     => LAN_ADMIN_RACEREPORTS_112, // Desatiny / Decimals
		'colors'  => LAN_ADMIN_RACEREPORTS_113, // Podfarbenie / Coloring
		'refresh' => LAN_ADMIN_RACEREPORTS_114, // Obnovenie / Refresh
		'custom'  => LAN_ADMIN_RACEREPORTS_115, // Ostatné / Other
	);

	// Fields and defaults: docs/plugins/racereports.md, "Settings". The defaults
	// are applied by the consumers (get('<pref>', <default>)), not here. Each value
	// is sanitised through its 'data' type (int) before storage.
	protected $prefs = array(
		// Display-only; sort keys always use the full-precision seconds. min/max
		// match the 3 decimals the data carries.
		'result_decimals' => array(
			'tab'        => 'dec',
			'title'      => LAN_ADMIN_RACEREPORTS_110,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_111,
			'writeParms' => array('min' => 0, 'max' => 3),
		),
		// min=0 keeps a negative count away from race_format::formatElapsed().
		'stu_decimals' => array(
			'tab'        => 'dec',
			'title'      => LAN_ADMIN_RACEREPORTS_070,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_071,
			'writeParms' => array('min' => 0),
		),
		'stu_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_072,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_073,
		),
		'online_refresh_interval' => array(
			'tab'        => 'refresh',
			'title'      => LAN_ADMIN_RACEREPORTS_074,
			'type'       => 'number',
			'data'       => 'int',
			'help'       => LAN_ADMIN_RACEREPORTS_075,
			'writeParms' => array('min' => 0),
		),
 
		'finish_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_076,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_077,
		),
		'finish_categ' => array(
			'tab'   => 'custom',
			'title' => LAN_ADMIN_RACEREPORTS_095,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_096,
		),
		'start_colors' => array(
			'tab'   => 'colors',
			'title' => LAN_ADMIN_RACEREPORTS_078,
			'type'  => 'boolean',
			'data'  => 'int',
			'help'  => LAN_ADMIN_RACEREPORTS_079,
		),
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
