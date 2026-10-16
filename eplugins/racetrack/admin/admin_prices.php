<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - admin/admin_prices.php
 *
*/

/*
 * Price tiers screen (fields and the tier rule: docs/plugins/racetrack.md,
 * "Price tiers").
 */

require_once("../../../class2.php");

require_once("admin_menu.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

/**
 * Price tiers. The applicable tier is resolved by racereg
 * (race_registration::resolvePrice()), not here.
 */
class racetrack_price_ui extends e_admin_ui
{
	protected $pluginTitle	= LAN_GLOBAL_RACE_001;
	protected $pluginName	= 'racetrack';
	protected $table		= 'race_price';
	protected $pid			= 'race_price_id';
	protected $perPage		= 30;
	protected $batchDelete	= true;
	protected $batchCopy	= true;

	protected $listOrder	= 'race_price_race ASC, race_price_from ASC';

	protected $fields = array(
		'checkboxes' => array('title' => '', 'type' => null, 'data' => null, 'width' => '5%', 'thclass' => 'center', 'forced' => 'value', 'class' => 'center', 'toggle' => 'e-multiselect'),
		'race_price_id' => array(
			'title' => LAN_ID,
			'type'  => 'number',
			'data'  => 'int',
			'width' => '5%',
		),
		'race_price_race' => array(
			'title'    => LAN_ADMIN_PRICE_TRACK,
			'type'     => 'dropdown',
			'data'     => 'int',
			'width'    => 'auto',
			'filter'   => true,
			'inline'   => true,
			'writeParms' => array(),
			'readParms'  => array(),
		),
		'race_price_value' => array(
			'title'    => LAN_ADMIN_PRICE_VALUE,
			'type'     => 'text',
			'data'     => 'float',
			'width'    => 'auto',
			'inline'   => true,
			'help'     => LAN_ADMIN_PRICE_VALUE_HELP,
			'writeParms' => array('size' => 'small'),
		),
		// Unix timestamp (date + time).
		'race_price_from' => array(
			'title'    => LAN_ADMIN_PRICE_FROM,
			'type'     => 'datestamp',
			'data'     => 'int',
			'width'    => 'auto',
			'help'     => LAN_ADMIN_PRICE_FROM_HELP,
			'writeParms' => array(),
		),
		'options' => array(
			'title'   => LAN_OPTIONS,
			'type'    => null,
			'data'    => null,
			'width'   => '10%',
			'thclass' => 'center last',
			'class'   => 'center last',
			'forced'  => 'value',
		),
	);

	protected $fieldpref = array('race_price_race', 'race_price_value', 'race_price_from');

	protected $prefs = array();

	public function init()
	{
		// Track dropdown.
		$tracks = array();
		$rows   = e107::getDb()->retrieve('race', 'race_id, race_name', '', true);
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$tracks[(int) $row['race_id']] = $row['race_name'];
			}
		}
		$this->fields['race_price_race']['writeParms']['optArray'] = $tracks;
		$this->fields['race_price_race']['readParms']['optArray']  = $tracks;
	}
}


class racetrack_price_form_ui extends e_admin_form_ui
{
}


new racetrack_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
