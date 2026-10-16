<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racerfid plugin - admin/admin_config.php
 *
*/

/*
 * Admin screens (user view: docs/plugins/racerfid.md, "Admin area"):
 * racerfid_ui = plugin settings, dbsetting_ui = database access + test.
 * Native e_admin_ui $prefs forms (core handles storage and the e_token CSRF
 * check). plugin_racerfid_import is autoloaded from includes/import.php.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}






class racerfid_ui extends e_admin_ui
{

	protected $pluginTitle		= 'RFID import';
	protected $pluginName		= 'racerfid';
	protected $table			= '';
	protected $pid				= '';
	protected $perPage			= 10;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;


	protected $listOrder		= ' DESC';

	protected $fields 		= array();

	protected $fieldpref = array();


	protected $prefs = array(
		'tracking_active'		=> array('title' => LAN_RACETRACKING_PREF_001, 'tab' => 0, 'type' => 'boolean', 'data' => 'int', 'help' => LAN_RACETRACKING_PREF_001_HELP, 'writeParms' => []),
		'cron_disabled'		=> array('title' => LAN_RACETRACKING_PREF_002, 'tab' => 0, 'type' => 'boolean', 'data' => 'int', 'help' => LAN_RACETRACKING_PREF_002_HELP, 'writeParms' => []),
		'refresh_interval' => array('title' => LAN_RACETRACKING_PREF_REFRESH, 'tab' => 0, 'type' => 'number', 'data' => 'int', 'help' => LAN_RACETRACKING_PREF_REFRESH_HELP, 'writeParms' => []),

	);


	public function init()
	{
 

	}

	public function beforeCreate($new_data, $old_data)
	{
		return $new_data;
	}

	public function afterCreate($new_data, $old_data, $id)
	{
	}

	public function onCreateError($new_data, $old_data)
	{
	}


	public function beforeUpdate($new_data, $old_data, $id)
	{
		return $new_data;
	}

	public function afterUpdate($new_data, $old_data, $id)
	{
	}

	public function onUpdateError($new_data, $old_data, $id)
	{
	}

	// Left-panel help box.
	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text = LAN_RACETRACKING_HELP_002;


		$text .= "<hr>";
		$text .= LAN_RACETRACKING_HELP_MANUAL_LINK . " <br> ";
		$link = e_PLUGIN_ABS . "racerfid/citacka.php";

		$text .= "<a class='btn btn-success' target='_blank' href='" . $link . "'>" . LAN_RACETRACKING_HELP_MANUAL_BTN . "</a><br>";

		$text .= LAN_RACETRACKING_HELP_INTERVAL_NOTE . " ";


		return array('caption' => $caption, 'text' => $text);
	}
}

class dbsetting_ui extends racerfid_ui
{


	protected $fields 		= array();

	protected $fieldpref = array();


	protected $prefs = array(
		'e107db_server'		=> array('title' => LAN_RACETRACKING_SERVER, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_port'		=> array('title' => LAN_RACETRACKING_PORT, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_username'	=> array('title' => LAN_RACETRACKING_USERNAME, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_password'	=> array('title' => LAN_RACETRACKING_PASSWORD, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_database'	=> array('title' => LAN_RACETRACKING_DATABASE, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_prefix'		=> array('title' => LAN_RACETRACKING_PREFIX, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => []),
		'e107db_table'		=> array('title' => LAN_RACETRACKING_TABLE, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => ['default'=> 'race_tracking']),
		'e107db_fieldname'  => array('title' => LAN_RACETRACKING_FIELDNAME, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => ['default' => 'Meno']),
		'e107db_fieldnumber'  => array('title' => LAN_RACETRACKING_FIELDNUMBER, 'tab' => 0, 'type' => 'text', 'data' => 'str', 'help' => '', 'writeParms' => ['default' => 'TagID']),
		// No start/finish column prefs: each checkpoint's DB Import Field
		// (racetrack race_point_dbfield) names its column.
	);

	private $import;
	public function init()
	{
	}



	// Left-panel help box.
	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text = LAN_RACETRACKING_HELP_001;

		



		return array('caption' => $caption, 'text' => $text);
	}


	public function testconnectionPage()
	{
		$import = new plugin_racerfid_import();

		$import->testconnection(); 
 
	}
 
}

class racerfid_form_ui extends e_admin_form_ui
{
}


new racerfid_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
