<?php
/*
 * e107 website system
 *
 * racetiming plugin - bulk start-generation admin.
 *
 * Relocated verbatim (behaviour-preserving) from timetracker's
 * admin/admin_timetracker.php (mode=tracker action=generujStart) +
 * admin/includes/generujstart.php as part of the strangler decommission of
 * timetracker. racetiming OWNS the race_time table, and start-generation writes
 * `start` passings into race_time, so the feature belongs here.
 *
 * It hosts two things:
 *   1. the `starttime` plugin pref (datetime STRING, seconds precision) - the
 *      default value pre-filled into the generation form. Read/written via
 *      e107::getPlugConfig('racetiming'). NOTE: `generujStart` was never a pref
 *      in timetracker (only a mode/action/button name) - there is nothing to
 *      register for it.
 *   2. the bulk start-generation page (generujStartPage / action=generujStart):
 *      select tracks + a start time, then insert one `start` race_time row per
 *      active racer that does not already have one.
 *
 * SECURITY / conventions (native e107 only):
 *   - Bootstrap via class2.php, gate the whole screen on this plugin's OWN admin
 *     permission with getperms('P').
 *   - e_admin_ui handles CSRF via e_token; the custom generation form is opened
 *     with e107::getForm()->open() (carries the form token). Input is read
 *     through $tp->toDB(); output through $tp->toHTML()/toAttribute(); all
 *     queries go through the db class - the existence lookup is parameterised
 *     via $tp->toDB() (the legacy code concatenated the bib number raw - fixed
 *     here without changing behaviour).
 *   - starttime is a datetime STRING (seconds precision), NEVER an int/datestamp.
 *   - the start/bib number (race_time_racer_number) is a STRING (4-char, leading
 *     zeros), never cast to int.
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

	// $table is required by e_admin_ui plumbing for the prefs page and the
	// custom generation page; no race_time CRUD is exposed from this controller
	// (that lives in admin_config.php). Every standard CRUD action is blocked -
	// the custom generujStart page is unaffected by $disallow.
	protected $table			= 'race_time';
	protected $pid				= 'race_time_id';

	protected $disallow = array('list', 'grid', 'create', 'edit', 'delete', 'copy', 'batch', 'inline', 'sort');

	// Single prefs tab holding the start-generation default time. starttime is a
	// plain text field carrying a datetime STRING (e.g. "2024-10-08 07:00:00",
	// seconds precision) - never an int/datestamp type, which would convert
	// to/from a unix int.
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

		$text = ''; // pre istotu vyčistíme premennú

		if (array_key_exists('GenerujStartTrate', $_POST))
		{
			$checkedRaces = $_POST['race_id'] ?? [];

			// Keep only actually-selected tracks (checkbox value === 1). Submitting
			// the form with no track checked must NOT echo an empty results page.
			$selected = array_filter($checkedRaces, static function ($v) {
				return (int) $v === 1;
			});

			if (empty($selected))
			{
				// No track selected: warn and re-render the selection form so the
				// admin can retry, instead of producing a blank page. render() the
				// message stack explicitly since this page echoes its own markup.
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
						// race_time_racer_number is a STRING (4-char, leading zeros) - never cast to int.
						$racer_number = $racer['racer_number'];

						$text_tmp .= '<tr><td>' . $racer['racer_number'] . ' ' . $racer['racer_surname'] . '</td><td>';

						if (!empty($racer['racer_active'])) // predpokladám, že pole sa volá racer_active
						{
							// SECURITY: the bib number is escaped via $tp->toDB() before being
							// embedded in the existence lookup (legacy code concatenated it raw).
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
								// Values are escaped by the db class on insert (parameterised
								// path) - the start time is kept as the exact datetime STRING.
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
	 * Build the track-selection + start-time form (the generation form).
	 *
	 * Extracted so it can be re-rendered when the form is submitted with no
	 * track selected, in addition to the initial page load. Behaviour-preserving:
	 * the markup is identical to the original else-branch.
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

		// Default start time comes from the racetiming `starttime` pref
		// (datetime STRING). NOT read from timetracker prefs any more.
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
