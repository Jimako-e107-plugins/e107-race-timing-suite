<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - admin/admin_archive.php
 *
*/

/*
 * Archive screen (fields, buttons, linked vs unlinked:
 * docs/plugins/racetrack.md, "Archive"). The generate routine shared with the
 * Track list's Archive button is in includes/race_archive_generate.php.
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// racetrack_archive_trigger() for the Regenerate row button.
require_once(e_PLUGIN . 'racetrack/includes/race_archive_generate.php');


/**
 * Archive list and form. race_archive_race = 0 means "Unlinked archive".
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
			// Auto-filled from the name.
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
			'title' => 'HTML', 'type' => 'textarea', 'data' => false, 'width' => 'auto',
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
		// Regenerate row button (token-protected POST); the posted id is this
		// row's race_archive_race.
		if (!empty($_POST['racetrack_pregenerovat']))
		{
			racetrack_archive_trigger((int) varset($_POST['racetrack_archive_race'], 0));
		}

		// Track dropdown: every track plus 0 => Unlinked archive.
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

		// View: the public archive page, new tab. Never regenerates.
		$viewUrl = e107::url('racetrack', 'archiv', array('race_archive_sef' => $sef), array('mode' => 'full'));
		$text .= "<a target='_blank' class='btn btn-default' title='" . $tp->toAttribute(LAN_ADMIN_ARCHIVE_VIEW) . "' href='"
			. $viewUrl . "'>" . $tp->toHTML(LAN_ADMIN_ARCHIVE_VIEW, false) . "</a>";

		// Regenerate - only for a linked row. Handled in race_archive_ui::init().
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
