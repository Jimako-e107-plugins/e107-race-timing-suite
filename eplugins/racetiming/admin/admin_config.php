<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - admin/admin_config.php
 *
*/

/*
 * race_time CRUD: Time Entries / Add Time Entry (docs/plugins/racetiming.md).
 *
 * - race_time_racer_number is a string (leading zeros are data), never an int.
 * - race_time_time is a plain text field holding the 'Y-m-d H:i:s.v' millisecond
 *   string - never a datestamp type, which would convert it to a unix int and
 *   lose the milliseconds.
 * - Queries go through the db class; e_admin_ui handles the e_token (CSRF).
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

class race_time_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_GLOBAL_RACETIMING_001;
	protected $pluginName		= 'racetiming';
	protected $table			= 'race_time';
	protected $pid				= 'race_time_id';
	protected $perPage			= 200;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	protected $listOrder		= 'race_time_racer_number DESC';

	protected $fields 		= array(
		'checkboxes'              => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect', 'readParms' => [], 'writeParms' => [],),

		'race_time_id'            => array('title' => LAN_ID,   'data' => 'int',

		'width' => '5%', 'help' => '', 'readParms' => [],
		'writeParms' => [], 'class' => 'left', 'thclass' => 'left',),

		'race_time_point'      => array(
			'title' => LAN_ADMIN_RACETIMING_020, 'type' => 'dropdown', 'data' => 'str', 'width' => 'auto',
			'filter' => true, 'validate' => true, 'help' => LAN_ADMIN_RACETIMING_021, 'readParms' => [], 'writeParms' => [],
			'class' => 'left', 'thclass' => 'left', 'batch' => true,
		),

		'race_time_racer_number'  => array('title' => LAN_ADMIN_RACETIMING_022,
		'type' => 'text', 'data' => 'str', 'width' => 'auto',
		'validate' => true, 'help' => LAN_ADMIN_RACETIMING_023, 'readParms' => [], 'writeParms' => [],
		'class' => 'left', 'thclass' => 'left', 'batch' => false,),

		'race_time_time'       => array('title' => LAN_ADMIN_RACETIMING_024,
			'validate' => true,
		'type' => 'text', 'data' => 'str', 'width' => 'auto',  'help' => LAN_ADMIN_RACETIMING_025,
		'readParms' => [], 'writeParms' => [], 'class' => 'left', 'thclass' => 'left',),

		'race_time_ended' =>
			array(
				'title' => LAN_ADMIN_RACETIMING_026,
				'type' => 'dropdown', 'data' => 'str',  'filter'=> true,
				'width' => 'auto',  'help' => LAN_ADMIN_RACETIMING_027,
				'readParms' => [], 'writeParms' => [], 'class' => 'left', 'thclass' => 'left',
			),


		'race_time_created'       => array('title' => LAN_ADMIN_RACETIMING_028, 'type' => 'datestamp', 'data' => 'int',
		'noedit'=>1, 'width' => 'auto',  'help' => '', 'readParms' => [], 'writeParms' => [], 'class' => 'left', 'thclass' => 'left',),
		'race_time_updated'       => array('title' => LAN_ADMIN_RACETIMING_029, 'type' => 'datestamp', 'data' => 'int',
			'width' => 'auto',
			'noedit' => 1,
			'help' => '', 'readParms' => [],
			'writeParms' => [], 'class' => 'left', 'thclass' => 'left',),
		'options'                 => array('title' => LAN_OPTIONS, 'type' => null, 'data' => null,
		'width' => '10%', 'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value',
		'readParms' => [], 'writeParms' => [],),
	);

	protected $fieldpref = array('race_time_race_id', 'race_time_racer_number', 'race_time_point', 'race_time_created', 'race_time_updated');

	public function init()
	{
		$numbers = e107::getDb()->retrieve('racer', "*", true, true, "racer_number");
		$values = array();
		foreach ($numbers as $key => $racer)
		{
			$values[$key] = $key . " - " . $racer['racer_surname'] .  " " . $racer['racer_firstname'];
		}

		$this->fields['race_time_racer_number']['writeParms']['optArray'] = $values;

		$preteky = e107::getDb()->retrieve('race', '*', TRUE, TRUE);

		$tmp[0] = "---";
		foreach ($preteky as $pretek)
		{
			$tmp[$pretek['race_id']] = $pretek['race_name'];
		}

		$this->fields['race_time_race_id']['writeParms']['optArray'] = $tmp;

		$numbers = e107::getDb()->retrieve('race_point', "*", true, true, "race_point_code");
		$values = array();
		foreach ($numbers as $key => $racer)
		{
			$values[$key] = $key . " - " .    $racer['race_point_name'];

		}

		$this->fields['race_time_point']['writeParms']['optArray'] = $values;

		$this->fields['race_time_ended']['writeParms']['useKeys'] = 1;
		$this->fields['race_time_ended']['writeParms']['optArray'] = [''=>'', 'DNF'=>'DNF', 'DSQ'=> 'DSQ', 'DNS' => 'DNS' ];

		$current_time = date('Y-m-d H:i:s', time());
		$this->fields['race_time_time']['writeParms']['default'] = $current_time;
	}

	public function beforeCreate($new_data, $old_data)
	{
		$new_data['race_time_created'] = time();
		$new_data['race_time_updated'] = time();
		return $new_data;
	}

	public function beforeUpdate($new_data, $old_data, $id)
	{
		$new_data['race_time_updated'] = time();
		return $new_data;
	}

	public function afterUpdate($new_data, $old_data, $id)
	{
		$racer_number = $data['race_result_number'] = $new_data['race_time_racer_number'];

		if($new_data['race_time_ended'] == '')  e107::getEvent()->trigger('terminovka_deleteresult', $data);
		else {

			$data['race_result_time'] = $new_data['race_time_ended'];
			$data['race_result_log'] = "";
			$data['race_result_updated'] = time();
			$data['race_result_sent'] = 0;

			// The terminovka_* events are handled by terminovka/e_event.php (only
			// when terminovka is installed); sending to the API is done by the
			// terminovka batch page. See docs, "The three statuses".
			e107::getEvent()->trigger('terminovka_saveresult', $data);
		}
	}

	public function afterDelete($deleted_data, $id, $deleted_check)
	{
		$data['race_result_number'] = $deleted_data['race_time_racer_number'];
		e107::getEvent()->trigger('terminovka_deleteresult', $data);
	}

	public function renderHelp()
	{
		// $caption = LAN_HELP;
		// $text = LAN_TR_IMPORT_INFO;

		// return array('caption' => $caption, 'text' => $text);
	}
}

class race_time_form_ui extends e_admin_form_ui
{
}

new racetiming_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
