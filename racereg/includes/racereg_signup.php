<?php
/*
 * e107 website system
 *
 * racereg plugin - front-end sign-up processing (issue #24).
 *
 * Server-side handling for the public registration form. ALL public input is
 * untrusted: every value is validated here and stored through the e107 db class
 * array API with an explicit _FIELD_TYPES map (the e107 equivalent of parameter
 * binding); string values are passed through $tp->toDB() first. Price and the
 * variable symbol are resolved SERVER-SIDE ONLY and frozen - they are never read
 * from the client.
 *
 * Data sources (confirmed in pre-work):
 *   - event registration window + payee details: e107::getPlugConfig('raceevent')
 *     prefs registrationStartAt / registrationEndAt / payeeIban / payeeName.
 *   - track config: the `race` table columns race_registration_closed,
 *     race_unlimited_capacity, race_capacity, race_requires_approval, and the
 *     date-tiered `race_price` rows (race_price_value, race_price_from).
 *
 * Placement sentinel: a registration is "on the start list" when
 * start_list_at > 0. Substitutes and pending-approval rows store 0. The capacity
 * count therefore uses `start_list_at > 0` (robust to both 0 and NULL).
 */

if (!defined('e107_INIT')) { exit; }

require_once(e_PLUGIN . 'racereg/includes/racereg_vs.php');
require_once(e_PLUGIN . 'racereg/includes/racereg_payment_view.php'); // #40 shared payment view + pay_token
require_once(e_PLUGIN . 'racereg/includes/race_registration.php'); // #30 track config + price read API

class racereg_signup
{
	const TABLE_REG   = 'racereg_registration';
	const TABLE_TRACK = 'race';
	const TABLE_PRICE = 'race_price';

	/** Result state constants. */
	const STATE_STARTLIST  = 'startlist';
	const STATE_SUBSTITUTE = 'substitute';
	const STATE_PENDING    = 'pending';

	/** @var object e107 db */
	protected $db;

	/** @var object e107 parser */
	protected $tp;

	/** @var object raceevent plugin config */
	protected $eventCfg;

	/** @var array field => error message */
	protected $errors = array();

	public function __construct()
	{
		$this->db       = e107::getDb();
		$this->tp       = e107::getParser();
		$this->eventCfg = e107::getPlugConfig('raceevent');
	}

	/* ---------------------------------------------------------------------- *
	 *  Event window (raceevent prefs)
	 * ---------------------------------------------------------------------- */

	/**
	 * @return array{0:int,1:int} [start, end] Unix timestamps (0 = unbounded)
	 */
	public function getWindow()
	{
		return array(
			(int) $this->eventCfg->get('registrationStartAt', 0),
			(int) $this->eventCfg->get('registrationEndAt', 0),
		);
	}

	/**
	 * Is the event registration window currently open?
	 *
	 * @param int|null $now
	 * @return bool
	 */
	public function isWindowOpen($now = null)
	{
		$now = ($now === null) ? time() : (int) $now;
		list($start, $end) = $this->getWindow();

		if ($start > 0 && $now < $start)
		{
			return false;
		}
		if ($end > 0 && $now > $end)
		{
			return false;
		}

		return true;
	}

	/* ---------------------------------------------------------------------- *
	 *  Tracks (race)
	 * ---------------------------------------------------------------------- */

	/**
	 * Tracks open for sign-up (registrationClosed not set), id => name.
	 *
	 * @return array
	 */
	public function getOpenTracks()
	{
		$out  = array();
		$rows = $this->db->retrieve(
			self::TABLE_TRACK,
			'race_id, race_name',
			'race_registration_closed = 0 ORDER BY race_name ASC',
			true
		);

		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				$out[(int) $row['race_id']] = $row['race_name'];
			}
		}

		return $out;
	}

	/**
	 * Load a single track row, or false.
	 *
	 * @param int $trackId
	 * @return array|false
	 */
	public function getTrack($trackId)
	{
		$trackId = (int) $trackId;
		if ($trackId < 1)
		{
			return false;
		}

		return $this->db->retrieve(self::TABLE_TRACK, '*', 'race_id = ' . $trackId);
	}

	/* ---------------------------------------------------------------------- *
	 *  Price resolution (server-side, frozen)
	 * ---------------------------------------------------------------------- */

	/**
	 * Resolve the applicable fee for a track: the price tier with the greatest
	 * fromDate <= $now. Returns 0.00 if the track has no price tier at $now.
	 *
	 * Delegates to the canonical race read API (#30) so the date-tiered price
	 * logic lives in exactly one place and is not re-implemented here.
	 *
	 * @param int      $trackId
	 * @param int|null $now
	 * @return float
	 */
	public function resolvePrice($trackId, $now = null)
	{
		return race_registration::resolvePrice((int) $trackId, $now);
	}

	/* ---------------------------------------------------------------------- *
	 *  Capacity
	 * ---------------------------------------------------------------------- */

	/**
	 * Count registrations currently placed on the start list for a track.
	 * Placement sentinel: start_list_at > 0; soft-deleted rows excluded.
	 *
	 * @param int      $trackId
	 * @param int|null $maxId limit to registration_id <= this (rank check)
	 * @return int
	 */
	public function countPlaced($trackId, $maxId = null)
	{
		$trackId = (int) $trackId;
		$where   = 'track_id = ' . $trackId . ' AND start_list_at > 0 AND deleted_at IS NULL';

		if ($maxId !== null)
		{
			$where .= ' AND registration_id <= ' . (int) $maxId;
		}

		return (int) $this->db->count(self::TABLE_REG, '(*)', $where);
	}

	/* ---------------------------------------------------------------------- *
	 *  Shared placement + price (single source of truth)
	 *
	 *  The price-freeze + capacity-placement decision for a NEW registration on a
	 *  track. Used by BOTH the front-end sign-up (process()) and the admin manual
	 *  add (registrations e_admin_ui beforeCreate/afterCreate). All values here are
	 *  resolved SERVER-SIDE from the chosen track only - never trusted from the
	 *  client / admin POST. Track config + the date-tiered price come from the
	 *  canonical race read API (#30); they are not re-implemented here.
	 * ---------------------------------------------------------------------- */

	/**
	 * Freeze the date-tiered track price and decide initial placement by capacity
	 * (or approval) for a registration created at $now.
	 *
	 * @param int      $trackId
	 * @param int|null $now registration timestamp (price resolved at this moment)
	 * @return array{
	 *     fields: array{amount_due: float, approval_status: int, start_list_at: int},
	 *     state: string,   STATE_STARTLIST | STATE_SUBSTITUTE | STATE_PENDING
	 *     placed: bool,    placed on the start list now
	 *     limited: bool    placed AND capacity-limited -> last-spot recheck needed
	 * }
	 */
	public function applyTrackPlacementAndPrice($trackId, $now = null)
	{
		$trackId = (int) $trackId;
		$now     = ($now === null) ? time() : (int) $now;

		$cfg = race_registration::getTrackRegistration($trackId, $now);

		// Unknown track: zero fee, queued as a substitute (never auto-placed).
		if ($cfg === false)
		{
			return array(
				'fields'  => array('amount_due' => 0.00, 'approval_status' => 1, 'start_list_at' => 0),
				'state'   => self::STATE_SUBSTITUTE,
				'placed'  => false,
				'limited' => false,
			);
		}

		$fields = array('amount_due' => (float) $cfg['price']);

		// Approval tracks: created pending, NOT placed (placement happens on approval).
		if ((int) $cfg['requires_approval'] === 1)
		{
			$fields['approval_status'] = 0;
			$fields['start_list_at']   = 0;

			return array(
				'fields'  => $fields,
				'state'   => self::STATE_PENDING,
				'placed'  => false,
				'limited' => false,
			);
		}

		// Non-approval: auto-place by capacity. A free entry is still placed.
		$fields['approval_status'] = 1;

		$unlimited = ((int) $cfg['unlimited_capacity'] === 1);
		$capacity  = (int) $cfg['capacity'];
		$placeNow  = ($unlimited || $this->countPlaced($trackId) < $capacity);

		$fields['start_list_at'] = $placeNow ? $now : 0;

		return array(
			'fields'  => $fields,
			'state'   => $placeNow ? self::STATE_STARTLIST : self::STATE_SUBSTITUTE,
			'placed'  => $placeNow,
			'limited' => ($placeNow && !$unlimited),
		);
	}

	/**
	 * Last-spot safety (concurrency): after inserting a row that was placed on a
	 * capacity-limited track, re-check its rank by insertion order and demote it to
	 * a substitute if a concurrent create took the final spot. AUTO_INCREMENT
	 * guarantees id order == insert order, so the loser deterministically lands as
	 * rank capacity+1 here. Call only when applyTrackPlacementAndPrice() reported
	 * 'limited' => true. Returns the resolved state.
	 *
	 * @param int    $trackId
	 * @param int    $newId  the just-inserted registration id
	 * @param string $state  the optimistic state from applyTrackPlacementAndPrice()
	 * @return string STATE_STARTLIST | STATE_SUBSTITUTE
	 */
	public function confirmPlacement($trackId, $newId, $state)
	{
		$trackId = (int) $trackId;
		$newId   = (int) $newId;

		$cfg = race_registration::getTrackConfig($trackId);
		if ($cfg === false || (int) $cfg['unlimited_capacity'] === 1)
		{
			return $state;
		}

		$capacity = (int) $cfg['capacity'];
		if ($this->countPlaced($trackId, $newId) > $capacity)
		{
			$this->db->update(self::TABLE_REG, array(
				'data'         => array('start_list_at' => 0),
				'_FIELD_TYPES' => array('start_list_at' => 'int'),
				'WHERE'        => 'registration_id = ' . $newId,
			));

			return self::STATE_SUBSTITUTE;
		}

		return $state;
	}

	/* ---------------------------------------------------------------------- *
	 *  Validation
	 * ---------------------------------------------------------------------- */

	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Read a posted scalar, trimmed. Never trusts the client beyond this.
	 */
	protected function posted($data, $key)
	{
		return isset($data[$key]) ? trim((string) $data[$key]) : '';
	}

	/**
	 * Validate the public input. On success returns a clean, db-ready data array
	 * (strings already passed through toDB()). On failure returns false and
	 * fills getErrors(). Does NOT include price / VS / placement - those are set
	 * server-side in process().
	 *
	 * @param array $data raw $_POST
	 * @return array|false
	 */
	public function validate($data)
	{
		$this->errors = array();

		// --- Track: must exist and be open. -------------------------------
		$trackId = (int) $this->posted($data, 'track_id');
		$track   = $this->getTrack($trackId);

		if (!$track)
		{
			$this->errors['track_id'] = LAN_RACEREG_ERR_TRACK;
		}
		elseif ((int) $track['race_registration_closed'] === 1)
		{
			$this->errors['track_id'] = LAN_RACEREG_ERR_TRACK_CLOSED;
		}

		// --- Required text. ------------------------------------------------
		$firstName = $this->posted($data, 'first_name');
		$lastName  = $this->posted($data, 'last_name');
		if ($firstName === '')
		{
			$this->errors['first_name'] = LAN_RACEREG_ERR_REQUIRED;
		}
		if ($lastName === '')
		{
			$this->errors['last_name'] = LAN_RACEREG_ERR_REQUIRED;
		}

		// --- Email. --------------------------------------------------------
		$email = $this->posted($data, 'email');
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$this->errors['email'] = LAN_RACEREG_ERR_EMAIL;
		}

		// --- Birth date: e107 datepicker posts a Unix timestamp. Must parse,
		//     be in the past and within a sane year range. -------------------
		$birthRaw = $this->posted($data, 'birth_date');
		$birthTs  = $this->parseBirthDate($birthRaw);
		if ($birthTs === false)
		{
			$this->errors['birth_date'] = LAN_RACEREG_ERR_BIRTH;
		}

		// --- GDPR consent: required. --------------------------------------
		if ($this->posted($data, 'gdpr_consent') === '')
		{
			$this->errors['gdpr_consent'] = LAN_RACEREG_ERR_GDPR;
		}

		if (!empty($this->errors))
		{
			return false;
		}

		// Build clean, db-ready row. Strings -> toDB(); ints cast. Optional
		// fields kept only if provided (data minimisation).
		$clean = array(
			'track_id'   => $trackId,
			'first_name' => $this->tp->toDB($firstName),
			'last_name'  => $this->tp->toDB($lastName),
			'birth_date' => (int) $birthTs,
			'email'      => $this->tp->toDB($email),
		);

		foreach (array('street', 'city', 'postal_code', 'country', 'phone', 'club') as $opt)
		{
			$val = $this->posted($data, $opt);
			if ($val !== '')
			{
				$clean[$opt] = $this->tp->toDB($val);
			}
		}

		// New optional fields (category / nationality / local racer flag). All
		// untrusted public input, all optional - no new required-field errors.
		// category_id: int-cast (0 = "neurčené"). nationality: toDB() + maxlength 10
		// (matches the varchar(10) column). local: int toggle 0/1.
		$clean['category_id'] = (int) $this->posted($data, 'category_id');

		$nationality = $this->posted($data, 'nationality');
		if ($nationality !== '')
		{
			$clean['nationality'] = $this->tp->toDB(substr($nationality, 0, 10));
		}

		$clean['local'] = ($this->posted($data, 'local') !== '') ? 1 : 0;

		return $clean;
	}

	/**
	 * Parse the birth date posted by the e107 datepicker (a Unix timestamp, see
	 * form_handler::datepicker() useUnix default) into a validated Unix timestamp,
	 * or false if empty / non-numeric / future / implausible.
	 *
	 * @param string $raw
	 * @return int|false
	 */
	protected function parseBirthDate($raw)
	{
		if ($raw === '' || !is_numeric($raw))
		{
			return false;
		}

		$ts   = (int) $raw;
		$year = (int) date('Y', $ts);

		if ($ts > time() || $year < 1900)
		{
			return false;
		}

		return $ts;
	}

	/* ---------------------------------------------------------------------- *
	 *  Main flow
	 * ---------------------------------------------------------------------- */

	/**
	 * Validate, gate, freeze price, place (or queue as substitute / pending),
	 * generate the variable symbol, store the row and fire the (no-op) notify
	 * hook. Returns a result array for the confirmation page, or false on
	 * failure (see getErrors()).
	 *
	 * Honeypot + CSRF are checked by the caller (signup.php) before this runs.
	 *
	 * @param array $data raw $_POST
	 * @return array|false
	 */
	public function process($data)
	{
		$now = time();

		// Gate 1: event registration window.
		if (!$this->isWindowOpen($now))
		{
			$this->errors['window'] = LAN_RACEREG_ERR_WINDOW;
			return false;
		}

		// Gate 2 + field validation (includes track exists / not closed).
		$clean = $this->validate($data);
		if ($clean === false)
		{
			return false;
		}

		$track   = $this->getTrack($clean['track_id']);
		$trackId = (int) $clean['track_id'];

		// Price freeze + placement decision - the shared single source of truth,
		// server side only (never from the client). See applyTrackPlacementAndPrice().
		$placement = $this->applyTrackPlacementAndPrice($trackId, $now);
		$clean     = array_merge($clean, $placement['fields']);
		$state     = $placement['state'];

		// Variable symbol - server side, unique, locked.
		$clean['variable_symbol'] = racereg_vs::generate();

		// Pay token (#40) - unguessable, unique, server side. Lets the applicant
		// return to the tokenized public pay page later.
		$clean['pay_token'] = racereg_payment_view::generateToken();

		$clean['registration_date'] = $now;

		// Insert (parameterised via _FIELD_TYPES).
		$newId = $this->insert($clean);
		if (!$newId)
		{
			$this->errors['save'] = LAN_RACEREG_ERR_SAVE;
			return false;
		}

		// Last-spot race mitigation (documented in NOTES.md): if we placed on a
		// limited track, re-check this row's rank by insertion order and demote to
		// substitute if a concurrent sign-up took the final spot.
		if (!empty($placement['limited']))
		{
			$state = $this->confirmPlacement($trackId, $newId, $state);
		}

		// Audit - no PII in the log line (id + track + state only).
		e107::getLog()->add(
			'RACEREG_02',
			'Sign-up #' . $newId . ' track ' . $trackId . ' state ' . $state,
			E_LOG_INFORMATIVE,
			''
		);

		// Fire the registration event; e_notify.php delivers the admin email
		// when a recipient is assigned in Admin -> Notify (no-op otherwise).
		e107::getEvent()->trigger('racereg_registration_submitted', array('registration_id' => (int) $newId));

		return array(
			'registration_id' => (int) $newId,
			'state'           => $state,
			'track_name'      => $track['race_name'],
			'first_name'      => $clean['first_name'],
			'last_name'       => $clean['last_name'],
			'email'           => $clean['email'],
			'amount_due'      => (float) $clean['amount_due'],
			'variable_symbol' => $clean['variable_symbol'],
			'pay_token'       => $clean['pay_token'],
			'payee_iban'      => (string) $this->eventCfg->get('payeeIban', ''),
			'payee_name'      => (string) $this->eventCfg->get('payeeName', ''),
			'payee_swift'     => (string) $this->eventCfg->get('payeeSwift', ''),
		);
	}

	/**
	 * Insert a clean row via the db class array API with an explicit field-type
	 * map. Free-text strings are already toDB()'d in validate() and mapped here
	 * as 'escape' (the db class SQL-escaping layer); ids are cast with 'int'.
	 *
	 * @param array $clean
	 * @return int|false new registration_id
	 */
	protected function insert(array $clean)
	{
		$typeMap = array(
			'track_id'          => 'int',
			'first_name'        => 'escape',
			'last_name'         => 'escape',
			'birth_date'        => 'int',
			'street'            => 'escape',
			'city'              => 'escape',
			'postal_code'       => 'escape',
			'country'           => 'escape',
			'email'             => 'escape',
			'phone'             => 'escape',
			'club'              => 'escape',
			'category_id'       => 'int',
			'nationality'       => 'escape',
			'local'             => 'int',
			'registration_date' => 'int',
			'start_list_at'     => 'int',
			'variable_symbol'   => 'escape',
			'pay_token'         => 'escape',
			'amount_due'        => 'float',
			'approval_status'   => 'int',
		);

		$types = array();
		foreach (array_keys($clean) as $col)
		{
			if (isset($typeMap[$col]))
			{
				$types[$col] = $typeMap[$col];
			}
		}

		return $this->db->insert(self::TABLE_REG, array(
			'data'         => $clean,
			'_FIELD_TYPES' => $types,
		));
	}
}
