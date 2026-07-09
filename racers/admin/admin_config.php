<?php
/*
 * e107 website system
 *
 * racers plugin - admin CRUD UI classes (categories, racers, results).
 *
 * Bootstrap: load the framework, include the shared dispatcher/menu, check the
 * plugin's OWN admin permission (getperms('P')), then run the page. Native
 * e107 e_admin_ui throughout (toDB/toHTML, parameterized queries, e_token CSRF).
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

// This file is the admin entry point for the racer (main) + category (cat) CRUD only.
// The startlists / racerlist / registration modes are now their OWN self-contained
// entry files (admin_startlists.php / admin_racerlist.php / admin_registration.php),
// each routed via its $adminMenu 'url'; they are no longer require'd here.

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class race_category_ui extends e_admin_ui
{

	protected $pluginTitle		= 'Racers';
	protected $pluginName		= 'racers';
	//	protected $eventName		= 'racers-race_category'; // remove comment to enable event triggers in admin. 		

	//	protected $eventName		= 'timetracker-'; // remove comment to enable event triggers in admin. 		
	protected $table			= 'race_category';
	protected $pid				= 'race_category_id';
	protected $perPage			= 30;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	//	protected $sortField		= 'somefield_order';
	//	protected $sortParent      = 'somefield_parent';
	//	protected $treePrefix      = 'somefield_title';

	//	protected $tabs				= array('tab1'=>'Tab 1', 'tab2'=>'Tab 2'); // Use 'tab'=>'tab1'  OR 'tab'=>'tab2' in the $fields below to enable. 

	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.

	protected $listOrder		= 'race_category_id DESC';

	protected $fields 		= array(
		'checkboxes'         => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect',),
		'race_category_id'   => array(
			'title' => LAN_ID,
			'type' => 'number',
			'force' => 1,
			'data' => 'int',
			'width' => '5%',
		),

		'race_category_race'		=> array(
			'title' => 'Trať',
			'tab' => 1,
			'type' => 'dropdown',
			'data' => 'safestr',
			'batch' => true,
			'inline' => true,
			'readParms' => array('type' => 'checkboxes'),
		),

		'race_category_gender'   => array('title' => 'Gender', 'type' => 'dropdown', 'data' => 'safestr',  'filter' => true,   'batch' => true),
		'race_category_age_from' => array('title' => 'Vek od', 'type' => 'number', 'data' => 'int',),
		'race_category_age_to'   => array('title' => 'Vek do', 'type' => 'number', 'data' => 'int',),


		'race_category_name'           => array(
			'title' => 'Názov kategórie',
			'type' => 'text',
			'required' => 1,
			'data' => 'safestr',

			'inline' => true,


			'writeParms' => 'size=block_level', 
		),

		'race_category_color'           => array(
			'title' => 'Farba kategórie',
			'type' => 'text',
			'required' => 1,
			'data' => 'safestr',

			'inline' => true,
		),



		'race_category_sef'               => array(
			'title' => LAN_SEFURL,
			'type' => 'text',
			'data' => 'safestr',

			'inline' => true,
			'validate' => true,


			'writeParms' => ['show' => 1, 'sef' => 'race_category_name'],
		),


		'options'                 => array(
			'title' => LAN_OPTIONS,
			'type' => null,
			'data' => null,
			'width' => '10%',
			'thclass' => 'center last',
			'class' => 'center last',
			'forced' => 'value',


		),
	);

	protected $fieldpref = array('race_category_id', 'race_category_gender', 'race_category_age_from', 'race_category_age_to', 'race_category_sef', 'race_category_name', 'race_category_color');


	//	protected $preftabs        = array('General', 'Other' );
	protected $prefs = array();


	public function init()
	{

		$this->fields['race_category_gender']['writeParms']['optArray'] = array('M' => LAN_RACERS_ADMIN_015, "F" => LAN_RACERS_ADMIN_016, "S" => LAN_RACERS_GLOBAL_031);

		$preteky = e107::getDb()->retrieve('race', '*', TRUE, TRUE);


		foreach ($preteky as $pretek)
		{
			$tmp[$pretek['race_id']] = $pretek['race_name'];
		}


		$this->fields['race_category_race']['writeParms']['optArray'] = $tmp;
		//$this->fields['page_metarobots']['writeParms']['title'] = 'x';
		$this->fields['race_category_race']['writeParms']['multiple'] = 1;

		unset($tmp);

	}



	// left-panel help menu area. (replaces e_help.php used in old plugins)
	public function renderHelp()
	{



		return array('caption' => $caption, 'text' => $text);
	}
}





class race_category_form_ui extends e_admin_form_ui
{
}



class racer_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_RACERS_ADMIN_003;
	protected $pluginName		= 'racers';

	protected $table			= 'racer';
	protected $pid				= 'racer_id';
	protected $perPage			= 100;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	protected $listOrder		= 'racer_number DESC';


	protected $fields 		= array(
		'checkboxes'            => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect',),

		'racer_id'             => array(
			'title' => LAN_ID,
			'type' => 'number',
			'data' => 'int',
			'width' => '5%',
		),


		'racer_firstname'           => array(
			'title' => LAN_RACERS_ADMIN_009,
			'type' => 'text',
			'data' => 'str',
			'help' => LAN_RACERS_ADMIN_023_HELP,
			'writeParms' => []
		),

		'racer_surname'           => array(
			'title' => LAN_RACERS_ADMIN_010,
			'type' => 'text',
			'data' => 'safestr'
		),

		'racer_birthday'           => array(
			'title' => LAN_RACERS_ADMIN_011,
			'type' => 'text',
			'data' => 'str',
			'help' => LAN_RACERS_ADMIN_011_HELP

		),

		'racer_race_id'        => array(
			'title' => LAN_RACERS_ADMIN_023,
			'tab' => 0,
			'type' => 'dropdown',
			'filter' => true,
			'batch' => true,
			'data' => 'str',
			'help' => LAN_RACERS_ADMIN_023_HELP,
			'writeParms' => []
		),

		'racer_category_id'    => array(
			'title' => LAN_RACERS_ADMIN_025,
			'tab' => 0,
			'filter' => true,
			'default' => 1,
			'batch'
			=> 1,
			'type' => 'dropdown',
			'data' => 'str',
			'writeParms' => []
		),

		'racer_number'         => array(
			'title' => LAN_RACERS_ADMIN_026,
			'type' => 'text',
			'data' => 'safestr',
 
			'inline' => true,

		),

		'racer_active' => array('title' => 'Nastúpil', 'type' => 'boolean', 'date ' => 'int', 'filter' => true, 'inline' => true,  'batch' => true),

		'racer_gender'           => array(
			'title' => LAN_RACERS_ADMIN_014,
			'type' => 'radio',
			'data' => 'safestr',
		),
		'racer_nacionality'           => array(
			'title' => LAN_RACERS_ADMIN_017,
			'type' => 'radio',
			'data' => 'safestr',
		),

		'racer_city' => array(
			'title' => LAN_RACERS_GLOBAL_025,
			'type' => 'text',
			'date ' => 'str',
			'filter' => true,
			'writeParms' => ['size' => 'xlarge'],
		),

		'racer_local' => array(
			'title' => LAN_RACERS_ADMIN_018,
			'type' => 'boolean',
			'date ' => 'int',
			'filter' => true,
		),

		'racer_team' => array(
			'title' => LAN_RACERS_ADMIN_019,
			'type' => 'text',
			'date ' => 'str',
			'filter' => true,
			'writeParms' => ['size' => 'xlarge'],
		),

		'racer_extid' => array(
			'title' => LAN_RACERS_ADMIN_013,
			'type' => 'number',
			'date ' => 'int',
			'filter' => true,
			'inline' => false,
			'help' => LAN_RACERS_ADMIN_013_HELP
		),

		'racer_tags' => array(
			'title' => LAN_RACERS_ADMIN_020,
			'type' => 'tags',
			'date ' => 'str'
		),


		'options' => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null, 'width' => '10%', 'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value',),
	);

	protected $fieldpref = array('racer_firstname', 'racer_surname', 'racer_active', 'racer_team');
	protected $preftabs        = array('Nastavenie',  'Tlač');

	protected $prefs = array(
		'name_hash' => array(
			'title' => LAN_RACERS_ADMIN_005,
			'type' => 'boolean',
			'data' => 'int',
			'help' => LAN_RACERS_ADMIN_005_HELP,
		),

		'display_team' => array(
			'title' => LAN_RACERS_ADMIN_006,
			'type' => 'boolean',
			'data' => 'int',
			'help' => LAN_RACERS_ADMIN_006_HELP,
		),

		'display_local' => array(
			'title' => LAN_RACERS_ADMIN_007,
			'type' => 'boolean',
			'data' => 'int',
			'help' => LAN_RACERS_ADMIN_007_HELP,
		),
		'text_local'        => array(
			'title' => LAN_RACERS_ADMIN_021,
			'tab' => 0,
			'type' => 'text',
			'data' => 'str',
			'writeParms' => ['size' => 'block-level']
		),
		'states'        => array(
			'title' => LAN_RACERS_ADMIN_022,
			'tab' => 0,
			'type' => 'text',
			'data' => 'str',
			'writeParms' => ['size' => 'block-level']
		),
		'startforage'        => array(
			'title' => LAN_RACERS_ADMIN_027,
			'tab' => 0,
			'type' => 'boolean',
			'data' => 'str',
			'writeParms' => ['enabled' => LAN_RACERS_ADMIN_028, 'disabled' => LAN_RACERS_ADMIN_029]
		),
		'manualinput'        => array(
			'title' => LAN_RACERS_ADMIN_030,
			'tab' => 0,
			'type' => 'boolean',
			'data' => 'str',
			'writeParms' => ['label' => 'yesno']
		),

		'characters'        => array(
			'title' => "Počet znakov",
			'tab' => 0,
			'type' => 'number',
			'help' => '11 - dve tisiciny',
			'data' => 'int',
			'writeParms' => ['default' => '11']
		),

		'print_css'        => array(
			'title' => "CSS pre export",
			'tab' => 1,
			'type' => 'textarea',
			'data' => 'str',
			'writeParms' => ['size' => 'block-level']
		),

		'print_html'        => array(
			'title' => "HTML pre export",
			'tab' => 1,
			'type' => 'textarea',
			'data' => 'str',
			'writeParms' => ['size' => 'block-level']
		),
		'print_local'        => array(
			'title' => LAN_RACERS_ADMIN_021,
			'tab' => 1,
			'type' => 'text',
			'data' => 'str',
			'writeParms' => ['size' => 'block-level']
		),

	);


	public function init()
	{

		// if (e107::isInstalled('timetracker'))
		// {
		// 	$message = e107::getMessage()->addWarning("Plugin timetracker is not installed!")->render();
		// 	echo $message;
		// }


		$states = e107::pref('racers', 'states');
		$states_array = explode(',', $states);
		$states_assoc_array = array_combine($states_array, $states_array);
		$this->fields['racer_nacionality']['writeParms']['optArray'] = $states_assoc_array;


		$preteky = e107::getDb()->retrieve('race', '*', TRUE, TRUE);


		$this->fields['racer_birthday']['writeParms']['post'] = "<div>Vek (roky): <span id='xy'></span></div>";


		$tmp[0] = LAN_RACERS_ADMIN_024;
		foreach ($preteky as $pretek)
		{
			$tmp[$pretek['race_id']] = $pretek['race_name'];
		}

		$this->fields['racer_race_id']['writeParms']['optArray'] = $tmp;

		$preteky = $tmp;
		unset($tmp);

		$kategorie = e107::getDb()->retrieve('race_category', '*', TRUE, TRUE);

		$tmp[0] = LAN_RACERS_ADMIN_024;
		foreach ($kategorie as $cat)
		{
			$tmp[$cat['race_category_id']] = $preteky[$cat['race_category_race']]  . " : " . $cat['race_category_name'];
		}

		$this->fields['racer_category_id']['writeParms']['optArray'] = $tmp;

		$this->fields['racer_gender']['writeParms']['optArray'] = array('M' => LAN_RACERS_ADMIN_015, "F" => LAN_RACERS_ADMIN_016);
	}

	public function renderHelp()
	{
		$caption = LAN_HELP;

		$text = LAN_RACERS_ADMIN_008 . ':<br>
        <pre style="color: red; user-select:all; cursor:pointer; max-width:326px; overflow-x:scroll">&lt;i class=fas fa-map-marker-alt&gt;&lt;/i&gt;</pre>
       <i class="fas fa-map-marker-alt"></i><hr>';

		// The on-site registration info that used to live here now has its own admin
		// item "Registrácia na mieste" (admin/admin_registration.php, mode 'registration').

		return array('caption' => $caption, 'text' => $text);
	}

 

	public function beforeUpdate($new_data, $old_data, $id)
	{

		return $new_data;
	}
}



class racer_form_ui extends e_admin_form_ui
{
}



class racer_result_ui extends e_admin_ui
{

	protected $pluginTitle		= 'racers';
	protected $pluginName		= 'racers';
	//	protected $eventName		= 'racers-racer_result'; // remove comment to enable event triggers in admin. 		
	protected $table			= 'racer_result';
	protected $pid				= 'racer_result_id';
	protected $perPage			= 10;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	//	protected $sortField		= 'somefield_order';
	//	protected $sortParent      = 'somefield_parent';
	//	protected $treePrefix      = 'somefield_title';

	//	protected $tabs				= array('tab1'=>'Tab 1', 'tab2'=>'Tab 2'); // Use 'tab'=>'tab1'  OR 'tab'=>'tab2' in the $fields below to enable. 

	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.

	protected $listOrder		= 'racer_result_id DESC';

	protected $fields 		= array(
		'checkboxes'              => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect',),
		'racer_result_id'         => array('title' => LAN_ID, 'type' => 'number', 'data' => 'int', 'width' => '5%',),
		'racer_result_racer'      => array('title' => 'Racer', 'type' => 'dropdown', 'data' => 'int',  'filter' => false, 'batch' => false,),
		'racer_result_number'     => array('title' => 'Number', 'type' => 'text', 'data' => 'safestr',),
		'racer_result_time'       => array('title' => 'Time', 'type' => 'text', 'data' => 'safestr',),
		'racer_result_sent'       => array('title' => 'Sent', 'type' => 'boolean', 'data' => 'int',),
		'racer_result_log'        => array('title' => 'Log', 'type' => 'textarea', 'data' => 'str',),
		'racer_result_created'    => array('title' => 'Created', 'type' => 'datestamp', 'data' => 'int',),
		'racer_result_updated'    => array('title' => 'Updated', 'type' => 'datestamp', 'data' => 'int',),
		'options'                 => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null, 'width' => '10%', 'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value',),
	);

	protected $fieldpref = array();


	public function init()
	{
		// This code may be removed once plugin development is complete. 
		if (!e107::isInstalled('racers'))
		{
			e107::getMessage()->addWarning("This plugin is not yet installed. Saving and loading of preference or table data will fail.");
		}

		// Set drop-down values (if any). 
		$this->fields['racer_result_racer']['writeParms']['optArray'] = array('racer_result_racer_0', 'racer_result_racer_1', 'racer_result_racer_2'); // Example Drop-down array. 

	}

	// ------- Customize Create --------

	public function beforeCreate($new_data, $old_data)
	{
		return $new_data;
	}

	public function afterCreate($new_data, $old_data, $id)
	{
		// do something
	}

	public function onCreateError($new_data, $old_data)
	{
		// do something		
	}


	// ------- Customize Update --------

	public function beforeUpdate($new_data, $old_data, $id)
	{

		return $new_data;
	}

	public function afterUpdate($new_data, $old_data, $id)
	{
		// do something	
	}

	public function onUpdateError($new_data, $old_data, $id)
	{
		// do something		
	}
 
}



class racer_result_form_ui extends e_admin_form_ui
{
}


new racers_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
