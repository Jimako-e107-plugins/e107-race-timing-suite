<?php
/*
 * e107 website system
 *
 * racereg plugin - confirmation-page preview data builder.
 *
 * Isolated helper used ONLY by the admin-only inert preview of the sign-up
 * confirmation page (signup.php?preview_confirm=<state>, guarded by
 * getperms('0')). It builds a FAKE result array in the exact shape returned by
 * racereg_signup::process(), so the real confirm renderer (racereg_render_confirm
 * + racereg_payment_view) renders a true-to-production preview with no database
 * write and no event trigger.
 *
 * Kept in its own class/file on purpose: the tested racereg_signup class (form
 * validation, placement, price freeze, VS, insert) is NOT touched by this feature.
 *
 * SECURITY: no user input is stored or queried. The only request value consumed
 * is the state keyword, whitelisted against racereg_signup::STATE_* (unknown ->
 * pending). pay_token is an obviously-fake constant that never resolves to a real
 * registration. Payee IBAN/name/SWIFT are the organizer's own NON-PII config from
 * the raceevent prefs.
 */

if (!defined('e107_INIT')) { exit; }

require_once(e_PLUGIN . 'racereg/includes/racereg_signup.php'); // STATE_* constants (read-only)

class racereg_preview
{
	/** Obviously-fake pay token - display only, never resolved against the DB. */
	const FAKE_PAY_TOKEN = '00000000000000000000000000000000';

	/**
	 * Build a fake process()-shaped result for the confirmation preview.
	 *
	 * @param string $state one of racereg_signup::STATE_* (unknown -> pending)
	 * @return array
	 */
	public static function build($state)
	{
		$allowed = array(
			racereg_signup::STATE_STARTLIST,
			racereg_signup::STATE_SUBSTITUTE,
			racereg_signup::STATE_PENDING,
		);

		if (!in_array($state, $allowed, true))
		{
			$state = racereg_signup::STATE_PENDING;
		}

		$cfg = e107::getPlugConfig('raceevent');

		return array(
			'registration_id' => 0,
			'state'           => $state,
			'track_name'      => 'Sample track 10 km',
			'first_name'      => 'John',
			'last_name'       => 'Example',
			'email'           => 'john@example.com',
			'amount_due'      => 25.00,
			'variable_symbol' => '2026000123',
			'pay_token'       => self::FAKE_PAY_TOKEN,
			'payee_iban'      => (string) $cfg->get('payeeIban', ''),
			'payee_name'      => (string) $cfg->get('payeeName', ''),
			'payee_swift'     => (string) $cfg->get('payeeSwift', ''),
		);
	}
}
