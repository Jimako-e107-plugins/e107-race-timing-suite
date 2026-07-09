<?php
/*
 * e107 website system
 *
 * racetrack plugin - checkpoints (race_point) administration.
 *
 * Self-contained admin entry file for the `control` mode (checkpoints CRUD),
 * split out of admin/admin_config.php. Mirrors racetiming's one-file-per-area
 * layout (racetiming/admin/admin_generujstart.php): bootstrap, require the
 * shared dispatcher (admin_menu.php), gate on the plugin's OWN admin permission,
 * define ONLY this area's controller + form classes, then run the page. The
 * dispatcher's `control` mode points here via its $adminMenu 'url'.
 *
 * Field definitions moved verbatim from admin_config.php - no behavioural change.
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

class racetrack_point_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_ADMIN_POINTS;
	protected $pluginName		= 'racetrack';
	//	protected $eventName		= 'race-point-'; // remove comment to enable event triggers in admin.
	protected $table			= 'race_point';
	protected $pid				= 'race_point_id';
	protected $perPage			= 20;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	protected $listOrder		= 'race_point_order ASC';
	protected $sortField	= 'race_point_order';

	protected $fields 		= array(
		'checkboxes'              => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect', 'readParms' => [], 'writeParms' => [],),
		'race_point_id'                      => array(
			'title' => LAN_ID,
			'type' => 'number',
			'force' => true,
			'data' => 'int',
			'width' => '5%',
			'help' => '',
			'readParms' => [],
			'writeParms' => [],

			'thclass' => 'left',
		),


		'race_point_race'		=> array(
			'title' => 'Pretek',
			'tab' => 1,
			'type' => 'dropdown',
			'data' => 'safestr',
			'batch' => true,
			'inline' => true,
			'readParms' => array('type' => 'checkboxes'),
			'width' => 'auto',
			'thclass' => 'left',

			'nosort' => false,
			'filter' => true
		),

		'race_point_code'                    => array('title' => 'Code', 'type' => 'text', 'data' => 'safestr', 'width' => 'auto', 'inline' => true, 'validate' => true, 'help' => '', 'readParms' => [], 'writeParms' => [],  'thclass' => 'left',),


		'race_point_dbfield'                    => array('title' => 'DB Import Field', 'type' => 'text', 'data' => 'safestr', 'width' => 'auto', 
		
		'inline' => true,  'help' => 'DB pole z import tabuľky ', 'readParms' => [], 'writeParms' => [],  'thclass' => 'left',),

		
		'race_point_name'                    => array(
			'title' => LAN_TITLE,
			'type' => 'text',
			'data' => 'safestr',
			'width' => 'auto',
			'inline' => true,
			'validate' => true,
			'help' => '',
			'readParms' => [],
			'writeParms' => ['size' => 'block-level'],

			'thclass' => 'left',
		),
		'race_point_password'                => array(
			'title' => 'Password',
			'type' => 'password',
			'data' => 'safestr',
			'width' => 'auto',
			'inline' => true,
			'validate' => true,
			'help' => '',
			'readParms' => [],
			'writeParms' => [
				'size' => 50,
				'class' => 'tbox e-password',
				'placeholder' => USRLAN_251,
				'generate' => 1,
				'strength' => 1,
				'required' => 0,
			],

			'thclass' => 'left',
		),

		'race_point_order'           => array(
			'title' => LAN_ORDER,
			'type' => 'number',
			'data' => 'int',
			'width' => 'auto',
			'help' => '',
			'readParms' => [],
			'writeParms' => [],

			'thclass' => 'left',
		),
		'options'                 => array(
			'title' => LAN_OPTIONS,
			'type' => 'method',
			'data' => null,
			'width' => '10%',
			'thclass' => 'center last',
			'class' => 'center last',
			'forced' => 'value',
			'readParms' => 'sort=1',
			'writeParms' => [],
		),
	);



	protected $fieldpref = array('race_point_code', 'race_point_name', 'race_point_password', 'race_point_order');


	public function init()
	{

		$preteky = e107::getDb()->retrieve('race', '*', TRUE, TRUE);


		foreach ($preteky as $pretek)
		{
			$tmp[$pretek['race_id']] = $pretek['race_name'];
		}


		$this->fields['race_point_race']['writeParms']['optArray'] = $tmp;
		//$this->fields['page_metarobots']['writeParms']['title'] = 'x';
		$this->fields['race_point_race']['writeParms']['multiple'] = 1;

		unset($tmp);
	}


	// left-panel help menu area. (replaces e_help.php used in old plugins)
	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text = 'Kontrolné body pre štart a cieľ musia mať kódy start a finish, inak sa zobrazia ako klasické kontroly.';

		return array('caption' => $caption, 'text' => $text);
	}
}



class racetrack_point_form_ui extends e_admin_form_ui
{
	function options($att, $value, $id, $attributes)
	{
		if ($attributes['mode'] == 'read')
		{

			$Data = $this->getController()->getListModel()->getData();
			$Data['race_sef'] = "all";

			$url1 = e107::url('racetrack', 'point', $Data, ['mode' => 'full']);
			$url2 = e107::url('racetiming', 'kontrola', $Data, ['mode' => 'full']); // `kontrola` route moved to racetiming

	 

			$text = "<div class='btn-group'>";

			$text .= "<a target='_blank' class='btn btn-default' href='" . $url1 . "'>" . ADMIN_VIEW_ICON . "</a>";
			$text .= "<a target='_blank' class='btn btn-default' href='" . $url2 . "'>" . ADMIN_EXECUTE_ICON . "</a>";
			$text .= $this->renderValue('options', $value, array('readParms' => 'edit=1&sort=1'), $id);
			$text .= "</div>";



			return $text;
		}
	}
}


new racetrack_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
