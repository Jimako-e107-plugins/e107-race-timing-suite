<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetiming plugin - admin/admin_generujstart.php
 *
*/

/*
 * Start time setting (the 'starttime' pref) and Generate Start
 * (docs/plugins/racetiming.md).
 *
 * - 'starttime' is a datetime string with seconds precision, never an
 *   int/datestamp.
 * - The bib (race_time_racer_number) is a string, never cast to int.
 * - The custom form is opened with e107::getForm()->open(), which carries the
 *   e_token (CSRF).
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

class race_generate_ui extends e_admin_ui
{

	protected $pluginTitle		= LAN_GLOBAL_RACETIMING_001;
	protected $pluginName		= 'racetiming';

	// $table is required by the e_admin_ui plumbing; the race_time CRUD itself
	// lives in admin_config.php. $disallow blocks every standard CRUD action; the
	// custom generujStart page is unaffected.
	protected $table			= 'race_time';
	protected $pid				= 'race_time_id';

	protected $disallow = array('list', 'grid', 'create', 'edit', 'delete', 'copy', 'batch', 'inline', 'sort');

	// starttime: plain text field with a datetime string (e.g.
	// "2024-10-08 07:00:00") - never an int/datestamp type.
	protected $preftabs		= array(LAN_ADMIN_RACETIMING_040);
	protected $prefs = array(
		'starttime' => array(
			'title'      => LAN_ADMIN_RACETIMING_041,
			'type'       => 'text',
			'data'       => 'str',
			'help'       => LAN_ADMIN_RACETIMING_042,
			'tab'        => 0,
			'writeParms' => array('size' => 'block-level'),
		),
	);

	public function init()
	{
		$this->prefs['starttime']['writeParms']['default'] = date('Y-m-d H:i:s', time());
	}

	public function generujStartPage()
	{
		$tp = e107::getParser();

		$text = '';

		if (array_key_exists('GenerujStartTrate', $_POST))
		{
			$checkedRaces = $_POST['race_id'] ?? [];

			// Only the ticked tracks (checkbox value 1).
			$selected = array_filter($checkedRaces, static function ($v) {
				return (int) $v === 1;
			});

			if (empty($selected))
			{
				// No track selected: warn and show the form again. render() the
				// message stack explicitly because this page echoes its own markup.
				e107::getMessage()->addWarning(LAN_ADMIN_RACETIMING_053);
				$text .= e107::getMessage()->render();
				$text .= $this->renderTrackSelectionForm();

				echo $text;
				return;
			}

			foreach ($selected as $raceId => $value)
			{
				if ($value == 1)
				{
					$pretek_id = (int) $raceId;

					$query = "
						SELECT * FROM " . MPREFIX . "racer AS rr
						WHERE racer_race_id = {$pretek_id} AND racer_number != '0000'
					";

					$racers = e107::getDb()->retrieve($query, true);

					$text_tmp = '<table class="table adminlist table-striped">';
					$text_tmp .= '<thead><tr><th>Štartovné číslo a meno</th><th>Stav</th></tr></thead>';
					$text_tmp .= '<tbody>';

					foreach ($racers as $racer)
					{
						// Bib stays a string.
						$racer_number = $racer['racer_number'];

						$text_tmp .= '<tr><td>' . $racer['racer_number'] . ' ' . $racer['racer_surname'] . '</td><td>';

						if (!empty($racer['racer_active'])) // inactive = did not start
						{
							// SECURITY: escape the bib before embedding it in the lookup.
							$racer_number_db = $tp->toDB($racer_number);

							$query2 = "SELECT race_time_id FROM " . MPREFIX . "race_time
									   WHERE race_time_point = 'start'
									   AND race_time_racer_number = '{$racer_number_db}'
									   LIMIT 1";

							$exist = e107::getDb()->retrieve($query2);

							if ($exist)
							{
								$text_tmp .= '<span class="bg-danger text-white">' . LAN_ADMIN_RACETIMING_049 . '</span>';
							}
							else
							{
								// The db class escapes the values on insert; the start time is
								// stored as the exact datetime string.
								$insert = [
									'race_time_point'        => 'start',
									'race_time_racer_number' => $racer_number,
									'race_time_time'         => $_POST['time'] ?? '',
									'race_time_ended'        => '',
									'race_time_created'      => time()
								];

								$result = e107::getDb()->insert('race_time', $insert);

								if ($result)
								{
									$text_tmp .= '<span class="bg-success text-white">' . LAN_ADMIN_RACETIMING_050 . '</span>';
								}
								else
								{
									$text_tmp .= '<span class="bg-warning text-dark">Chyba pri generovaní</span>';
								}
							}
						}
						else
						{
							$text_tmp .= '<span class="bg-info text-white">' . LAN_ADMIN_RACETIMING_051 . '</span>';
						}

						$text_tmp .= '</td></tr>';
					}

					$text_tmp .= '</tbody></table>';

					$text .= e107::getRender()->tablerender(
						strtr(LAN_ADMIN_RACETIMING_048, ['{ID}' => $pretek_id]),
						$text_tmp,
						'default',
						true
					);
				}
			}
		}
		else
		{
			$text .= $this->renderTrackSelectionForm();
		}

		echo $text;
	}

	/**
	 * Track selection + start time form. Also shown again after a submit with no
	 * track selected.
	 */
	protected function renderTrackSelectionForm()
	{
		$vf = e107::getForm()->open('generujstart', 'POST');

		$field = [
			'title'     => 'Trať',
			'tab'       => 1,
			'type'      => 'checkboxes',
			'data'      => 'safestr',
			'batch'     => true,
			'inline'    => true,
			'width'     => 'auto',
			'thclass'   => 'left',
			'class'     => 'left',
			'nosort'    => false,
			'filter'    => true
		];

		$preteky = e107::getDb()->retrieve('race', '*', true, true);

		$tmp = [];
		foreach ($preteky as $pretek)
		{
			$tmp[$pretek['race_id']] = $pretek['race_name'];
		}

		$field['writeParms']['optArray'] = $tmp;
		$field['writeParms']['multiple'] = 1;
		$field['writeParms']['inline']   = 1;

		$vf .= e107::getForm()->renderElement('race_id', null, $field);

		// Pre-filled from the 'starttime' pref; the current time if it is unset.
		$time = e107::getPlugConfig('racetiming')->get('starttime', date('Y-m-d H:i:s'));


		$field2 = [
			'title'     => LAN_ADMIN_RACETIMING_045,
			'validate'  => true,
			'type'      => 'text',
			'data'      => 'str',
			'width'     => 'auto',
			'required'  => 1,
			'writeParms'=> ['default' => $time, 'size' => 'large'],
			'class'     => 'left',
			'style'     => 'padding: 10px;' ,
			'thclass'   => 'left',
		];
		$vf .=  "<div style='margin: 10px 0;'>";
		$vf .= e107::getForm()->renderElement('time', null, $field2);
		$vf .=  "<div>";
		$vf .=  "<div style='margin: 10px 0;'>";
		$current_time = date('Y-m-d H:i:s', time());

		$vf .= '
			<input type="button" class="btn btn-primary" value="' . LAN_ADMIN_RACETIMING_046 . '"
				   onclick="document.querySelector(\'input[name=time]\').value = \'' . $current_time . '\';" />
		';

		$vf .= e107::getForm()->submit('GenerujStartTrate', LAN_ADMIN_RACETIMING_047);
		$vf .=  "<div>";
		$vf .= e107::getForm()->close();


		return "<h4>" . LAN_ADMIN_RACETIMING_044 . "</h4>" . $vf;
	}
}

class race_generate_form_ui extends e_admin_form_ui
{
}

new racetiming_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
