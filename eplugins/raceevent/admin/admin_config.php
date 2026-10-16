<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - admin/admin_config.php
 *
*/

/*
 * Event configuration screen (fields, save rules and messages:
 * docs/plugins/raceevent.md, "Event configuration"). Native e_admin_ui $prefs
 * form - the core renders it and handles Save, the e_token (CSRF) and storage;
 * other plugins read the values via e107::getPlugConfig('raceevent').
 * getperms('P') resolves to the plugin's own admin permission because this file
 * lives under the plugins folder.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class raceevent_config_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEEVENT_001;
	protected $pluginName  = 'raceevent';

	// Prefs-only screen - no DB table.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'prefs';

	protected $preftabs = array('default'=> 'Default',
		'reg'=> 'Registration'); 

	// Each value is sanitised through its 'data' type (toDB) before storage.
	protected $prefs = array(
		'event_name' => array(
			'title'      => LAN_RACEEVENT_NAME,
			'type'       => 'text', 'tab'=>'default',
			'data'       => 'safestr',
			'validate'   => true,
			'writeParms' => array('size' => 'xxlarge', 'required' => 1),
		),
		// writeParms type=date: date-only datepicker (format from the site
		// 'inputdate' pref).
		'event_date' => array(
			'title'      => LAN_RACEEVENT_DATE,
			'type'       => 'datestamp',
			'tab' => 'default',
			'data'       => 'int',
			'writeParms' => array('type' => 'date'),
		),
		'event_city' => array(
			'title'      => LAN_RACEEVENT_CITY,
			'type'       => 'text',
			'tab' => 'default',
			'data'       => 'str',
			'writeParms' => array('size' => 'xxlarge'),
		),
		'event_location' => array(
			'title'      => LAN_RACEEVENT_LOCATION,
			'type'       => 'text',
			'tab' => 'default',
			'data'       => 'str',
			'writeParms' => array('size' => 'xxlarge'),
		),
		'event_description' => array(
			'title'      => LAN_RACEEVENT_DESCRIPTION,
			'type'       => 'textarea',
			'tab' => 'default',
			'data'       => 'str',
			'writeParms' => array('size' => 'block-level'),
		),
		'event_organizer' => array(
			'title'      => LAN_RACEEVENT_ORGANIZER,
			'type'       => 'textarea',
			'tab' => 'default',
			'data'       => 'str',
			'writeParms' => array('size' => 'block-level'),
		),
		'is_charity' => array(
			'title' => LAN_RACEEVENT_IS_CHARITY,
			'type'  => 'boolean',
			'tab' => 'default',
			'data'  => 'int',
		),
		'is_children_runs_included' => array(
			'title' => LAN_RACEEVENT_IS_CHILDREN_RUNS,
			'type'  => 'boolean',
			'tab' => 'default',
			'data'  => 'int',
		),
		'is_participate_with_dog_allowed' => array(
			'title' => LAN_RACEEVENT_IS_DOG_ALLOWED,
			'type'  => 'boolean',
			'tab' => 'default',
			'data'  => 'int', 
		),
		// --- Registration tab (read by racereg). writeParms type=datetime:
		// date + time picker; 0 = unbounded on that side. ----------------------
		'registrationStartAt' => array(
			'title'      => LAN_RACEEVENT_REG_START,
			'type'       => 'datestamp',
			'tab' => 'reg',
			'data'       => 'int',
			'help'       => LAN_RACEEVENT_REG_START_HELP,
			'writeParms' => array('type' => 'datetime'),
		),
		'registrationEndAt' => array(
			'title'      => LAN_RACEEVENT_REG_END,
			'type'       => 'datestamp',
			'data'       => 'int',
			'tab' => 'reg',
			'help'       => LAN_RACEEVENT_REG_END_HELP,
			'writeParms' => array('type' => 'datetime'),
		),
 
		'payeeIban' => array(
			'title'      => LAN_RACEEVENT_PAYEE_IBAN,
			'type'       => 'text',
			'tab' => 'reg',
			'data'       => 'str',
			'help'       => LAN_RACEEVENT_PAYEE_IBAN_HELP,
			'writeParms' => array('size' => 'xxlarge'),
		),
		'payeeName' => array(
			'title'      => LAN_RACEEVENT_PAYEE_NAME,
			'type'       => 'text',
			'tab' => 'reg',
			'data'       => 'str',
			'help'       => LAN_RACEEVENT_PAYEE_NAME_HELP,
			'writeParms' => array('size' => 'xxlarge'),
		),
		'payeeSwift' => array(
			'title'      => LAN_RACEEVENT_PAYEE_SWIFT,
			'type'       => 'text',
			'tab' => 'reg',
			'data'       => 'str',
			'help'       => LAN_RACEEVENT_PAYEE_SWIFT_HELP,
			'writeParms' => array('size' => 'large'),
		),
	);

	/**
	 * Normalise + validate the payee / registration-window prefs before saving.
	 * The rules and messages are listed in docs/plugins/raceevent.md
	 * ("What the form refuses to save").
	 *
	 * @param array $new_data posted prefs (token already stripped)
	 * @param array $old_data current stored prefs
	 * @return array the full prefs array to save
	 */
	public function beforePrefsSave($new_data, $old_data)
	{
		if (isset($new_data['payeeIban']))
		{
			$iban = strtoupper(preg_replace('/\s+/', '', (string) $new_data['payeeIban']));
			$new_data['payeeIban'] = $iban;

			if ($iban !== '' && !$this->isValidIban($iban))
			{
				e107::getMessage()->addWarning(LAN_RACEEVENT_IBAN_WARN);
				e107::getLog()->add('RACEEVENT_01', 'Payee IBAN saved with a format warning', E_LOG_INFORMATIVE, '');
			}
		}

		if (isset($new_data['payeeSwift']))
		{
			$swift = strtoupper(preg_replace('/\s+/', '', (string) $new_data['payeeSwift']));
			$new_data['payeeSwift'] = $swift;

			// BIC: 8 or 11 chars, AAAA BB CC (DDD). Light check only.
			if ($swift !== '' && !preg_match('/^[A-Z0-9]{8}([A-Z0-9]{3})?$/', $swift))
			{
				e107::getMessage()->addWarning(LAN_RACEEVENT_SWIFT_WARN);
			}
		}

		// --- HARD: IBAN requires a beneficiary name. Compare the values that
		// would actually be stored (posted if present, else the existing pref).
		$ibanToSave = isset($new_data['payeeIban'])
			? (string) $new_data['payeeIban']
			: (string) varset($old_data['payeeIban'], '');
		$nameToSave = isset($new_data['payeeName'])
			? trim((string) $new_data['payeeName'])
			: trim((string) varset($old_data['payeeName'], ''));

		if ($ibanToSave !== '' && $nameToSave === '')
		{
			e107::getMessage()->addError(LAN_RACEEVENT_PAYEE_NAME_REQUIRED);
			e107::getLog()->add('RACEEVENT_02',
				'Rejected raceevent save: payee IBAN set without a beneficiary name', E_LOG_INFORMATIVE, '');

			// Reject: restore the stored payee pair.
			$new_data['payeeIban'] = (string) varset($old_data['payeeIban'], '');
			$new_data['payeeName'] = (string) varset($old_data['payeeName'], '');
		}

		// --- HARD: if both bounds are set, opening must be before closing. ----
		$startToSave = isset($new_data['registrationStartAt'])
			? (int) $new_data['registrationStartAt']
			: (int) varset($old_data['registrationStartAt'], 0);
		$endToSave = isset($new_data['registrationEndAt'])
			? (int) $new_data['registrationEndAt']
			: (int) varset($old_data['registrationEndAt'], 0);

		if ($startToSave > 0 && $endToSave > 0 && $startToSave >= $endToSave)
		{
			e107::getMessage()->addError(LAN_RACEEVENT_REG_WINDOW_INVALID);
			e107::getLog()->add('RACEEVENT_03',
				'Rejected raceevent save: registration window start >= end', E_LOG_INFORMATIVE, '');

			// Reject: restore the stored window.
			$new_data['registrationStartAt'] = (int) varset($old_data['registrationStartAt'], 0);
			$new_data['registrationEndAt']   = (int) varset($old_data['registrationEndAt'], 0);
		}

		// --- SOFT: registration in use but the payee block is incomplete. Warn
		// on the values that will actually be stored (after any revert above).
		$ibanFinal  = (string) varset($new_data['payeeIban'], varset($old_data['payeeIban'], ''));
		$nameFinal  = trim((string) varset($new_data['payeeName'], varset($old_data['payeeName'], '')));
		$startFinal = (int) varset($new_data['registrationStartAt'], varset($old_data['registrationStartAt'], 0));
		$endFinal   = (int) varset($new_data['registrationEndAt'], varset($old_data['registrationEndAt'], 0));

		$registrationInUse = ($startFinal > 0 || $endFinal > 0);

		if ($registrationInUse && ($ibanFinal === '' || $nameFinal === ''))
		{
			e107::getMessage()->addWarning(LAN_RACEEVENT_PAYEE_INCOMPLETE_WARN);
		}

		return $new_data;
	}

	/**
	 * Light IBAN validation: structure + length (SK = 24 chars) + the standard
	 * ISO 7064 mod-97 checksum, computed iteratively (no bcmath dependency).
	 *
	 * @param string $iban already whitespace-stripped + upper-cased
	 * @return bool
	 */
	protected function isValidIban($iban)
	{
		// Country (2 letters) + 2 check digits + 11..30 alphanumerics; max 34.
		if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) || strlen($iban) > 34)
		{
			return false;
		}

		// Slovak IBANs are exactly 24 characters.
		if (strpos($iban, 'SK') === 0 && strlen($iban) !== 24)
		{
			return false;
		}

		// mod-97: move the first four chars to the end, map letters A-Z -> 10-35,
		// then take the remainder mod 97 digit-by-digit (no big-int dependency).
		$rearranged = substr($iban, 4) . substr($iban, 0, 4);
		$remainder  = 0;

		for ($i = 0, $n = strlen($rearranged); $i < $n; $i++)
		{
			$ch    = $rearranged[$i];
			$chunk = ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;

			for ($j = 0, $m = strlen($chunk); $j < $m; $j++)
			{
				$remainder = ($remainder * 10 + (int) $chunk[$j]) % 97;
			}
		}

		return $remainder === 1;
	}

	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text    = LAN_RACEEVENT_CONFIG_HELP;

		return array('caption' => $caption, 'text' => $text);
	}
}


class raceevent_config_form_ui extends e_admin_form_ui
{
}


new raceevent_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
