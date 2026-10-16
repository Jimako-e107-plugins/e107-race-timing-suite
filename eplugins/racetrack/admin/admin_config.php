<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - admin/admin_config.php
 *
*/

/*
 * Track list screen (fields, warnings, buttons: docs/plugins/racetrack.md,
 * "Track list"). Also the plugin's admin entry point (plugin.xml adminLinks).
 * The other areas have their own entry files; admin_menu.php is the shared
 * dispatcher.
 */

require_once('../../../class2.php');

require_once('admin_menu.php'); // shared dispatcher / menu + LAN (racetrack_adminArea)

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

// racetrack_archive_trigger() for the Archive row button below.
require_once(e_PLUGIN . 'racetrack/includes/race_archive_generate.php');


class racetrack_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_GLOBAL_RACE_001;
	protected $pluginName		= 'racetrack';
	// Required: e_admin.php addons (terminovka) target this form via
	// $ui->getEventName() === 'race'.
	protected $eventName		= 'race';
	protected $table			= 'race';
	protected $pid				= 'race_id';
	protected $perPage			= 10;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

	//	protected $sortField		= 'somefield_order';
	//	protected $sortParent      = 'somefield_parent';
	//	protected $treePrefix      = 'somefield_title';

	// e_admin.php addons may add tabs (core merges $config['tabs'] into this
	// array), so every editable field needs an explicit 'tab'.
	protected $tabs				= array('race' => LAN_ADMIN_RACE_TAB_TRACK);

	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.

	protected $listOrder		= 'race_id DESC';

	protected $fields = array(
		'checkboxes' => array(
			'title' => '',
			'type' => null,
			'data' => null,
			'width' => '5%',
			'thclass' => 'center',
			'forced' => 'value',
			'class' => 'center',
			'toggle' => 'e-multiselect',
		),
		'race_id' => array(
			'title' => LAN_ID,
			'type' => 'number',
			'data' => 'int',
			'tab' => 'race',
		),
		'race_name'  => array(
			'title' => LAN_TITLE,
			'type' => 'text',
			'data' => 'safestr',
			'tab' => 'race',
			'writeParms' => 'size=xxlarge',
		),

		'race_sef'               => array(
			'title' => LAN_ADMIN_RACE_001,
			'type' => 'text',
			'data' => 'safestr',
			'tab' => 'race',
			'validate' => true,
			'help' => LAN_ADMIN_RACE_001_HELP,
			'writeParms' => ['show' => 1, 'sef' => 'race_name', 'size' => 'xxlarge'],
		),

		'race_code'               => array(
			'title' => LAN_ADMIN_RACE_002,
			'type' => 'text',
			'data' => 'safestr',
			'tab' => 'race',
			'validate' => true,
			'help' => LAN_ADMIN_RACE_002_HELP,
			'writeParms' => ['show' => 1, 'sef' => 'race_name', 'size' => 'xxlarge'],

		),

		// The registration fields are added by addRegistrationFields().

		'options' => array(
			'title' => LAN_OPTIONS,
			'type' => 'method',
			'data' => null,
			'width' => '10%',
			'thclass' => 'center last',
			'class' => 'center last',
			'forced' => 'value',
		),
	);


	protected $fieldpref = array('race_name');


	//	protected $preftabs        = array('General', 'Other' );
	protected $prefs = array();


	public function init()
	{
		// Archive row button (token-protected POST from racetrack_form_ui::options).
		if (!empty($_POST['racetrack_archivovat']))
		{
			racetrack_archive_trigger((int) varset($_POST['racetrack_archive_race'], 0));
		}
 
		// Runtime guard, not a dependency: racereg depends on racetrack, never
		// the other way round.
		if (e107::isInstalled('racereg'))
		{
			$this->addRegistrationFields();
		}
	}


	/**
	 * Add the Registration tab and its fields. The columns always exist on the
	 * `race` table; only their visibility in the form is gated on racereg.
	 */
	protected function addRegistrationFields()
	{
		$this->tabs['registracia'] = LAN_ADMIN_RACE_TAB_REG;

		$this->fields['race_capacity'] = array(
			'title'      => LAN_ADMIN_RACE_CAPACITY,
			'type'       => 'number',
			'data'       => 'int',
			'tab'        => 'registracia',
			'inline'     => true,
			'help'       => LAN_ADMIN_RACE_CAPACITY_HELP,
			'writeParms' => array('size' => 'small', 'min' => 0),
		);
		$this->fields['race_unlimited_capacity'] = array(
			'title'  => LAN_ADMIN_RACE_UNLIMITED,
			'type'   => 'boolean',
			'data'   => 'int',
			'tab'    => 'registracia',
			'inline' => true,
			'help'   => LAN_ADMIN_RACE_UNLIMITED_HELP,
		);
		$this->fields['race_requires_approval'] = array(
			'title'  => LAN_ADMIN_RACE_APPROVAL,
			'type'   => 'boolean',
			'data'   => 'int',
			'tab'    => 'registracia',
			'inline' => true,
			'help'   => LAN_ADMIN_RACE_APPROVAL_HELP,
		);
		$this->fields['race_registration_closed'] = array(
			'title'  => LAN_ADMIN_RACE_CLOSED,
			'type'   => 'boolean',
			'data'   => 'int',
			'tab'    => 'registracia',
			'inline' => true,
			'help'   => LAN_ADMIN_RACE_CLOSED_HELP,
		);
	}


	/**
	 * Save-time warnings (docs/plugins/racetrack.md, "Warnings on save"). Never
	 * returns false, so the save always proceeds.
	 *
	 * @param array $data  posted track row (e_admin_ui field keys, already toDB'd)
	 * @param int   $raceId existing race_id on update, 0 on create
	 */
	protected function warnOpenTrackConfig($data, $raceId)
	{
		if (!e107::isInstalled('racereg'))
		{
			return;
		}

		if (!empty($data['race_registration_closed']))
		{
			return;
		}

		$unlimited = !empty($data['race_unlimited_capacity']);
		$capacity  = (int) varset($data['race_capacity'], 0);

		if (!$unlimited && $capacity <= 0)
		{
			e107::getMessage()->addWarning(LAN_ADMIN_RACE_CAP_WARN);
		}

		// race_price.race_price_race = race_id. On create there is no id yet, so
		// no tier. The id is int-cast before it goes into the WHERE.
		$raceId = (int) $raceId;
		$tiers  = ($raceId > 0)
			? (int) e107::getDb()->count('race_price', '(*)', 'race_price_race=' . $raceId)
			: 0;

		if ($tiers <= 0)
		{
			e107::getMessage()->addWarning(LAN_ADMIN_RACE_FREE_WARN);
		}
	}


	/**
	 * Soft-warn on create. Returns nothing so the save proceeds normally.
	 */
	public function beforeCreate($new_data, $old_data)
	{
		$this->warnOpenTrackConfig($new_data, 0);
	}


	/**
	 * Soft-warn on update. Returns nothing so the save proceeds normally.
	 */
	public function beforeUpdate($new_data, $old_data, $id)
	{
		$this->warnOpenTrackConfig($new_data, $id);
	}


	// Left-panel help.
	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text    = LAN_ADMIN_RACE_TRACK_HELP;

		return array('caption' => $caption, 'text' => $text);
	}
}



class racetrack_form_ui extends e_admin_form_ui
{

	function options($att, $value, $id, $attributes)
	{
		if ($attributes['mode'] == 'read')
		{

			$data = $this->getController()->getListModel()->getData();

			// Start list of this track (racers 'startlist' route). Note:
			// racers/startlist.php treats only 'all' as "every category"; with
			// 'komplet' it matches no category and lists nobody.
			$url = e107::url('racers', 'startlist', array('race_sef' => $data['race_sef'], 'race_category_sef' => 'komplet'), ['mode' => 'full']);
 
			// SUT results of this track (racereports 'stu' route).
			$url1 = e107::url('racereports', 'stu', $data, ['mode' => 'full']);


			$text = "<div class='btn-group'>";

			$text .= "<a target='_blank' title='Detail' class='btn btn-default' href='" . $url . "'>" . ADMIN_VIEW_ICON . "</a>";

			$text .= "<a target='_blank' title='SUT' class='btn btn-default' href='" . $url1 . "'>" . ADMIN_EXECUTE_ICON. "</a>";

			// Archive - token-protected POST to the list page, handled in
			// racetrack_ui::init().
			$tp     = e107::getParser();
			$raceId = (int) $data['race_id'];
			$text .= "<form method='post' action='" . e_SELF . "?mode=main&action=list' style='display:inline'>";
			$text .= $this->token();
			$text .= "<input type='hidden' name='racetrack_archive_race' value='" . $raceId . "' />";
			$text .= "<button type='submit' name='racetrack_archivovat' value='1' class='btn btn-default'>"
				. $tp->toHTML(LAN_ADMIN_ARCHIVE_ARCHIVOVAT, false) . "</button>";
			$text .= "</form>";

			$text .= $this->renderValue('options', $value, array('readParms' => 'edit=1'), $id);
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
