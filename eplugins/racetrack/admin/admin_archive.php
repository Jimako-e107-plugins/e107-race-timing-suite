<?php
/*
 * e107 website system
 *
 * racetrack plugin - ARCHIVE administration (race_archive).
 *
 * Self-contained admin entry file for the `archive` mode (archive CRUD), split
 * out of admin/admin_config.php. Mirrors the sibling area entry files
 * (admin_points.php / admin_prices.php): bootstrap, require the shared dispatcher
 * (admin_menu.php, which loads class2 + the admin LAN), gate on the plugin's OWN
 * admin permission, define ONLY this area's controller + form classes, then run
 * the page. The dispatcher's `archive` mode points here via its $adminMenu 'url'.
 *
 * ONE output = ONE admin file, mirroring racereports' per-report admin files
 * (admin_aktualne.php, admin_finish.php, ...) and racers' admin_startlists.php /
 * admin_racerlist.php. The archive classes moved here verbatim from
 * admin_config.php - no behavioural change.
 *
 * The shared archive generate routine + its e_token/message wrapper
 * (racetrack_archive_generate / racetrack_archive_trigger) live in
 * racetrack/includes/race_archive_generate.php - the single home both the
 * "Pregenerovat" button here AND race_ui's "Archivovat" row button call.
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// Shared archive generate routine + trigger wrapper - used by BOTH this screen's
// "Pregenerovat" button and race_ui's "Archivovat" row button (admin_config.php).
require_once(e_PLUGIN . 'racetrack/includes/race_archive_generate.php');


/**
 * ARCHIVE admin (race_archive). Standard e_admin_ui mirroring the legacy
 * timetracker admin_archive.php, but now owned by racetrack (the table lives in
 * racetrack_sql.php). List: ID | Track | Title | Sef | Options; perPage 10;
 * order race_archive_id DESC.
 *
 * Track (race_archive_race) is an inline-editable DROPDOWN = all #race rows
 * (race_id => race_name) PLUS 0 => "Unlinked archive". Setting 0 IS the unlink
 * control. Desc/Data/HTML are frozen payloads (textarea/bbarea); Created/Updated
 * are noedit datestamps. Manual create is allowed; once linked (race > 0) the
 * "Pregenerovat" option appears.
 */
class race_archive_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_ADMIN_ARCHIVE;
	protected $pluginName  = 'racetrack';
	protected $table       = 'race_archive';
	protected $pid         = 'race_archive_id';
	protected $perPage     = 10;
	protected $batchDelete = true;
	protected $batchExport = true;
	protected $batchCopy   = true;

	protected $listOrder   = 'race_archive_id DESC';

	protected $fields = array(
		'checkboxes' => array(
			'title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center',
			'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect',
		),
		'race_archive_id' => array(
			'title' => LAN_ID, 'type' => 'number', 'data' => 'int', 'width' => '5%',
			'thclass' => 'left', 'class' => 'left',
		),
		'race_archive_race' => array(
			'title' => LAN_ADMIN_ARCHIVE_TRACK,
			'type' => 'dropdown',
			'data' => 'safestr',
			'batch' => true,
			'inline' => true,
			'filter' => true,
			'width' => 'auto',
			'thclass' => 'left',
			'class' => 'left',
		),
		'race_archive_name' => array(
			'title' => LAN_TITLE,
			'type' => 'text',
			'data' => 'safestr',
			'inline' => true,
			'validate' => true,
			'width' => 'auto',
			'thclass' => 'left',
			'class' => 'left',
			'writeParms' => array('size' => 'block-level'),
		),
		'race_archive_sef' => array(
			'title' => 'Sef',
			'type' => 'text',
			'data' => 'safestr',
			'validate' => true,
			'width' => 'auto',
			'thclass' => 'left',
			'class' => 'left',
			// Auto-sef from the name on create (same idiom as race_sef).
			'writeParms' => array('show' => 1, 'sef' => 'race_archive_name'),
		),
		'race_archive_desc' => array(
			'title' => 'Desc', 'type' => 'bbarea', 'data' => 'str', 'width' => 'auto',
			'thclass' => 'left', 'class' => 'left', 'filter' => false, 'batch' => false,
		),
		'race_archive_data' => array(
			'title' => 'Data', 'type' => 'textarea', 'data' => 'str', 'width' => 'auto',
			'thclass' => 'left', 'class' => 'left', 'filter' => false, 'batch' => false,
		),
		'race_archive_html' => array(
			'title' => 'HTML', 'type' => 'textarea', 'data' => 'str', 'width' => 'auto',
			'thclass' => 'left', 'class' => 'left', 'filter' => false, 'batch' => false,
		),
		'race_archive_created' => array(
			'title' => LAN_ADMIN_ARCHIVE_CREATED, 'type' => 'datestamp', 'data' => 'int',
			'noedit' => 1, 'width' => 'auto', 'thclass' => 'left', 'class' => 'left',
		),
		'race_archive_updated' => array(
			'title' => LAN_ADMIN_ARCHIVE_UPDATED, 'type' => 'datestamp', 'data' => 'int',
			'noedit' => 1, 'width' => 'auto', 'thclass' => 'left', 'class' => 'left',
		),
		'options' => array(
			'title' => LAN_OPTIONS, 'type' => 'method', 'data' => null, 'width' => '10%',
			'thclass' => 'center last', 'class' => 'center last', 'forced' => 'value',
		),
	);

	protected $fieldpref = array('race_archive_id', 'race_archive_race', 'race_archive_name', 'race_archive_sef');

	public function init()
	{
		// "Pregenerovat" archive-row button (token-protected POST). Same shared
		// generate as "Archivovat"; the posted race id comes from this row's
		// race_archive_race (must be linked, > 0).
		if (!empty($_POST['racetrack_pregenerovat']))
		{
			racetrack_archive_trigger((int) varset($_POST['racetrack_archive_race'], 0));
		}

		// Track dropdown: every race PLUS 0 => Unlinked. Setting 0 unlinks the row.
		$tmp  = array();
		$rows = e107::getDb()->retrieve('race', 'race_id, race_name', '', true);
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$tmp[(int) $row['race_id']] = $row['race_name'];
			}
		}
		$tmp[0] = LAN_ADMIN_ARCHIVE_UNLINKED;
		asort($tmp);

		$this->fields['race_archive_race']['writeParms']['optArray'] = $tmp;
		$this->fields['race_archive_race']['readParms']['optArray']  = $tmp;
		$this->fields['race_archive_race']['writeParms']['multiple'] = 0;
	}

	/**
	 * Manual create stamps both timestamps (the payload may be hand-entered).
	 */
	public function beforeCreate($new_data, $old_data)
	{
		$new_data['race_archive_created'] = time();
		$new_data['race_archive_updated'] = time();
		return $new_data;
	}

	/**
	 * Inline/edit save bumps updated (NOT created - that is the original capture).
	 */
	public function beforeUpdate($new_data, $old_data, $id)
	{
		$new_data['race_archive_updated'] = time();
		return $new_data;
	}

	public function renderHelp()
	{
		return array('caption' => LAN_HELP, 'text' => LAN_ADMIN_ARCHIVE_NOTE);
	}
}


class race_archive_form_ui extends e_admin_form_ui
{
	function options($att, $value, $id, $attributes)
	{
		if ($attributes['mode'] !== 'read')
		{
			return '';
		}

		$tp   = e107::getParser();
		$data = $this->getController()->getListModel()->getData();
		$race = (int) varset($data['race_archive_race'], 0);
		$sef  = (string) varset($data['race_archive_sef'], '');

		$text = "<div class='btn-group'>";

		// "Zobrazit" (Quick View) - ALWAYS available. PURE view of the FRONTEND
		// snapshot; opens in a new window and NEVER regenerates (the fix for the
		// legacy Quick-View bug that secretly ran &generuj=1).
		$viewUrl = e107::url('racetrack', 'archiv', array('race_archive_sef' => $sef), array('mode' => 'full'));
		$text .= "<a target='_blank' class='btn btn-default' title='" . $tp->toAttribute(LAN_ADMIN_ARCHIVE_VIEW) . "' href='"
			. $viewUrl . "'>" . $tp->toHTML(LAN_ADMIN_ARCHIVE_VIEW, false) . "</a>";

		// "Pregenerovat" - ONLY when linked (race > 0). Token-protected POST that
		// runs the shared generate for this row's linked race, overwriting it.
		// Hidden for unlinked rows (no race to generate from).
		if ($race > 0)
		{
			$text .= "<form method='post' action='" . e_SELF . "?mode=archive&action=list' style='display:inline'>";
			$text .= $this->token();
			$text .= "<input type='hidden' name='racetrack_archive_race' value='" . $race . "' />";
			$text .= "<button type='submit' name='racetrack_pregenerovat' value='1' class='btn btn-default' title='"
				. $tp->toAttribute(LAN_ADMIN_ARCHIVE_REGENERATE) . "'>"
				. $tp->toHTML(LAN_ADMIN_ARCHIVE_REGENERATE, false) . "</button>";
			$text .= "</form>";
		}

		$text .= $this->renderValue('options', $value, array('readParms' => 'edit=1'), $id);
		$text .= "</div>";

		return $text;
	}
}


new racetrack_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
