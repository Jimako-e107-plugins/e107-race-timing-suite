<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - admin/admin_config.php
 *
*/

/*
 * Entry point for the racer (main) and category (cat) CRUD and the plugin
 * Settings (fields: docs/plugins/racers.md, "Settings", "Categories",
 * "List / Add racer"). The other admin screens are their own entry files.
 * racer_result_ui below is not wired into any mode (no racer_result table).
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu
require_once(e_PLUGIN . 'racers/includes/racers_name.php'); // flag map default + validation

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class race_category_ui extends e_admin_ui
{

	protected $pluginTitle		= 'Racers';
	protected $pluginName		= 'racers';
	protected $table			= 'race_category';
	protected $pid				= 'race_category_id';
	protected $perPage			= 30;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

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
			'help' => '',
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
	protected $preftabs        = array('Nastavenie',  'Tlač', LAN_RACERS_ADMIN_053);

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
		// Country flag next to the racer name (rendered by racers_name::build()) -
		// own settings tab (index 2).
		'display_flag'        => array(
			'title' => LAN_RACERS_ADMIN_044,
			'tab' => 2,
			'type' => 'boolean',
			'data' => 'int',
			'help' => LAN_RACERS_ADMIN_044_HELP,
		),
		'flag_position'        => array(
			'title' => LAN_RACERS_ADMIN_045,
			'tab' => 2,
			'type' => 'dropdown',
			'data' => 'str',
			'help' => LAN_RACERS_ADMIN_045_HELP,
			// 'after' first: an unset pref (existing installs) shows the default.
			'writeParms' => ['optArray' => ['after' => LAN_RACERS_ADMIN_047, 'before' => LAN_RACERS_ADMIN_046]]
		),
		'flag_size'        => array(
			'title' => LAN_RACERS_ADMIN_048,
			'tab' => 2,
			'type' => 'number',
			'data' => 'int',
			'help' => LAN_RACERS_ADMIN_048_HELP,
			'writeParms' => ['default' => racers_name::FLAG_SIZE_DEFAULT, 'min' => racers_name::FLAG_SIZE_MIN, 'max' => racers_name::FLAG_SIZE_MAX, 'post' => ' px']
		),
		'flag_map'        => array(
			'title' => LAN_RACERS_ADMIN_049,
			'tab' => 2,
			'type' => 'method',
			'data' => 'array',
			'help' => LAN_RACERS_ADMIN_049_HELP,
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

		return array('caption' => $caption, 'text' => $text);
	}

 

	public function beforeUpdate($new_data, $old_data, $id)
	{

		return $new_data;
	}

	/**
	 * Normalise the country-flag prefs before e_admin_ui saves them (rules:
	 * docs/plugins/racers.md, "Settings"). e_token CSRF is handled by e_admin_ui;
	 * the values come from its posted data, not from $_POST.
	 *
	 * - flag_map: posted as rows flag_map[n][ioc] / flag_map[n][iso]; stored as
	 *   IOC => ISO sorted by key (an all-empty map is stored empty).
	 * - flag_position: anything other than 'before' becomes 'after'.
	 *
	 * @param array $new_data posted prefs
	 * @param array $old_data stored prefs
	 * @return array
	 */
	public function beforePrefsSave($new_data, $old_data)
	{
		if (isset($new_data['flag_map']))
		{
			$map     = array();
			$invalid = array();
			$rows    = is_array($new_data['flag_map']) ? $new_data['flag_map'] : array();

			foreach ($rows as $row)
			{
				if (!is_array($row))
				{
					continue;
				}

				$ioc = isset($row['ioc']) && is_scalar($row['ioc']) ? strtoupper(trim((string) $row['ioc'])) : '';
				$iso = isset($row['iso']) && is_scalar($row['iso']) ? strtoupper(trim((string) $row['iso'])) : '';

				if ($ioc === '' && $iso === '')
				{
					continue; // empty row - ignored silently
				}

				if (preg_match('/^[A-Z]{3}$/', $ioc) && preg_match('/^[A-Z]{2}$/', $iso))
				{
					$map[$ioc] = $iso;
				}
				else
				{
					$invalid[] = $ioc . '=' . $iso;
				}
			}

			ksort($map);
			$new_data['flag_map'] = $map;

			if (!empty($invalid))
			{
				// The dropped values are admin input: escape them before echoing.
				$list = htmlspecialchars(implode(', ', $invalid), ENT_QUOTES, 'UTF-8');
				e107::getMessage()->addWarning(str_replace('[x]', $list, LAN_RACERS_ADMIN_050));
			}
		}

		if (isset($new_data['flag_size']))
		{
			$new_data['flag_size'] = racers_name::clampFlagSize($new_data['flag_size']);
		}

		if (isset($new_data['flag_position']) && $new_data['flag_position'] !== 'before')
		{
			$new_data['flag_position'] = 'after';
		}

		return $new_data;
	}
}



class racer_form_ui extends e_admin_form_ui
{
	/**
	 * Reference list shown under the flag map field: IOC code => ISO 3166-1 alpha-2
	 * for the countries most likely to appear at a race. Display-only help - it is
	 * never saved; the admin copies the codes they need into the map.
	 */
	const IOC_ISO_REFERENCE = array(
		'ALB' => 'AL', 'AND' => 'AD', 'ARG' => 'AR', 'ARM' => 'AM', 'AUS' => 'AU',
		'AUT' => 'AT', 'AZE' => 'AZ', 'BEL' => 'BE', 'BIH' => 'BA', 'BLR' => 'BY',
		'BRA' => 'BR', 'BUL' => 'BG', 'CAN' => 'CA', 'CHI' => 'CL', 'CHN' => 'CN',
		'COL' => 'CO', 'CRO' => 'HR', 'CYP' => 'CY', 'CZE' => 'CZ', 'DEN' => 'DK',
		'ESP' => 'ES', 'EST' => 'EE', 'ETH' => 'ET', 'FIN' => 'FI', 'FRA' => 'FR',
		'GBR' => 'GB', 'GEO' => 'GE', 'GER' => 'DE', 'GRE' => 'GR', 'HUN' => 'HU',
		'IND' => 'IN', 'IRL' => 'IE', 'ISL' => 'IS', 'ISR' => 'IL', 'ITA' => 'IT',
		'JPN' => 'JP', 'KAZ' => 'KZ', 'KEN' => 'KE', 'KOR' => 'KR', 'LAT' => 'LV',
		'LIE' => 'LI', 'LTU' => 'LT', 'LUX' => 'LU', 'MDA' => 'MD', 'MEX' => 'MX',
		'MKD' => 'MK', 'MLT' => 'MT', 'MNE' => 'ME', 'MON' => 'MC', 'NED' => 'NL',
		'NOR' => 'NO', 'NZL' => 'NZ', 'POL' => 'PL', 'POR' => 'PT', 'ROU' => 'RO',
		'RSA' => 'ZA', 'RUS' => 'RU', 'SLO' => 'SI', 'SMR' => 'SM', 'SRB' => 'RS',
		'SUI' => 'CH', 'SVK' => 'SK', 'SWE' => 'SE', 'TUR' => 'TR', 'UKR' => 'UA',
		'USA' => 'US',
	);

	/**
	 * Settings 'method' field for the flag_map pref (IOC code -> ISO 3166-1 alpha-2).
	 * When the pref is not set at all (existing installs) the default map is shown,
	 * so a save keeps it. Values are escaped by e_form::text(). Normalised in
	 * racer_ui::beforePrefsSave().
	 *
	 * @param mixed  $curVal stored pref value
	 * @param string $mode   'write' on the settings page
	 * @param array  $parms
	 * @return string
	 */
	public function flag_map($curVal, $mode, $parms = array())
	{
		if ($mode !== 'write')
		{
			return '';
		}

		$map = ($curVal === null) ? racers_name::DEFAULT_FLAG_MAP : racers_name::cleanFlagMap($curVal);

		$rows = array();
		foreach ($map as $ioc => $iso)
		{
			$rows[] = array($ioc, $iso);
		}
		for ($i = 0; $i < 3; $i++)
		{
			$rows[] = array('', '');
		}

		$text = "<table class='table table-condensed table-bordered' style='width:auto'>
			<thead><tr><th>" . LAN_RACERS_ADMIN_051 . "</th><th>" . LAN_RACERS_ADMIN_052 . "</th></tr></thead><tbody>";

		foreach ($rows as $n => $row)
		{
			$text .= "<tr><td>" . $this->text('flag_map[' . $n . '][ioc]', $row[0], 3, array('size' => 'mini'))
				. "</td><td>" . $this->text('flag_map[' . $n . '][iso]', $row[1], 2, array('size' => 'mini'))
				. "</td></tr>";
		}

		$text .= "</tbody></table>";

		$text .= $this->flagMapHelp($map);

		return $text;
	}

	/**
	 * Help under the flag map field: the 'states' codes with no flag yet, and the
	 * IOC_ISO_REFERENCE table with country names from e_form::getCountry().
	 * All values are A-Z codes or core country names, escaped via toHTML anyway.
	 *
	 * @param array $map current IOC => ISO map shown in the field
	 * @return string
	 */
	protected function flagMapHelp($map)
	{
		$tp = e107::getParser();

		$text = '';

		$states  = (string) e107::pref('racers', 'states');
		$missing = array();
		foreach (explode(',', $states) as $code)
		{
			$code = strtoupper(trim($code));
			if ($code !== '' && !isset($map[$code]))
			{
				$missing[] = $code;
			}
		}

		if (!empty($missing))
		{
			$text .= "<div class='alert alert-warning'>"
				. str_replace('[x]', $tp->toHTML(implode(', ', $missing), false, 'TITLE'), LAN_RACERS_ADMIN_056)
				. "</div>";
		}

		$text .= "<details><summary>" . LAN_RACERS_ADMIN_054 . "</summary>"
			. "<table class='table table-condensed table-striped' style='width:auto'>"
			. "<thead><tr><th>" . LAN_RACERS_ADMIN_051 . "</th><th>" . LAN_RACERS_ADMIN_052 . "</th><th>"
			. LAN_RACERS_ADMIN_055 . "</th></tr></thead><tbody>";

		foreach (self::IOC_ISO_REFERENCE as $ioc => $iso)
		{
			$text .= "<tr><td><code>" . $ioc . "</code></td><td><code>" . $iso . "</code></td><td>"
				. $tp->toHTML($this->getCountry(strtolower($iso)), false, 'TITLE') . "</td></tr>";
		}

		$text .= "</tbody></table></details>";

		return $text;
	}
}



class racer_result_ui extends e_admin_ui
{

	protected $pluginTitle		= 'racers';
	protected $pluginName		= 'racers';
	protected $table			= 'racer_result';
	protected $pid				= 'racer_result_id';
	protected $perPage			= 10;
	protected $batchDelete		= true;
	protected $batchExport     = true;
	protected $batchCopy		= true;

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
		if (!e107::isInstalled('racers'))
		{
			e107::getMessage()->addWarning("This plugin is not yet installed. Saving and loading of preference or table data will fail.");
		}

		$this->fields['racer_result_racer']['writeParms']['optArray'] = array('racer_result_racer_0', 'racer_result_racer_1', 'racer_result_racer_2');

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
 
}



class racer_result_form_ui extends e_admin_form_ui
{
}


new racers_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
