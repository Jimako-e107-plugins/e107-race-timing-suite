<?php
/*
 * e107 website system
 *
 * racereg plugin - shared payment-details view + PAY by square QR (issue #40).
 *
 * Single source of truth for the payment block that used to live inline in
 * signup.php (issue #24/#25). It assembles the NON-SECRET payment data for one
 * registration - payee IBAN / name / SWIFT from the raceevent prefs, amount_due
 * and the variable symbol from the registration - and returns the payment table
 * + the hidden QR JSON island + mount (the same structure / markup as before).
 * Three callers reuse it: the confirmation page (#24 refactor), the admin
 * "Show payment details" action and the tokenized public pay page. Every caller
 * enqueues the vendored bundle racereg/js/racereg-qr.bundle.js via e107::js().
 *
 * It also owns the pay_token (#40): an unguessable 128-bit random token, UNIQUE
 * and indexed (racereg_sql.php), used to reach the public pay page. The token is
 * NEVER derived from registration_id. It is generated on both create paths and
 * backfilled lazily for legacy rows (ensureToken()).
 *
 * SECURITY:
 * - resolveByToken() is parameterised: the token is strictly format-validated
 *   (32 lowercase hex chars) and passed through the e107 db class escaped - it is
 *   never concatenated raw into SQL. An invalid / unknown token returns false so
 *   the caller can show a generic "not found" (no leak, no id enumeration).
 * - ensureToken() stores via the db class array API with an explicit _FIELD_TYPES
 *   map (the e107 equivalent of parameter binding) - no raw SQL.
 * - All output goes through $tp->toHTML(); the QR JSON island is encoded with the
 *   hex flags so a name / note can never break out of the <script> island.
 * - The QR is display-only: amount_due + variable symbol stay server-side
 *   authoritative; client tampering changes nothing stored.
 */

if (!defined('e107_INIT')) { exit; }

class racereg_payment_view
{
	/** Registrations table (without the site prefix). */
	const TABLE = 'racereg_registration';

	/**
	 * Generate a unique, unguessable payment token: 16 random bytes (128-bit) as
	 * 32 lowercase hex chars. NOT derived from registration_id. Uniqueness is
	 * checked through the db class (escaped) and also guaranteed by the UNIQUE
	 * index; the loop is bounded so a pathological collision streak cannot hang.
	 *
	 * @return string
	 */
	public static function generateToken()
	{
		$db = e107::getDb();

		$attempts = 0;
		do
		{
			$token = bin2hex(random_bytes(16)); // 128-bit, 32 hex chars
			$attempts++;

			$taken = (int) $db->count(self::TABLE, '(*)', "pay_token='" . $db->escape($token) . "'");
		}
		while ($taken > 0 && $attempts < 50);

		return $token;
	}

	/**
	 * Return a registration's pay_token, generating + storing one (parameterised
	 * update) if it is missing. Shared by the admin create path (afterCreate) and
	 * the lazy backfill when the admin opens payment details for a legacy row.
	 *
	 * @param int    $regId
	 * @param string $existing the row's current pay_token (avoids a re-read)
	 * @return string the resolved token ('' only if $regId is invalid)
	 */
	public static function ensureToken($regId, $existing = '')
	{
		$regId = (int) $regId;
		if ($regId < 1)
		{
			return '';
		}

		$existing = trim((string) $existing);
		if ($existing !== '')
		{
			return $existing;
		}

		$token = self::generateToken();

		e107::getDb()->update(self::TABLE, array(
			'data'         => array('pay_token' => $token),
			'_FIELD_TYPES' => array('pay_token' => 'escape'),
			'WHERE'        => 'registration_id = ' . $regId,
		));

		e107::getLog()->add('RACEREG_20',
			'Backfilled pay_token for reg #' . $regId, E_LOG_INFORMATIVE, '');

		return $token;
	}

	/**
	 * Resolve a non-deleted registration row by its pay_token, or false.
	 *
	 * The token is strictly format-validated (32 lowercase hex chars) BEFORE the
	 * lookup, then passed escaped through the db class - it is never concatenated
	 * raw into SQL. A malformed / unknown token returns false (the caller shows a
	 * generic "not found", so there is no leak and no registration_id enumeration).
	 *
	 * @param string $token
	 * @return array|false
	 */
	public static function resolveByToken($token)
	{
		$token = (string) $token;

		// Reject anything that is not exactly our token shape - this alone makes
		// SQL injection impossible; the escape below is defence in depth.
		if (!preg_match('/^[a-f0-9]{32}$/', $token))
		{
			return false;
		}

		$db  = e107::getDb();
		$row = $db->retrieve(self::TABLE, '*',
			"pay_token='" . $db->escape($token) . "' AND deleted_at IS NULL");

		return empty($row) ? false : $row;
	}

	/**
	 * Build the NON-SECRET payment data array for a registration row: payee fields
	 * from the raceevent prefs, amount_due + variable symbol from the row. This is
	 * the same shape the #24 confirmation result already used, so render() works
	 * for all three callers unchanged.
	 *
	 * @param array $reg a racereg_registration row
	 * @return array
	 */
	public static function buildData(array $reg)
	{
		$cfg = e107::getPlugConfig('raceevent');

		return array(
			'payee_name'      => (string) $cfg->get('payeeName', ''),
			'payee_iban'      => (string) $cfg->get('payeeIban', ''),
			'payee_swift'     => (string) $cfg->get('payeeSwift', ''),
			'amount_due'      => (float) varset($reg['amount_due'], 0),
			'variable_symbol' => (string) varset($reg['variable_symbol'], ''),
		);
	}

	/**
	 * Render the payment block: the payee / IBAN / SWIFT / amount / VS table, the
	 * reference note, and the PAY by square QR island. Markup is byte-identical to
	 * the inline #24 confirmation copy this replaces, so the confirmation page
	 * output is unchanged. The caller must enqueue the QR bundle via e107::js().
	 *
	 * @param array       $r  non-secret payment data (buildData() or the #24 result)
	 * @param object|null $tp e107 parser (defaults to e107::getParser())
	 * @return string
	 */
	public static function render($r, $tp = null)
	{
		if ($tp === null)
		{
			$tp = e107::getParser();
		}

		$amount = number_format((float) $r['amount_due'], 2, '.', ' ') . ' €';

		$ibanRow = ((string) $r['payee_iban'] !== '')
			? "<tr><th>" . LAN_RACEREG_PAY_IBAN . "</th><td>" . $tp->toHTML($r['payee_iban'], false, 'defs') . "</td></tr>"
			: "<tr><td colspan='2'><em>" . LAN_RACEREG_PAY_NO_IBAN . "</em></td></tr>";

		$payeeRow = ((string) $r['payee_name'] !== '')
			? "<tr><th>" . LAN_RACEREG_PAY_PAYEE . "</th><td>" . $tp->toHTML($r['payee_name'], false, 'defs') . "</td></tr>"
			: '';

		$swift = isset($r['payee_swift']) ? (string) $r['payee_swift'] : '';
		$swiftRow = ($swift !== '')
			? "<tr><th>" . LAN_RACEREG_PAY_SWIFT . "</th><td>" . $tp->toHTML($swift, false, 'defs') . "</td></tr>"
			: '';

		return "<table class='table table-striped'>"
			. $payeeRow
			. $ibanRow
			. $swiftRow
			. "<tr><th>" . LAN_RACEREG_AMOUNT_DUE . "</th><td>" . $amount . "</td></tr>"
			. "<tr><th>" . LAN_RACEREG_VS . "</th><td>" . $tp->toHTML($r['variable_symbol'], false, 'defs') . "</td></tr>"
			. "</table>"
			. "<p class='text-muted'>" . LAN_RACEREG_PAY_NOTE_TEXT . "</p>"
			. self::renderQr($r);
	}

	/**
	 * PAY by square QR block. Emits the NON-SECRET payment data as a safely-encoded
	 * JSON island plus an empty mount + caption, all initially hidden. The vendored
	 * client bundle (racereg/js/racereg-qr.bundle.js) reads the JSON, builds the
	 * bysquare model, encodes it (pure-JS LZMA, no xz) and draws the QR into the
	 * mount, then reveals the block. If JS is off or anything fails, the block
	 * stays hidden and the textual payment details remain the fallback. The QR is
	 * generated on the fly and never stored.
	 *
	 * @param array $r non-secret payment data
	 * @return string
	 */
	public static function renderQr($r)
	{
		// No payee account configured -> no QR (textual fallback already covers it).
		if ((string) $r['payee_iban'] === '')
		{
			return '';
		}

		// bysquare requires a beneficiary name: encode() throws without it. Guard it
		// here (symmetric with the empty-IBAN guard above) so we never emit an island
		// the client must fail to encode - textual fallback covers it. The raceevent
		// prefs save (beforePrefsSave) enforces a name whenever an IBAN is set, so in
		// practice this only catches legacy/inconsistent prefs.
		if ((string) $r['payee_name'] === '')
		{
			return '';
		}

		$payload = array(
			'iban'           => (string) $r['payee_iban'],
			'bic'            => isset($r['payee_swift']) ? (string) $r['payee_swift'] : '',
			'beneficiary'    => (string) $r['payee_name'],
			// Dot-decimal, machine form (display formatting is separate).
			'amount'         => number_format((float) $r['amount_due'], 2, '.', ''),
			'variableSymbol' => (string) $r['variable_symbol'],
			'currency'       => 'EUR',
		);

		// Hex flags neutralise <, >, &, ', " (so a name/note can never break out of
		// the <script> island or inject markup); JSON_UNESCAPED_UNICODE keeps
		// diacritics readable (bysquare deburrs them client-side anyway).
		$json = json_encode(
			$payload,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
		);

		if ($json === false)
		{
			return '';
		}

		return "<div id='racereg-qr' class='racereg-qr' hidden>"
			. "<h4>" . LAN_RACEREG_QR_TITLE . "</h4>"
			. "<p class='text-muted'>" . LAN_RACEREG_QR_HINT . "</p>"
			. "<div id='racereg-qr-mount' class='racereg-qr-mount' style='max-width:260px'></div>"
			. "<script type='application/json' id='racereg-qr-data'>" . $json . "</script>"
			. "</div>";
	}

	/**
	 * Build the tokenized public pay URL for a token (issue #40). Native SEF route
	 * racereg/pay with the token in the PATH: the {token} sef placeholder is filled
	 * from the data array, producing /platba/<token>/, which the e_url regex maps
	 * back to pay.php?t=<token>. The result is already htmlspecialchars-escaped by
	 * e107::url(), safe to drop straight into an href.
	 *
	 * @param string $token
	 * @return string '' when no token
	 */
	public static function payUrl($token)
	{
		$token = trim((string) $token);
		if ($token === '')
		{
			return '';
		}

		return e107::url('racereg', 'pay', array('token' => $token));
	}
}
