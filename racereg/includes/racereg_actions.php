<?php
/*
 * e107 website system
 *
 * racereg plugin - organizer admin actions service (issue #26).
 *
 * All state changes for the organizer workflow live here so the admin
 * controllers stay thin and the rules are in one testable place. Everything is
 * SERVER-SIDE and goes through the e107 db class array API with explicit
 * _FIELD_TYPES (the e107 equivalent of parameter binding) - no raw SQL, no value
 * is ever trusted from the client. Every state change is logged via
 * e107::getLog(); native e107 events are triggered at approval, rejection and
 * promotion so e_notify.php (and any future listener) can react.
 *
 * Track flags + capacity are read through racereg_signup (the canonical track
 * reader from #24: getTrack() / countPlaced()), so the race_ column names live in
 * exactly one place. Placement sentinel is shared too: a registration is on the
 * start list when start_list_at > 0 (substitutes / pending store 0).
 *
 * Derived paid status is computed from the sum of VALID payments vs the frozen
 * amount_due - it is display/filter only and never stored.
 */

if (!defined('e107_INIT')) { exit; }

require_once(e_PLUGIN . 'racereg/includes/racereg_signup.php');

class racereg_actions
{
	const TABLE_REG = 'racereg_registration';
	const TABLE_PAY = 'racereg_payment';

	/** Derived paid-status codes (display/filter only - never stored). */
	const PAID_UNPAID  = 0;
	const PAID_PARTIAL = 1;
	const PAID_PAID    = 2;
	const PAID_NOFEE   = 3; // amount_due <= 0: nothing owed, distinct from "paid"

	/** Payment status codes (racereg_payment.status). */
	const PAY_PENDING = 0;
	const PAY_VALID   = 1;

	/** Approval status codes (racereg_registration.approval_status). */
	const APPROVAL_PENDING  = 0;
	const APPROVAL_APPROVED = 1;
	const APPROVAL_REJECTED = 2;

	/**
	 * Shared canonical track reader (racereg_signup) - track flags + capacity.
	 * @return racereg_signup
	 */
	protected static function signup()
	{
		static $s = null;
		if ($s === null)
		{
			$s = new racereg_signup();
		}
		return $s;
	}

	/* ---------------------------------------------------------------------- *
	 *  Reads
	 * ---------------------------------------------------------------------- */

	/**
	 * Load a non-deleted registration row, or false.
	 * @param int $regId
	 * @return array|false
	 */
	public static function getRegistration($regId)
	{
		$regId = (int) $regId;
		if ($regId < 1)
		{
			return false;
		}

		$row = e107::getDb()->retrieve(self::TABLE_REG, '*',
			'registration_id = ' . $regId . ' AND deleted_at IS NULL');

		return empty($row) ? false : $row;
	}

	/**
	 * Sum of VALID payments for a registration (DECIMAL, 2dp).
	 * @param int $regId
	 * @return float
	 */
	public static function validPaidSum($regId)
	{
		$regId = (int) $regId;
		$rows  = e107::getDb()->retrieve(self::TABLE_PAY, 'amount',
			'registration_id = ' . $regId . ' AND status = ' . self::PAY_VALID, true);

		$sum = 0.0;
		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$sum += (float) $r['amount'];
			}
		}

		return round($sum, 2);
	}

	/**
	 * Derived paid status for a registration: compares the sum of VALID payments
	 * to the frozen amount_due. Display/filter only - never stored.
	 *
	 * A row with amount_due <= 0 is NO_FEE (a distinct "nothing owed" state) and
	 * is never reported as "paid": "paid" requires amount_due > 0 AND a covering
	 * valid payment.
	 *
	 * @param int        $regId
	 * @param float|null $amountDue pass the row's amount_due to avoid a re-read
	 * @return int one of PAID_NOFEE / PAID_UNPAID / PAID_PARTIAL / PAID_PAID
	 */
	public static function paidState($regId, $amountDue = null)
	{
		if ($amountDue === null)
		{
			$row       = self::getRegistration($regId);
			$amountDue = $row ? (float) $row['amount_due'] : 0.0;
		}

		$due = round((float) $amountDue, 2);
		if ($due <= 0)
		{
			return self::PAID_NOFEE; // nothing owed - distinct from "paid"
		}

		$paid = self::validPaidSum($regId);
		if ($paid <= 0)
		{
			return self::PAID_UNPAID;
		}

		return ($paid < $due) ? self::PAID_PARTIAL : self::PAID_PAID;
	}

	/**
	 * Registration ids matching a derived paid state, for the (raw-SQL-free)
	 * list filter. Computed in PHP from the db class, then fed to a
	 * `registration_id IN (...)` WHERE built by the controller.
	 *
	 * @param int $state PAID_UNPAID / PAID_PARTIAL / PAID_PAID
	 * @return int[] list of registration ids (may be empty)
	 */
	public static function registrationIdsByPaidState($state)
	{
		$state = (int) $state;
		$ids   = array();

		$regs = e107::getDb()->retrieve(self::TABLE_REG, 'registration_id, amount_due',
			'deleted_at IS NULL', true);

		if (is_array($regs))
		{
			foreach ($regs as $r)
			{
				$id = (int) $r['registration_id'];
				if (self::paidState($id, (float) $r['amount_due']) === $state)
				{
					$ids[] = $id;
				}
			}
		}

		return $ids;
	}

	/**
	 * Per-track registration figures for the read-only overview screen
	 * (admin/admin_regtracks.php). Logic lives here (racereg owns the
	 * registration data); the page just renders the returned array.
	 *
	 * Returns an array keyed by track_id, each value:
	 *   all       - non-deleted registrations on the track
	 *   approved  - approval_status = APPROVAL_APPROVED
	 *   rejected  - approval_status = APPROVAL_REJECTED
	 *   pending   - approval_status = APPROVAL_PENDING
	 *   nofee     - amount_due <= 0 (NOFEE is purely amount_due <= 0)
	 *   paid      - derived PAID: amount_due > 0 AND covered by valid payments
	 *
	 * Three reads via the db class (no raw concatenated input):
	 *   - approval breakdown: one GROUP BY query (all = sum of buckets);
	 *   - bez poplatku: one GROUP BY query (no payment lookup needed);
	 *   - zaplatené: the only count needing payments - iterate non-deleted rows
	 *     with amount_due > 0 and tally PAID_PAID via paidState().
	 *
	 * @return array track_id => array{all,approved,rejected,pending,nofee,paid}
	 */
	public static function countsByTrack()
	{
		$db  = e107::getDb();
		$out = array();

		// Approval breakdown - one grouped query; all = sum of the row's buckets.
		$rows = $db->retrieve(self::TABLE_REG,
			'track_id, approval_status, COUNT(*) AS cnt',
			'deleted_at IS NULL GROUP BY track_id, approval_status', true);

		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$tid = (int) $r['track_id'];
				if (!isset($out[$tid])) { $out[$tid] = self::emptyTrackCounts(); }

				$cnt = (int) $r['cnt'];
				$out[$tid]['all'] += $cnt;

				switch ((int) $r['approval_status'])
				{
					case self::APPROVAL_APPROVED: $out[$tid]['approved'] += $cnt; break;
					case self::APPROVAL_REJECTED: $out[$tid]['rejected'] += $cnt; break;
					case self::APPROVAL_PENDING:  $out[$tid]['pending']  += $cnt; break;
				}
			}
		}

		// No fee: amount_due <= 0 - one grouped query (purely amount_due, no payments).
		$rows = $db->retrieve(self::TABLE_REG,
			'track_id, COUNT(*) AS cnt',
			'amount_due <= 0 AND deleted_at IS NULL GROUP BY track_id', true);

		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$tid = (int) $r['track_id'];
				if (!isset($out[$tid])) { $out[$tid] = self::emptyTrackCounts(); }
				$out[$tid]['nofee'] = (int) $r['cnt'];
			}
		}

		// Paid: the only count needing a payment lookup. Iterate non-deleted rows
		// with amount_due > 0 and tally derived PAID_PAID per track (pass the row's
		// amount_due to paidState() to avoid a re-read).
		$rows = $db->retrieve(self::TABLE_REG,
			'registration_id, track_id, amount_due',
			'amount_due > 0 AND deleted_at IS NULL', true);

		if (is_array($rows))
		{
			foreach ($rows as $r)
			{
				$tid = (int) $r['track_id'];
				if (!isset($out[$tid])) { $out[$tid] = self::emptyTrackCounts(); }

				if (self::paidState((int) $r['registration_id'], (float) $r['amount_due']) === self::PAID_PAID)
				{
					$out[$tid]['paid']++;
				}
			}
		}

		return $out;
	}

	/** Zeroed per-track count bucket. */
	protected static function emptyTrackCounts()
	{
		return array(
			'all'      => 0,
			'approved' => 0,
			'rejected' => 0,
			'pending'  => 0,
			'nofee'    => 0,
			'paid'     => 0,
		);
	}

	/**
	 * Can a new registration be placed on this track right now (capacity)?
	 * @param int $trackId
	 * @return bool
	 */
	public static function canPlace($trackId)
	{
		$track = self::signup()->getTrack($trackId);
		if (!$track)
		{
			return false;
		}
		if ((int) $track['race_unlimited_capacity'] === 1)
		{
			return true;
		}

		return self::signup()->countPlaced($trackId) < (int) $track['race_capacity'];
	}

	/* ---------------------------------------------------------------------- *
	 *  Mark-paid
	 * ---------------------------------------------------------------------- */

	/**
	 * Mark a single payment row VALID + stamp paid_at. Only the targeted row is
	 * touched; erroneous/refunded rows and multiple-payment setups are preserved.
	 *
	 * @param int $paymentId
	 * @return string|false 'changed' | 'noop' (already valid) | false (not found)
	 */
	public static function markPaymentValid($paymentId)
	{
		$paymentId = (int) $paymentId;
		$row = e107::getDb()->retrieve(self::TABLE_PAY, '*', 'payment_id = ' . $paymentId);
		if (empty($row))
		{
			return false;
		}

		if ((int) $row['status'] === self::PAY_VALID)
		{
			return 'noop';
		}

		e107::getDb()->update(self::TABLE_PAY, array(
			'data'         => array('status' => self::PAY_VALID, 'paid_at' => time()),
			'_FIELD_TYPES' => array('status' => 'int', 'paid_at' => 'int'),
			'WHERE'        => 'payment_id = ' . $paymentId,
		));

		e107::getLog()->add('RACEREG_10',
			'Payment #' . $paymentId . ' marked valid (reg #' . (int) $row['registration_id'] . ')',
			E_LOG_INFORMATIVE, '');

		return 'changed';
	}

	/**
	 * Quick action from a registration: record a VALID payment for the
	 * outstanding remainder (amount_due - sum of valid payments). Adds one
	 * payment row (multiple payments per registration preserved); does nothing if
	 * already fully paid.
	 *
	 * @param int $regId
	 * @return float|false amount recorded (>0), 0.0 if nothing outstanding,
	 *                     false if the registration was not found
	 */
	public static function recordRegistrationPayment($regId)
	{
		$reg = self::getRegistration($regId);
		if (!$reg)
		{
			return false;
		}

		$regId       = (int) $reg['registration_id'];
		$outstanding = round((float) $reg['amount_due'] - self::validPaidSum($regId), 2);
		if ($outstanding <= 0)
		{
			return 0.0;
		}

		$now = time();
		e107::getDb()->insert(self::TABLE_PAY, array(
			'data' => array(
				'registration_id' => $regId,
				'amount'          => $outstanding,
				'status'          => self::PAY_VALID,
				'paid_at'         => $now,
				'note'            => 'Recorded via admin mark-paid',
				'created_at'      => $now,
			),
			'_FIELD_TYPES' => array(
				'registration_id' => 'int',
				'amount'          => 'float',
				'status'          => 'int',
				'paid_at'         => 'int',
				'note'            => 'escape',
				'created_at'      => 'int',
			),
		));

		e107::getLog()->add('RACEREG_11',
			'Recorded valid payment for reg #' . $regId, E_LOG_INFORMATIVE, '');

		return $outstanding;
	}

	/* ---------------------------------------------------------------------- *
	 *  Approval workflow
	 * ---------------------------------------------------------------------- */

	/**
	 * Approve a registration: approval_status = approved; place on the start list
	 * if capacity allows, otherwise keep as a substitute (start_list_at stays 0).
	 * Fires the (no-op) approval hook.
	 *
	 * @param int $regId
	 * @return string|false 'placed' | 'substitute' | false (not found)
	 */
	public static function approve($regId)
	{
		$reg = self::getRegistration($regId);
		if (!$reg)
		{
			return false;
		}

		$regId   = (int) $reg['registration_id'];
		$trackId = (int) $reg['track_id'];

		$data  = array('approval_status' => self::APPROVAL_APPROVED);
		$types = array('approval_status' => 'int');

		$placed = false;
		// Only assign a spot if not already placed and capacity allows.
		if ((int) $reg['start_list_at'] <= 0 && self::canPlace($trackId))
		{
			$data['start_list_at']  = time();
			$types['start_list_at'] = 'int';
			$placed = true;
		}

		e107::getDb()->update(self::TABLE_REG, array(
			'data'         => $data,
			'_FIELD_TYPES' => $types,
			'WHERE'        => 'registration_id = ' . $regId,
		));

		e107::getLog()->add('RACEREG_12',
			'Approved reg #' . $regId . ' (' . ($placed ? 'placed' : 'substitute') . ')',
			E_LOG_INFORMATIVE, '');

		e107::getEvent()->trigger('racereg_registration_approved', array('registration_id' => (int) $regId));

		return $placed ? 'placed' : 'substitute';
	}

	/**
	 * Reject a registration: approval_status = rejected. No placement. Kept
	 * (NOT soft-deleted) so the decision stays auditable and distinct from a
	 * withdrawal; if it somehow held a spot, that spot is released. Fires the
	 * (no-op) rejection hook.
	 *
	 * @param int $regId
	 * @return bool
	 */
	public static function reject($regId)
	{
		$reg = self::getRegistration($regId);
		if (!$reg)
		{
			return false;
		}

		$regId    = (int) $reg['registration_id'];
		$freedFrom = ((int) $reg['start_list_at'] > 0) ? (int) $reg['track_id'] : 0;

		e107::getDb()->update(self::TABLE_REG, array(
			'data'         => array('approval_status' => self::APPROVAL_REJECTED, 'start_list_at' => 0),
			'_FIELD_TYPES' => array('approval_status' => 'int', 'start_list_at' => 'int'),
			'WHERE'        => 'registration_id = ' . $regId,
		));

		e107::getLog()->add('RACEREG_13', 'Rejected reg #' . $regId, E_LOG_INFORMATIVE, '');
		e107::getEvent()->trigger('racereg_registration_rejected', array('registration_id' => (int) $regId));

		// Releasing a held spot may let the next substitute move up.
		if ($freedFrom > 0)
		{
			self::autoPromoteNext($freedFrom);
		}

		return true;
	}

	/* ---------------------------------------------------------------------- *
	 *  Substitute promotion
	 * ---------------------------------------------------------------------- */

	/**
	 * Promote a substitute onto the start list (capacity permitting). Fires the
	 * (no-op) promotion hook.
	 *
	 * @param int $regId
	 * @return string 'promoted' | 'full' | 'noop' (already placed) | 'notfound'
	 */
	public static function promote($regId)
	{
		$reg = self::getRegistration($regId);
		if (!$reg)
		{
			return 'notfound';
		}

		$regId   = (int) $reg['registration_id'];
		$trackId = (int) $reg['track_id'];

		if ((int) $reg['start_list_at'] > 0)
		{
			return 'noop'; // already on the start list
		}

		// Rejected registrations are not eligible; pending approval-track rows
		// must be approved first.
		if ((int) $reg['approval_status'] === self::APPROVAL_REJECTED)
		{
			return 'noop';
		}

		if (!self::canPlace($trackId))
		{
			return 'full';
		}

		e107::getDb()->update(self::TABLE_REG, array(
			'data'         => array('start_list_at' => time()),
			'_FIELD_TYPES' => array('start_list_at' => 'int'),
			'WHERE'        => 'registration_id = ' . $regId,
		));

		e107::getLog()->add('RACEREG_14', 'Promoted substitute reg #' . $regId, E_LOG_INFORMATIVE, '');
		e107::getEvent()->trigger('racereg_substitute_promoted', array('registration_id' => (int) $regId));

		return 'promoted';
	}

	/**
	 * Auto-promote the next eligible substitute on a track after a spot frees up
	 * (a confirmed registration withdrawn / rejected). The oldest substitute by
	 * registration_date is chosen. No-op when the disable flag is set (manual
	 * only) or the track is still full. Promotes at most one (one freed spot).
	 *
	 * Disable flag source: the raceevent pref `disableAutoPromote` (0 = auto-on,
	 * the default; 1 = manual only). See NOTES.md.
	 *
	 * @param int $trackId
	 * @return int promoted registration id, or 0 if none
	 */
	public static function autoPromoteNext($trackId)
	{
		$trackId = (int) $trackId;

		if (self::autoPromoteDisabled())
		{
			return 0;
		}

		$track = self::signup()->getTrack($trackId);
		if (!$track || !self::canPlace($trackId))
		{
			return 0;
		}

		// Eligible substitutes: not placed (0/NULL), not deleted; on approval
		// tracks only approved rows qualify, otherwise anything not rejected.
		$approvalClause = ((int) $track['race_requires_approval'] === 1)
			? ' AND approval_status = ' . self::APPROVAL_APPROVED
			: ' AND approval_status != ' . self::APPROVAL_REJECTED;

		$rows = e107::getDb()->retrieve(self::TABLE_REG, 'registration_id',
			'track_id = ' . $trackId
			. ' AND (start_list_at IS NULL OR start_list_at = 0) AND deleted_at IS NULL'
			. $approvalClause
			. ' ORDER BY registration_date ASC, registration_id ASC LIMIT 1', true);

		if (empty($rows) || !is_array($rows))
		{
			return 0;
		}

		$first = reset($rows);
		$nextId = (int) $first['registration_id'];

		e107::getDb()->update(self::TABLE_REG, array(
			'data'         => array('start_list_at' => time()),
			'_FIELD_TYPES' => array('start_list_at' => 'int'),
			'WHERE'        => 'registration_id = ' . $nextId,
		));

		e107::getLog()->add('RACEREG_15',
			'Auto-promoted reg #' . $nextId . ' on track ' . $trackId . ' (freed spot)',
			E_LOG_INFORMATIVE, '');
		e107::getEvent()->trigger('racereg_substitute_promoted', array('registration_id' => (int) $nextId));

		return $nextId;
	}

	/**
	 * Is auto-promotion disabled (manual only)? Reads the raceevent pref
	 * `disableAutoPromote` (default 0 = auto-on).
	 * @return bool
	 */
	public static function autoPromoteDisabled()
	{
		return (int) e107::getPlugConfig('raceevent')->get('disableAutoPromote', 0) === 1;
	}
}
