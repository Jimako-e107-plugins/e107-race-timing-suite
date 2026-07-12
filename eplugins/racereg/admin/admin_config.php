<?php
/*
 * e107 website system
 *
 * racereg plugin - Registrations admin CRUD (issue #22).
 *
 * Bootstrap: load the framework, include the shared dispatcher/menu, check the
 * plugin's OWN admin permission (getperms('P')), then run the page. Native e107
 * e_admin_ui throughout: it runs $tp->toDB() on save and $tp->toHTML() on
 * display, builds parameterized queries from the field 'data' types, and
 * protects every form with the e_token (CSRF). No raw SQL bypasses it.
 *
 * PII NOTE: this screen holds the heaviest personal data in the suite (name,
 * birth date, address, email, phone). It is admin-only (organizer) - there is
 * no front-end exposure in this issue. Deletes are SOFT (deleted_at timestamp)
 * for audit / restore / retention; rows are never hard-deleted.
 *
 * Out of scope here (issue #3): automatic price resolution / amount_due freeze
 * and the front-end sign-up flow. amount_due is entered manually below.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu
require_once(e_PLUGIN . "racereg/includes/racereg_vs.php");           // shared VS generator
require_once(e_PLUGIN . "racereg/includes/racereg_actions.php");      // organizer workflow actions (#26)
require_once(e_PLUGIN . "racereg/includes/racereg_payment_view.php"); // shared payment view + pay_token (#40)

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racereg_registration_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEREG_001;
	protected $pluginName  = 'racereg';

	protected $table = 'racereg_registration';
	protected $pid   = 'registration_id';

	protected $perPage = 20;

	// Never hard-delete PII in bulk and never bulk-copy it; per-row delete is
	// intercepted by beforeDelete() below and turned into a soft-delete.
	protected $batchDelete = false;
	protected $batchCopy   = false;

	protected $listOrder = 'registration_id DESC';

	// Default visible list columns.
	protected $fieldpref = array('first_name', 'last_name', 'track_id', 'email', 'start_list_at', 'amount_due', 'paid_status', 'variable_symbol');

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
		'registration_id' => array(
			'title' => LAN_ID,
			'type'  => 'number',
			'data'  => 'int',
			'width' => '5%',
		),
		// Cross-plugin read: options populated from the race (tracks) table in
		// init(). data 'int' stores the race_id.
		'track_id' => array(
			'title'    => LAN_RACEREG_TRACK,
			'type'     => 'dropdown',
			'data'     => 'int',
			'width'    => 'auto',
			'filter'   => true,
			'inline'   => true,
			'writeParms' => array(),
			'readParms'  => array(),
		),
		'first_name' => array(
			'title'    => LAN_RACEREG_FIRST_NAME,
			'type'     => 'text',
			'data'     => 'safestr',
			'width'    => 'auto',
			'required' => true,
			'validate' => true,
			'inline'   => true,
			'writeParms' => array('size' => 'large', 'required' => 1),
		),
		'last_name' => array(
			'title'    => LAN_RACEREG_LAST_NAME,
			'type'     => 'text',
			'data'     => 'safestr',
			'width'    => 'auto',
			'required' => true,
			'validate' => true,
			'inline'   => true,
			'writeParms' => array('size' => 'large', 'required' => 1),
		),
		// Date-only (no time): datestamp + writeParms type=date. Stored as INT.
		'birth_date' => array(
			'title'      => LAN_RACEREG_BIRTH_DATE,
			'type'       => 'datestamp',
			'data'       => 'int',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('type' => 'date'),
		),
		'street' => array(
			'title'      => LAN_RACEREG_STREET,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'xxlarge'),
		),
		'city' => array(
			'title'      => LAN_RACEREG_CITY,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'large'),
		),
		// Numeric string - kept as string, never cast to int.
		'postal_code' => array(
			'title'      => LAN_RACEREG_POSTAL,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'small'),
		),
		'country' => array(
			'title'      => LAN_RACEREG_COUNTRY,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'large'),
		),
		'email' => array(
			'title'    => LAN_RACEREG_EMAIL,
			'type'     => 'email',
			'data'     => 'str',
			'width'    => 'auto',
			'writeParms' => array('size' => 'large'),
		),
		'phone' => array(
			'title'      => LAN_RACEREG_PHONE,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'large'),
		),
		'club' => array(
			'title'      => LAN_RACEREG_CLUB,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'large'),
		),
		// Auto-set to now on create (beforeCreate) if empty; editable date+time.
		'registration_date' => array(
			'title'      => LAN_RACEREG_REG_DATE,
			'type'       => 'datestamp',
			'data'       => 'int',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array(),
		),
		// Nullable: when set, the registrant is on the start list.
		'start_list_at' => array(
			'title'      => LAN_RACEREG_START_LIST_AT,
			'type'       => 'datestamp',
			'data'       => 'int',
			'width'      => 'auto',
			'writeParms' => array(),
		),
		// Auto-generated unique numeric string on create; read-only afterwards.
		'variable_symbol' => array(
			'title'      => LAN_RACEREG_VS,
			'type'       => 'text',
			'data'       => 'str',
			'width'      => 'auto',
			'help'       => LAN_RACEREG_VS_HELP,
			'writeParms' => array('readonly' => 1),
		),
		// Manual entry in this issue (auto price freeze comes in #3).
		'amount_due' => array(
			'title'      => LAN_RACEREG_AMOUNT_DUE,
			'type'       => 'text',
			'data'       => 'float',
			'width'      => 'auto',
			'help'       => LAN_RACEREG_AMOUNT_DUE_HELP,
			'writeParms' => array('size' => 'small'),
		),
		'approval_status' => array(
			'title'    => LAN_RACEREG_APPROVAL,
			'type'     => 'dropdown',
			'data'     => 'int',
			'width'    => 'auto',
			'filter'   => true,
			'inline'   => true,
			'writeParms' => array(),
		),
		// Derived paid status (issue #26): computed from VALID payments vs the
		// frozen amount_due. Display only - NOT a stored column, so no 'data' and
		// excluded from create/edit forms. Rendered by the form_ui paid_status().
		'paid_status' => array(
			'title'    => LAN_RACEREG_PAID_STATUS,
			'type'     => 'method',
			'data'     => null,
			'width'    => 'auto',
			'noedit'   => true,
			'nocreate' => true,
			'help'     => LAN_RACEREG_PAID_STATUS_HELP,
		),
		'private_note' => array(
			'title'      => LAN_RACEREG_PRIVATE_NOTE,
			'type'       => 'textarea',
			'data'       => 'str',
			'width'      => 'auto',
			'nolist'     => true,
			'writeParms' => array('size' => 'block-level'),
		),
		// type 'method' so the form_ui can append the workflow action buttons
		// (approve / reject / promote / mark-paid) next to the default edit/delete.
		'options' => array(
			'title'   => LAN_OPTIONS,
			'type'    => 'method',
			'data'    => null,
			'width'   => '15%',
			'thclass' => 'center last',
			'class'   => 'center last',
			'forced'  => 'value',
		),
	);

	protected $prefs = array();

	/** Per-track flags preloaded in init(): trackId => array(flags). */
	protected $trackFlags = array();

	/** Placement decision from beforeCreate(), consumed by afterCreate(). */
	protected $racereg_placement = null;

	/** Track id chosen on the create form, captured in beforeCreate(). */
	protected $racereg_placeTrack = 0;

	public function init()
	{
		// Hide soft-deleted rows by default (native permanent WHERE; ANDed with
		// any search/filter the core adds).
		$where = 'deleted_at IS NULL';

		// Derived paid-status quick filter (issue #26). Raw-SQL-free: compute the
		// matching ids in PHP via the action service, then constrain with a
		// framework `registration_id IN (...)` clause. -1 forces an empty set when
		// nothing matches, so the list correctly shows no rows.
		$fpaid = e107::getParser()->filter(varset($_GET['fpaid'], ''));
		$paidMap = array(
			'nofee'   => racereg_actions::PAID_NOFEE,
			'unpaid'  => racereg_actions::PAID_UNPAID,
			'partial' => racereg_actions::PAID_PARTIAL,
			'paid'    => racereg_actions::PAID_PAID,
		);
		if (isset($paidMap[$fpaid]))
		{
			$ids = racereg_actions::registrationIdsByPaidState($paidMap[$fpaid]);
			$ids = empty($ids) ? array(-1) : array_map('intval', $ids);
			$where .= ' AND registration_id IN (' . implode(',', $ids) . ')';
		}

		$this->listQrySql['db_where'] = $where;

		// Cross-plugin read-only read of the race (tracks) table -> dropdown +
		// per-track flag map (capacity/approval) for the row action buttons.
		$tracks = array();
		$rows   = e107::getDb()->retrieve('race',
			'race_id, race_name, race_capacity, race_unlimited_capacity, race_requires_approval',
			'', true);
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$tid          = (int) $row['race_id'];
				$tracks[$tid] = $row['race_name'];
				$this->trackFlags[$tid] = array(
					'capacity'           => (int) $row['race_capacity'],
					'unlimited_capacity' => (int) $row['race_unlimited_capacity'],
					'requires_approval'  => (int) $row['race_requires_approval'],
				);
			}
		}
		$this->fields['track_id']['writeParms']['optArray'] = $tracks;
		$this->fields['track_id']['readParms']['optArray']  = $tracks;

		// Approval status labels.
		$approval = array(
			0 => LAN_RACEREG_APPROVAL_0,
			1 => LAN_RACEREG_APPROVAL_1,
			2 => LAN_RACEREG_APPROVAL_2,
		);
		$this->fields['approval_status']['writeParms']['optArray'] = $approval;
		$this->fields['approval_status']['readParms']['optArray']  = $approval;
	}

	/**
	 * Per-track flags preloaded in init(), for the row action buttons.
	 * @param int $trackId
	 * @return array array('capacity','unlimited_capacity','requires_approval')
	 */
	public function getTrackFlags($trackId)
	{
		$trackId = (int) $trackId;
		return isset($this->trackFlags[$trackId])
			? $this->trackFlags[$trackId]
			: array('capacity' => 0, 'unlimited_capacity' => 0, 'requires_approval' => 0);
	}

	/* ---------------------------------------------------------------------- *
	 *  Custom workflow actions (issue #26)
	 *
	 *  Each is a native e_admin_ui custom action: a GET row-button carrying the
	 *  e107 e-token hits ?mode=main&action=<x>&id=<id>&e-token=..., which the
	 *  dispatcher routes to <X>Page(). The token is verified server-side; the work
	 *  is delegated to the racereg_actions service (logged + hooks fired there);
	 *  the result renders as messages + a back link (the githubSync pattern). All
	 *  state is server-side; nothing is trusted from the client.
	 * ---------------------------------------------------------------------- */

	/** Approve a pending registration (place per capacity, else substitute). */
	public function ApprovePage()
	{
		if (!$this->racereg_checkActionToken())
		{
			return $this->racereg_actionResult();
		}

		$res = racereg_actions::approve($this->getId());
		if ($res === 'placed')
		{
			e107::getMessage()->addSuccess(LAN_RACEREG_MSG_APPROVED_PLACED);
		}
		elseif ($res === 'substitute')
		{
			e107::getMessage()->addSuccess(LAN_RACEREG_MSG_APPROVED_SUB);
		}
		else
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_NOTFOUND);
		}

		return $this->racereg_actionResult();
	}

	/** Reject a pending registration (no placement; kept for audit). */
	public function RejectPage()
	{
		if (!$this->racereg_checkActionToken())
		{
			return $this->racereg_actionResult();
		}

		$ok = racereg_actions::reject($this->getId());
		e107::getMessage()->add(
			$ok ? LAN_RACEREG_MSG_REJECTED : LAN_RACEREG_ERR_NOTFOUND,
			$ok ? E_MESSAGE_SUCCESS : E_MESSAGE_ERROR
		);

		return $this->racereg_actionResult();
	}

	/** Promote a substitute onto the start list (capacity permitting). */
	public function PromotePage()
	{
		if (!$this->racereg_checkActionToken())
		{
			return $this->racereg_actionResult();
		}

		switch (racereg_actions::promote($this->getId()))
		{
			case 'promoted':
				e107::getMessage()->addSuccess(LAN_RACEREG_MSG_PROMOTED);
				break;
			case 'full':
				e107::getMessage()->addWarning(LAN_RACEREG_MSG_FULL);
				break;
			case 'noop':
				e107::getMessage()->addInfo(LAN_RACEREG_MSG_NOOP);
				break;
			default:
				e107::getMessage()->addError(LAN_RACEREG_ERR_NOTFOUND);
				break;
		}

		return $this->racereg_actionResult();
	}

	/** Quick mark-paid from a registration: record a valid payment for the rest. */
	public function MarkpaidPage()
	{
		if (!$this->racereg_checkActionToken())
		{
			return $this->racereg_actionResult();
		}

		$res = racereg_actions::recordRegistrationPayment($this->getId());
		if ($res === false)
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_NOTFOUND);
		}
		elseif ($res <= 0)
		{
			e107::getMessage()->addInfo(LAN_RACEREG_MSG_ALREADY_PAID);
		}
		else
		{
			e107::getMessage()->addSuccess(
				e107::getParser()->lanVars(LAN_RACEREG_MSG_RECORDED, number_format((float) $res, 2, '.', ' '))
			);
		}

		return $this->racereg_actionResult();
	}

	/**
	 * Show payment details (issue #40): render the SHARED payment view + PAY by
	 * square QR for one registration, behind the plugin admin permission (gated at
	 * the top of this file) and the e-token. For a legacy row without a pay_token,
	 * one is generated + stored lazily here (ensureToken). PII-light: shows the
	 * payee / IBAN / amount / VS / QR plus the applicant's own name, paid state and
	 * the tokenized pay link - nothing else.
	 */
	public function PaymentPage()
	{
		if (!$this->racereg_checkActionToken())
		{
			return $this->racereg_actionResult();
		}

		$reg = racereg_actions::getRegistration($this->getId());
		if (!$reg)
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_NOTFOUND);
			return $this->racereg_actionResult();
		}

		$tp    = e107::getParser();
		$regId = (int) $reg['registration_id'];

		// Lazy backfill for legacy rows; new rows already have one from afterCreate().
		$token = racereg_payment_view::ensureToken($regId, (string) varset($reg['pay_token'], ''));

		// Enqueue the vendored QR bundle in the ADMIN context (same local file).
		e107::js('footer', e_PLUGIN_ABS . 'racereg/js/racereg-qr.bundle.js');

		$name  = $tp->toHTML((string) varset($reg['first_name'], ''), false, 'defs')
			. ' ' . $tp->toHTML((string) varset($reg['last_name'], ''), false, 'defs');
		$badge = $this->racereg_paidBadge(racereg_actions::paidState($regId, (float) $reg['amount_due']));

		$head = "<p><strong>" . LAN_RACEREG_FIRST_NAME . " / " . LAN_RACEREG_LAST_NAME . ":</strong> " . $name . "</p>"
			. "<p><strong>" . LAN_RACEREG_PAID_STATUS . ":</strong> " . $badge . "</p>";

		$payment = racereg_payment_view::render(racereg_payment_view::buildData($reg), $tp);

		// The tokenized public pay link (organizer can copy / share it).
		$payUrl  = racereg_payment_view::payUrl($token);
		$linkRow = ($payUrl !== '')
			? "<p class='text-muted'>" . LAN_RACEREG_PAY_LINK_NOTE
				. "<br><a href='" . $payUrl . "' target='_blank'>" . $payUrl . "</a></p>"
			: '';

		return e107::getMessage()->render() . $head . $payment . $linkRow . $this->racereg_backLink();
	}

	/**
	 * Derived paid-state badge (display only), shared by the payment-details page.
	 * @param int $state racereg_actions PAID_* code
	 * @return string
	 */
	protected function racereg_paidBadge($state)
	{
		switch ((int) $state)
		{
			case racereg_actions::PAID_NOFEE:
				return "<span class='label label-info'>" . LAN_RACEREG_PAID_NOFEE . "</span>";
			case racereg_actions::PAID_PAID:
				return "<span class='label label-success'>" . LAN_RACEREG_PAID_PAID . "</span>";
			case racereg_actions::PAID_PARTIAL:
				return "<span class='label label-warning'>" . LAN_RACEREG_PAID_PARTIAL . "</span>";
			default:
				return "<span class='label label-default'>" . LAN_RACEREG_PAID_UNPAID . "</span>";
		}
	}

	/**
	 * Verify the e-token carried by the action link. Adds an error message on
	 * failure.
	 * @return bool
	 */
	protected function racereg_checkActionToken()
	{
		$token = $this->getRequest()->getQuery('e-token', '');
		if (!e107::getSession()->checkFormToken($token))
		{
			e107::getMessage()->addError(LAN_RACEREG_ERR_TOKEN);
			return false;
		}
		return true;
	}

	/**
	 * Render an action result page: the messages plus a link back to the list.
	 * @return string
	 */
	protected function racereg_actionResult()
	{
		return e107::getMessage()->render() . $this->racereg_backLink();
	}

	/**
	 * A "back to list" button (shared by the action result + the payment page).
	 * @return string
	 */
	protected function racereg_backLink()
	{
		$url = e_SELF . '?mode=main&action=list';
		return "<p><a class='btn btn-default' href='" . $url . "'>" . LAN_RACEREG_ACT_BACK . "</a></p>";
	}

	/**
	 * Auto-generate the variable_symbol (unique numeric string, <= 10 chars),
	 * default registration_date to now, then freeze the date-tiered track price and
	 * place the registration by capacity via the shared racereg_signup logic (the
	 * same single source of truth used by the front-end sign-up, #24). Price and
	 * placement are resolved SERVER-SIDE from the chosen track only - any posted
	 * amount_due / start_list_at / approval_status is overridden here. Returned
	 * values are merged into the row before insert by e_admin_ui.
	 */
	public function beforeCreate($new_data, $old_data)
	{
		$extra = array(
			'variable_symbol' => $this->generateVariableSymbol(),
		);

		// Registration timestamp: used both as the stored date and as the moment
		// the date-tiered price is resolved at.
		if (empty($new_data['registration_date']))
		{
			$regTs = time();
			$extra['registration_date'] = $regTs;
		}
		else
		{
			$regTs = (int) $new_data['registration_date'];
		}

		$trackId   = (int) varset($new_data['track_id'], 0);
		$signup    = new racereg_signup();
		$placement = $signup->applyTrackPlacementAndPrice($trackId, $regTs);

		// Server-side price freeze + placement (amount_due, approval_status,
		// start_list_at) - overrides anything posted.
		$extra = array_merge($extra, $placement['fields']);

		// Remembered for the post-insert last-spot recheck + audit log in afterCreate().
		$this->racereg_placement  = $placement;
		$this->racereg_placeTrack = $trackId;

		return $extra;
	}

	/**
	 * Post-insert last-spot recheck (concurrency safety, shared with the sign-up
	 * flow) and audit log of the server-side price freeze + placement for a manual
	 * add.
	 */
	public function afterCreate($new_data, $old_data, $id)
	{
		$id = (int) $id;

		// Pay token (#40): admin create path. A freshly created row has no token
		// (column default NULL) -> ensureToken() generates + stores one
		// (parameterised). Same helper used for the lazy legacy backfill below.
		racereg_payment_view::ensureToken($id);

		if (!is_array($this->racereg_placement))
		{
			return;
		}

		$trackId = (int) $this->racereg_placeTrack;
		$state   = $this->racereg_placement['state'];

		if (!empty($this->racereg_placement['limited']))
		{
			$signup = new racereg_signup();
			$state  = $signup->confirmPlacement($trackId, $id, $state);
			if ($state === racereg_signup::STATE_SUBSTITUTE)
			{
				e107::getMessage()->addInfo(LAN_RACEREG_MSG_CREATE_SUBSTITUTE);
			}
		}

		$price = (float) $this->racereg_placement['fields']['amount_due'];
		e107::getLog()->add('RACEREG_03',
			'Admin add #' . $id . ' track ' . $trackId . ' state ' . $state
			. ' price ' . number_format($price, 2, '.', ''),
			E_LOG_INFORMATIVE, '');
	}

	/**
	 * Soft-delete: stamp deleted_at and cancel the hard delete (return false).
	 * Called by e_admin_ui for both single and batch deletes.
	 */
	public function beforeDelete($data, $id)
	{
		$id = (int) $id;

		// Capture placement + track BEFORE soft-deleting, to know if a confirmed
		// spot is being freed (issue #26 auto-promotion).
		$row     = e107::getDb()->retrieve('racereg_registration',
			'track_id, start_list_at', 'registration_id=' . $id);
		$wasPlaced = (!empty($row) && (int) $row['start_list_at'] > 0);
		$trackId   = !empty($row) ? (int) $row['track_id'] : 0;

		e107::getDb()->update('racereg_registration', array(
			'data'         => array('deleted_at' => time()),
			'_FIELD_TYPES' => array('deleted_at' => 'int'),
			'WHERE'        => 'registration_id=' . $id,
		));

		e107::getLog()->add('RACEREG_01', 'Soft-deleted registration #' . $id, E_LOG_INFORMATIVE, '');
		e107::getMessage()->addSuccess(LAN_RACEREG_SOFT_DELETED);

		// Withdrawing a confirmed registration frees a spot -> auto-promote the
		// oldest eligible substitute, unless the disable flag is set (manual only).
		if ($wasPlaced && $trackId > 0)
		{
			$promoted = racereg_actions::autoPromoteNext($trackId);
			if ($promoted > 0)
			{
				e107::getMessage()->addInfo(LAN_RACEREG_MSG_AUTOPROMOTED);
			}
		}

		return false; // never hard-delete PII
	}

	/**
	 * Generate a unique numeric variable symbol (<= 10 chars). Delegates to the
	 * shared racereg_vs helper so admin (#22) and the front-end sign-up (#24)
	 * share one generator / uniqueness guarantee.
	 */
	protected function generateVariableSymbol()
	{
		return racereg_vs::generate();
	}

	public function renderHelp()
	{
		// Derived paid-status quick filter (links; the filter is applied in init()).
		$cur  = e107::getParser()->filter(varset($_GET['fpaid'], ''));
		$base = e_SELF . '?mode=main&action=list';
		$opts = array(
			''        => LAN_RACEREG_PAIDFILTER_ALL,
			'nofee'   => LAN_RACEREG_PAID_NOFEE,
			'unpaid'  => LAN_RACEREG_PAID_UNPAID,
			'partial' => LAN_RACEREG_PAID_PARTIAL,
			'paid'    => LAN_RACEREG_PAID_PAID,
		);

		$links = array();
		foreach ($opts as $key => $label)
		{
			$url    = ($key === '') ? $base : $base . '&fpaid=' . $key;
			$active = ($key === $cur) ? ' btn-primary' : ' btn-default';
			$links[] = "<a class='btn btn-xs" . $active . "' href='" . $url . "'>" . $label . "</a>";
		}

		$text = LAN_RACEREG_REG_HELP
			. "<hr><strong>" . LAN_RACEREG_PAIDFILTER . "</strong><br>"
			. "<div class='btn-group' style='margin-top:5px'>" . implode('', $links) . "</div>"
			. "<hr>" . LAN_RACEREG_CONFIG_DOC_HELP;

		return array('caption' => LAN_HELP, 'text' => $text);
	}
}


class racereg_registration_form_ui extends e_admin_form_ui
{
	/**
	 * Derived paid-status column (issue #26): a badge computed from VALID
	 * payments vs the frozen amount_due. List display only; never stored.
	 */
	public function paid_status($curVal, $mode, $attributes)
	{
		// Only meaningful for an existing list row.
		$row = $this->getController()->getListModel()->getData();
		if (empty($row['registration_id']))
		{
			return '';
		}

		$state = racereg_actions::paidState((int) $row['registration_id'], (float) $row['amount_due']);

		switch ($state)
		{
			case racereg_actions::PAID_NOFEE:
				return "<span class='label label-info'>" . LAN_RACEREG_PAID_NOFEE . "</span>";
			case racereg_actions::PAID_PAID:
				return "<span class='label label-success'>" . LAN_RACEREG_PAID_PAID . "</span>";
			case racereg_actions::PAID_PARTIAL:
				return "<span class='label label-warning'>" . LAN_RACEREG_PAID_PARTIAL . "</span>";
			default:
				return "<span class='label label-default'>" . LAN_RACEREG_PAID_UNPAID . "</span>";
		}
	}

	/**
	 * Options column (issue #26): default edit/delete plus the workflow action
	 * buttons, shown conditionally per row state. Each button is a GET link
	 * carrying the e107 e-token (verified server-side in the *Page() handlers).
	 */
	public function options($att, $value, $id, $attributes)
	{
		if (varset($attributes['mode']) !== 'read')
		{
			return '';
		}

		$row = $this->getController()->getListModel()->getData();
		$regId = (int) varset($row['registration_id'], 0);
		if ($regId < 1)
		{
			return $this->renderValue('options', $value, array('readParms' => 'edit=1'), $id);
		}

		$flags    = $this->getController()->getTrackFlags((int) varset($row['track_id'], 0));
		$reqApr   = !empty($flags['requires_approval']);
		$placed   = ((int) varset($row['start_list_at'], 0) > 0);
		$apr      = (int) varset($row['approval_status'], 0);
		$due      = (float) varset($row['amount_due'], 0);
		$paidSum  = racereg_actions::validPaidSum($regId);

		$base = e_SELF . '?mode=main&id=' . $regId . '&e-token=' . e_TOKEN . '&action=';
		$btns = '';

		// Approve / reject: pending rows on approval tracks.
		if ($reqApr && $apr === racereg_actions::APPROVAL_PENDING)
		{
			$btns .= "<a class='btn btn-success btn-sm' title='" . LAN_RACEREG_ACT_APPROVE . "' href='" . $base . "approve'>" . LAN_RACEREG_ACT_APPROVE . "</a> ";
			$btns .= "<a class='btn btn-danger btn-sm' title='" . LAN_RACEREG_ACT_REJECT . "' href='" . $base . "reject' onclick=\"return confirm('" . LAN_RACEREG_CONFIRM_REJECT . "');\">" . LAN_RACEREG_ACT_REJECT . "</a> ";
		}

		// Promote: a substitute (not placed) that is not rejected and, on approval
		// tracks, already approved.
		if (!$placed && $apr !== racereg_actions::APPROVAL_REJECTED && !($reqApr && $apr === racereg_actions::APPROVAL_PENDING))
		{
			$btns .= "<a class='btn btn-primary btn-sm' title='" . LAN_RACEREG_ACT_PROMOTE . "' href='" . $base . "promote'>" . LAN_RACEREG_ACT_PROMOTE . "</a> ";
		}

		// Mark-paid: only when something is still outstanding.
		if (round($due - $paidSum, 2) > 0)
		{
			$btns .= "<a class='btn btn-default btn-sm' title='" . LAN_RACEREG_ACT_MARKPAID . "' href='" . $base . "markpaid'>" . LAN_RACEREG_ACT_MARKPAID . "</a> ";
		}

		// Show payment details + QR (issue #40) - always available per registration.
		$btns .= "<a class='btn btn-info btn-sm' title='" . LAN_RACEREG_ACT_PAYMENT . "' href='" . $base . "payment'>" . LAN_RACEREG_ACT_PAYMENT . "</a> ";

		$btns .= $this->renderValue('options', $value, array('readParms' => 'edit=1'), $id);

		return "<div class='btn-group-vertical'>" . $btns . "</div>";
	}
}


new racereg_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
