<?php

// Generated e107 Plugin Admin Area
//
// racetrack admin ENTRY POINT and the `main` mode (tracks / race CRUD). The
// checkpoint (`control`) and price-tier (`prices`) areas live in their own entry
// files (admin_points.php / admin_prices.php), each reached via its $adminMenu
// 'url' - mirroring racetiming's one-file-per-area layout. The shared dispatcher
// (admin_menu.php) loads the LAN and is required by every area entry file.

require_once('../../../class2.php');

require_once('admin_menu.php'); // shared dispatcher / menu + LAN (racetrack_adminArea)

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

// Shared archive generate routine + its e_token/message trigger wrapper
// (racetrack_archive_generate / racetrack_archive_trigger). race_ui's "Archivovat"
// race-row button below needs racetrack_archive_trigger(), so the helper is loaded
// here. The archive admin itself lives in its OWN entry file (admin_archive.php),
// reached via the 'archive' $adminMenu 'url' - admin_config.php no longer serves it.
require_once(e_PLUGIN . 'racetrack/includes/race_archive_generate.php');


class racetrack_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_GLOBAL_RACE_001;
	protected $pluginName		= 'racetrack';
	// Event name MUST be set so e_admin.php addons can target this UI via
	// $ui->getEventName() (issue #34). Optional plugins hook the track edit
	// form through this name without race referencing them.
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

	// Track edit form tabs (issue #34). Optional plugins may add more tabs via
	// an e_admin.php addon (core merges $config['tabs'] into this array). Every
	// editable field below carries 'tab' => 'race' so nothing renders into a
	// broken/empty layout once tabs are active.
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

		// Registration settings (race_capacity, race_unlimited_capacity,
		// race_requires_approval, race_registration_closed) are NOT defined here.
		// They are merged in init() with 'tab' => 'registracia' only when racereg
		// is installed (opt-in Registration tab) - see addRegistrationFields().

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
		// "Archivovat" race-row button (token-protected POST from
		// racetrack_form_ui::options). This is the ONLY place to create an archive
		// for a race that has none yet; on an already-archived race it overwrites
		// (count() in the shared generate decides) - identical to "Pregenerovat".
		if (!empty($_POST['racetrack_archivovat']))
		{
			racetrack_archive_trigger((int) varset($_POST['racetrack_archive_race'], 0));
		}
 
		// Opt-in Registration tab: the four registration-config fields exist only
		// when racereg (their sole consumer) is installed. This is a runtime guard,
		// NOT a plugin dependency - racetrack never depends on racereg (the correct
		// direction is racereg -> racetrack). When racereg is absent the track form
		// stays registration-agnostic (single Track tab, no fields, no warnings).
		if (e107::isInstalled('racereg'))
		{
			$this->addRegistrationFields();
		}
	}


	/**
	 * Merge the registration-config fields onto a dedicated "Registration" tab.
	 * Called from init() ONLY when racereg is installed (opt-in). The columns
	 * still live on the `race` table; only their admin-form visibility is gated.
	 *
	 * Field definitions are verbatim from the pre-split inline $fields array, with
	 * 'tab' => 'registracia' so they render on the new tab.
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
	 * Soft, non-blocking warnings when saving a track (issue #47).
	 *
	 * The save is always allowed to proceed (we never return false here); these
	 * are heads-up warnings for a track that is OPEN for registration
	 * (race_registration_closed = 0) but configured so that the registration flow
	 * cannot behave as the organizer probably expects:
	 *   - finite capacity of 0 (and not unlimited) -> nobody can be placed;
	 *   - no price tier -> the sign-up is treated as free ("bez poplatku").
	 *
	 * @param array $data  posted track row (e_admin_ui field keys, already toDB'd)
	 * @param int   $raceId existing race_id on update, 0 on create
	 */
	protected function warnOpenTrackConfig($data, $raceId)
	{
		// Registration heads-up warnings only apply when racereg is installed -
		// the registration fields exist on the track form solely in that case.
		// Without racereg the track form is registration-agnostic (no warnings).
		if (!e107::isInstalled('racereg'))
		{
			return;
		}

		// Only relevant for tracks that are open for sign-up.
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

		// Price tiers live in the child table `race_price` keyed by race_price_race
		// = race_id. On create there is no id yet, so there cannot be any tier.
		// Parameterised int only - no string-built SQL.
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


	// left-panel help menu area. (replaces e_help.php used in old plugins)
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

			// Both buttons are links into the subordinate plugins - racetrack only
			// manages tracks and renders no racer/time data of its own.
			// Detail: the per-track public start list (štartovacia listina) owned
			// by the racers plugin (race_category_sef = all = every category).
			$url = e107::url('racers', 'startlist', array('race_sef' => $data['race_sef'], 'race_category_sef' => 'komplet'), ['mode' => 'full']);
 
			// STU: canonical timing report owned by timetracker (link, not embed).
			$url1 = e107::url('racereports', 'stu', $data, ['mode' => 'full']);


			$text = "<div class='btn-group'>";

			$text .= "<a target='_blank' title='Detail' class='btn btn-default' href='" . $url . "'>" . ADMIN_VIEW_ICON . "</a>";

			$text .= "<a target='_blank' title='SUT' class='btn btn-default' href='" . $url1 . "'>" . ADMIN_EXECUTE_ICON. "</a>";

			// "Archivovat" - token-protected POST to the list page; racetrack_ui::init
			// runs the shared generate for this race (insert or overwrite). This is
			// the only entry that can create an archive for a race that has none.
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
