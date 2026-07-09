<?php
/*
 * e107 website system
 *
 * racereg plugin - Payments admin CRUD (issue #22).
 *
 * Mode-per-file entry script for the 'payments' mode (dispatched from the shared
 * admin/admin_menu.php). Each payment row links to one registration via
 * registration_id (1:N - a registration can hold multiple payment rows). Native
 * e107 e_admin_ui throughout (toDB on save, toHTML on display, parameterized
 * queries from field 'data' types, e_token / CSRF).
 *
 * Out of scope here: the mark-paid convenience action, derived paid-status and
 * approval workflow (issue #5). This screen is plain CRUD so the model is fully
 * testable in admin.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu
require_once(e_PLUGIN . "racereg/includes/racereg_actions.php"); // mark-paid action (#26)

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racereg_payment_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACEREG_PLUGIN;
	protected $pluginName  = 'racereg';

	protected $table = 'racereg_payment';
	protected $pid   = 'payment_id';

	protected $perPage = 20;

	protected $batchDelete = false;
	protected $batchCopy   = false;

	protected $listOrder = 'payment_id DESC';

	protected $fieldpref = array('registration_id', 'amount', 'status', 'paid_at', 'created_at');

	protected $fields = array(
		'checkboxes' => array(
			'title'   => '',
			'type'    => null,
			'data'    => null,
			'width'   => '5%',
			'thclass' => 'center',
			'forced'  => 'value',
			'class'   => 'center',
			'toggle'  => 'e-multiselect',
		),
		'payment_id' => array(
			'title' => LAN_ID,
			'type'  => 'number',
			'data'  => 'int',
			'width' => '5%',
		),
		// Link to the parent registration. Options populated in init(); 'filter'
		// lets the admin list be filtered/grouped by registration.
		'registration_id' => array(
			'title'    => LAN_RACEREG_PAY_REGISTRATION,
			'type'     => 'dropdown',
			'data'     => 'int',
			'width'    => 'auto',
			'filter'   => true,
			'required' => true,
			'writeParms' => array(),
			'readParms'  => array(),
		),
		'amount' => array(
			'title'      => LAN_RACEREG_PAY_AMOUNT,
			'type'       => 'text',
			'data'       => 'float',
			'width'      => 'auto',
			'writeParms' => array('size' => 'small'),
		),
		'status' => array(
			'title'    => LAN_RACEREG_PAY_STATUS,
			'type'     => 'dropdown',
			'data'     => 'int',
			'width'    => 'auto',
			'filter'   => true,
			'inline'   => true,
			'writeParms' => array(),
			'readParms'  => array(),
		),
		'paid_at' => array(
			'title'      => LAN_RACEREG_PAY_PAID_AT,
			'type'       => 'datestamp',
			'data'       => 'int',
			'width'      => 'auto',
			'writeParms' => array(),
		),
		'note' => array(
			'title'      => LAN_RACEREG_PAY_NOTE,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'xxlarge'),
		),
		// Auto-set to now on create; editable date+time otherwise.
		'created_at' => array(
			'title'      => LAN_RACEREG_PAY_CREATED,
			'type'       => 'datestamp',
			'data'       => 'int',
			'width'      => 'auto',
			'writeParms' => array(),
		),
		'options' => array(
			'title'   => LAN_OPTIONS,
			'type'    => 'method',
			'data'    => null,
			'width'   => '12%',
			'thclass' => 'center last',
			'class'   => 'center last',
			'forced'  => 'value',
		),
	);

	protected $prefs = array();

	public function init()
	{
		// Registration options: id => "Last First (VS)". Read via the db class;
		// hide soft-deleted registrations from the picker.
		$regs = array();
		$rows = e107::getDb()->retrieve('racereg_registration',
			'registration_id, first_name, last_name, variable_symbol',
			'deleted_at IS NULL ORDER BY last_name ASC, first_name ASC', true);

		if (is_array($rows))
		{
			$tp = e107::getParser();
			foreach ($rows as $row)
			{
				$label = trim($row['last_name'] . ' ' . $row['first_name']);
				$regs[(int) $row['registration_id']] = $tp->toHTML($label, false) . ' (' . $row['variable_symbol'] . ')';
			}
		}
		$this->fields['registration_id']['writeParms']['optArray'] = $regs;
		$this->fields['registration_id']['readParms']['optArray']  = $regs;

		// Payment status labels (0 pending, 1 valid, 2 erroneous, 3 refunded).
		$status = array(
			0 => LAN_RACEREG_PAYST_0,
			1 => LAN_RACEREG_PAYST_1,
			2 => LAN_RACEREG_PAYST_2,
			3 => LAN_RACEREG_PAYST_3,
		);
		$this->fields['status']['writeParms']['optArray'] = $status;
		$this->fields['status']['readParms']['optArray']  = $status;
	}

	/**
	 * Default created_at to now on create.
	 */
	public function beforeCreate($new_data, $old_data)
	{
		if (empty($new_data['created_at']))
		{
			return array('created_at' => time());
		}

		return array();
	}

	public function renderHelp()
	{
		return array('caption' => LAN_HELP, 'text' => LAN_RACEREG_PAY_HELP);
	}

	/**
	 * Mark a single payment row VALID + stamp paid_at (issue #26). Native
	 * e_admin_ui custom action: a GET row-button with the e107 e-token hits
	 * ?mode=payments&action=markpaid&id=<payment_id>&e-token=..., verified
	 * server-side; the work is delegated to racereg_actions (logged there). Only
	 * the targeted row changes; erroneous/refunded rows and multiple payments per
	 * registration are preserved.
	 */
	public function MarkpaidPage()
	{
		$token = $this->getRequest()->getQuery('e-token', '');
		if (!e107::getSession()->checkFormToken($token))
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_TOKEN);
			return $this->racereg_payActionResult();
		}

		$res = racereg_actions::markPaymentValid($this->getId());
		if ($res === 'changed')
		{
			e107::getMessage()->addSuccess(LAN_RACEREG_MSG_PAID);
		}
		elseif ($res === 'noop')
		{
			e107::getMessage()->addInfo(LAN_RACEREG_MSG_NOOP);
		}
		else
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_NOTFOUND);
		}

		return $this->racereg_payActionResult();
	}

	/** Action result page: messages + a link back to the payments list. */
	protected function racereg_payActionResult()
	{
		$url = e_SELF . '?mode=payments&action=list';
		return e107::getMessage()->render()
			. "<p><a class='btn btn-default' href='" . $url . "'>" . LAN_RACEREG_ACT_BACK . "</a></p>";
	}
}


class racereg_payment_form_ui extends e_admin_form_ui
{
	/**
	 * Options column: default edit/delete plus a Mark-paid button for rows that
	 * are not already valid. GET link carries the e107 e-token (verified in
	 * MarkpaidPage()).
	 */
	public function options($att, $value, $id, $attributes)
	{
		if (varset($attributes['mode']) !== 'read')
		{
			return '';
		}

		$row  = $this->getController()->getListModel()->getData();
		$pid  = (int) varset($row['payment_id'], 0);
		$btns = '';

		if ($pid > 0 && (int) varset($row['status'], 0) !== racereg_actions::PAY_VALID)
		{
			$url = e_SELF . '?mode=payments&id=' . $pid . '&e-token=' . e_TOKEN . '&action=markpaid';
			$btns .= "<a class='btn btn-success btn-sm' title='" . LAN_RACEREG_ACT_MARKPAID . "' href='" . $url . "'>" . LAN_RACEREG_ACT_MARKPAID . "</a> ";
		}

		$btns .= $this->renderValue('options', $value, array('readParms' => 'edit=1'), $id);

		return "<div class='btn-group'>" . $btns . "</div>";
	}
}


new racereg_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
