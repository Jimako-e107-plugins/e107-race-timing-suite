<?php
/*
 * e107 website system
 *
 * racereg plugin - tokenized public payment page (issue #40).
 *
 * URL: racereg/pay/<token> (SEF: /platba/<token>/); the e_url regex forwards the
 * token to this script as ?t=<token>. Resolves the pay_token to
 * a registration and renders the SHARED payment view + PAY by square QR
 * (racereg_payment_view) so an applicant - e.g. a promoted substitute, or someone
 * paying later - can return to the QR without logging in. This is also the URL the
 * future notification module will send (notifications are out of scope here).
 *
 * SECURITY:
 * - The token is the only credential. It is unguessable (128-bit random, UNIQUE)
 *   and resolved by a PARAMETERISED db lookup (racereg_payment_view::resolveByToken
 *   strictly format-validates 32 hex chars, then queries escaped - never raw SQL).
 * - An invalid / unknown / missing token shows a GENERIC "not found" - no leak, no
 *   registration_id enumeration, no hint whether a token merely expired.
 * - A light per-session rate limit blunts brute-force scanning of the token space.
 * - PII MINIMISATION: this page is reachable by anyone holding the token, so it
 *   shows ONLY the payment essentials (payee / IBAN / amount / VS / QR) plus the
 *   applicant's own first name and the current paid state. No birth date, address,
 *   contact details, and no other registration.
 * - All output via $tp->toHTML(); the QR JSON island is encoded as on the
 *   confirmation page. The QR is display-only; amount_due + variable symbol stay
 *   server-side authoritative.
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

require_once(e_PLUGIN . 'racereg/includes/racereg_payment_view.php');
require_once(e_PLUGIN . 'racereg/includes/racereg_actions.php'); // derived paid state

e107::lan('racereg', '', true); // front strings: languages/<Language>/<Language>_front.php

$tp = e107::getParser();
$ns = e107::getRender();

/* -------------------------------------------------------------------------- *
 *  Light rate limit (per session): blunt brute-force scanning of the token
 *  space. A sliding 60s window; over the cap -> generic not-found, same as a
 *  bad token (so a scanner cannot tell the difference).
 * -------------------------------------------------------------------------- */
function racereg_pay_rate_ok()
{
	$max    = 20;   // requests allowed...
	$window = 60;   // ...per this many seconds
	$now    = time();

	if (session_status() !== PHP_SESSION_ACTIVE)
	{
		return true; // no session to track against - do not block
	}

	$hits = isset($_SESSION['racereg_pay_rl']) && is_array($_SESSION['racereg_pay_rl'])
		? $_SESSION['racereg_pay_rl'] : array();

	// Drop timestamps outside the window.
	$hits = array_filter($hits, function ($ts) use ($now, $window) {
		return ($now - (int) $ts) < $window;
	});

	$hits[] = $now;
	$_SESSION['racereg_pay_rl'] = $hits;

	return count($hits) <= $max;
}

/* -------------------------------------------------------------------------- *
 *  Resolve the token
 * -------------------------------------------------------------------------- */
$token = $tp->filter(varset($_GET['t'], '')); // strip control chars; format checked below
$reg   = false;

if (racereg_pay_rate_ok())
{
	$reg = racereg_payment_view::resolveByToken($token);
}

/* -------------------------------------------------------------------------- *
 *  Render
 * -------------------------------------------------------------------------- */
require_once(HEADERF);

if ($reg === false)
{
	// Generic not-found: identical for malformed, unknown and rate-limited tokens.
	$tmpl = e107::getTemplate('racereg', null, 'signup_closed');
	$text = str_replace('{MESSAGE}', LAN_RACEREG_PAY_NOT_FOUND, $tmpl);
	$ns->tablerender(LAN_RACEREG_PAY_DETAILS_TITLE, $text, 'racereg-pay');

	require_once(FOOTERF);
	exit;
}

// Vendored PAY by square QR bundle (local file only, no CDN). Same enqueue as the
// confirmation page.
e107::js('footer', e_PLUGIN_ABS . 'racereg/js/racereg-qr.bundle.js');

// Minimal identification only (PII minimisation): own first name + paid state.
$firstName = $tp->toHTML((string) varset($reg['first_name'], ''), false, 'defs');
$paidBadge = racereg_pay_paid_badge((int) racereg_actions::paidState(
	(int) $reg['registration_id'], (float) varset($reg['amount_due'], 0)
));

$head = "<p class='lead'>" . $tp->lanVars(LAN_RACEREG_PAY_GREETING, $firstName) . "</p>"
	. "<p>" . LAN_RACEREG_PAID_STATUS . ": " . $paidBadge . "</p>";

$payment = racereg_payment_view::render(racereg_payment_view::buildData($reg), $tp);

$ns->tablerender(
	LAN_RACEREG_PAY_DETAILS_TITLE,
	e107::getMessage()->render() . $head . $payment,
	'racereg-pay'
);

require_once(FOOTERF);
exit;


/* ========================================================================== *
 *  Helpers
 * ========================================================================== */

/**
 * Render the derived paid state (display only) as a Bootstrap label. Mirrors the
 * admin badge but uses the front LAN strings.
 *
 * @param int $state racereg_actions PAID_* code
 * @return string
 */
function racereg_pay_paid_badge($state)
{
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
