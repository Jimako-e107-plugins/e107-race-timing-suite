<?php
/*
 * e107 website system
 *
 * racereg plugin - public sign-up page (issue #24).
 *
 * GET  -> render the registration form (track select limited to open tracks,
 *         PII fields per schema, required GDPR consent, honeypot, CSRF token).
 * POST -> verify CSRF + honeypot, then hand off to racereg_signup for server-side
 *         validation, gating, price freeze, placement, VS generation and storage,
 *         and render the confirmation page.
 *
 * SECURITY: this is untrusted public input and the heaviest PII in the suite.
 * - CSRF: native e107 token (e107::getForm()->token() renders it, works for
 *   guests via the cookie/JWT CSRF handler; e107::getSession()->checkFormToken()
 *   verifies it server-side).
 * - All input validated + stored in racereg_signup (via $tp->toDB() + the db
 *   class). All output here goes through $tp->toHTML() / attribute escaping.
 * - Price and variable symbol are resolved server-side only (never from POST).
 */

if (!defined('e107_INIT'))
{
	require_once(__DIR__ . '/../../class2.php');
}

require_once(e_PLUGIN . 'racereg/includes/racereg_signup.php');

e107::lan('racereg', '', true); // front strings: languages/<Language>/<Language>_front.php

$frm = e107::getForm();
$tp  = e107::getParser();
$ns  = e107::getRender();

$signup = new racereg_signup();

/* -------------------------------------------------------------------------- *
 *  POST handling
 * -------------------------------------------------------------------------- */
$result   = null;
$showForm = true;

if (!empty($_POST) && isset($_POST['racereg_submit']))
{
	$token = isset($_POST['e-token']) ? $_POST['e-token'] : '';

	if (!e107::getSession()->checkFormToken($token))
	{
		// CSRF failure - reject outright.
		e107::getMessage()->addError(LAN_RACEREG_ERR_CSRF);
	}
	elseif (trim((string) varset($_POST['website'], '')) !== '')
	{
		// Honeypot tripped: treat as spam. Do NOT create a record; keep the
		// message generic so a bot learns nothing.
		e107::getLog()->add('RACEREG_03', 'Sign-up rejected by honeypot', E_LOG_INFORMATIVE, '');
		e107::getMessage()->addError(LAN_RACEREG_ERR_SPAM);
	}
	else
	{
		$result = $signup->process($_POST);

		if ($result === false)
		{
			$errs = $signup->getErrors();
			// Surface a single top-level message; field-level errors render inline.
			e107::getMessage()->addError(LAN_RACEREG_ERR_FORM);
			if (isset($errs['window']))
			{
				e107::getMessage()->addError($errs['window']);
			}
			if (isset($errs['save']))
			{
				e107::getMessage()->addError($errs['save']);
			}
		}
		else
		{
			$showForm = false;
		}
	}
}

// Admin-only inert preview of the confirmation page (dev/QA aid). No DB write,
// no event trigger: build fake data and reuse the real confirm render path below
// so the preview is identical to a real submission. Guarded on the main admin.
if ($showForm && getperms('0') && isset($_GET['preview_confirm']))
{
	require_once(e_PLUGIN . 'racereg/includes/racereg_preview.php');
	$result   = racereg_preview::build($_GET['preview_confirm']);
	$showForm = false;
}

/* -------------------------------------------------------------------------- *
 *  Render
 * -------------------------------------------------------------------------- */
require_once(HEADERF);

if (!$showForm && is_array($result))
{
	// Load the vendored, committed PAY by square QR bundle (issue #25) in the
	// footer zone - native e107 asset mechanism, local file only (no CDN, no
	// runtime npm). Only enqueued on the confirmation page.
	e107::js('footer', e_PLUGIN_ABS . 'racereg/js/racereg-qr.bundle.js');

	$text    = racereg_render_confirm($result, $tp);
	$caption = LAN_RACEREG_CONFIRM_TITLE;
}
elseif (!$signup->isWindowOpen())
{
	$tmpl    = e107::getTemplate('racereg', null, 'signup_closed');
	$text    = str_replace('{MESSAGE}', LAN_RACEREG_ERR_WINDOW, $tmpl);
	$caption = LAN_RACEREG_SIGNUP_TITLE;
}
else
{
	$tracks = $signup->getOpenTracks();

	if (empty($tracks))
	{
		$tmpl    = e107::getTemplate('racereg', null, 'signup_closed');
		$text    = str_replace('{MESSAGE}', LAN_RACEREG_ERR_NOTRACKS, $tmpl);
		$caption = LAN_RACEREG_SIGNUP_TITLE;
	}
	else
	{
		$text    = racereg_render_form($tracks, $signup->getErrors(), $frm, $tp);
		$caption = LAN_RACEREG_SIGNUP_TITLE;
	}
}

$ns->tablerender($caption, e107::getMessage()->render() . $text, 'racereg-signup');

require_once(FOOTERF);
exit;


/* ========================================================================== *
 *  Render helpers
 * ========================================================================== */

/**
 * Build one form-group row from the signup_row template.
 */
function racereg_render_row($fieldId, $label, $inputHtml, $required, $errorMsg)
{
	$tmpl = e107::getTemplate('racereg', null, 'signup_row');

	$error = ($errorMsg !== '')
		? "<span class='help-block text-danger'>" . $errorMsg . "</span>"
		: '';

	return str_replace(
		array('{ROW_CLASS}', '{FIELD_ID}', '{LABEL}', '{REQ}', '{INPUT}', '{ERROR}'),
		array(($errorMsg !== '' ? 'has-error' : ''), $fieldId, $label, ($required ? ' *' : ''), $inputHtml, $error),
		$tmpl
	);
}

/**
 * Render the full sign-up form. Every field is rendered natively via
 * $frm->renderElement() from the $fields definition array below (the upstream
 * submitnews.php pattern: a `type` + `writeParms` map). Submitted values are
 * repopulated on validation error. $errors is field => message.
 */
function racereg_render_form($tracks, $errors, $frm, $tp)
{
	$err = function ($key) use ($errors)
	{
		return isset($errors[$key]) ? $errors[$key] : '';
	};

	// Option sources, read EXACTLY as racers/registracia.php does (the racers
	// plugin is read directly, never called into).
	//
	// Categories: read the race_category TABLE - guarded with isInstalled('racers')
	// so a missing table can't fatal when racers isn't installed. id 0 = "neurčené"
	// is the allowed default. The category field is OMITTED entirely (not broken)
	// when racers/categories don't exist.
	$racersInstalled = e107::isInstalled('racers');
	$categories      = array();
	if ($racersInstalled)
	{
		$cats           = e107::getDb()->retrieve('race_category', '*', true, true);
		$categories[0]  = LAN_RACEREG_CATEGORY_NONE; // "neurčené"
		if (is_array($cats))
		{
			foreach ($cats as $c)
			{
				$categories[(int) $c['race_category_id']] = $c['race_category_name'];
			}
		}
	}

	// Nationalities: a PREF (safe even if empty) - leave the dropdown empty if absent.
	$states  = (string) e107::pref('racers', 'states');
	$natOpts = ($states !== '') ? array_combine(explode(',', $states), explode(',', $states)) : array();

	// Field definitions: key => [label, type, required, writeParms]. Rendered
	// through renderElement(), which escapes the (raw) value itself - so the
	// repopulated $_POST value is passed RAW (no toDB/toAttribute here, exactly
	// as submitnews.php passes raw stored values).
	$fields = array(
		// Track select (open tracks only). dropdown reads its options from
		// writeParms['optArray']; the blank default forces a choice.
		'track_id' => array(
			'label'      => LAN_RACEREG_TRACK,
			'type'       => 'dropdown',
			'required'   => true,
			'writeParms' => array(
				'optArray'     => $tracks,
				'default'      => LAN_RACEREG_SELECT_TRACK,
				'defaultValue' => '',
				'required'     => 1,
				'class'        => 'form-control',
			),
		),
		// Category - second field, immediately after Track. Optional; default 0
		// ("neurčené"). Unset below when racers/categories aren't installed, so it
		// is absent (not broken) rather than rendering an empty/invalid dropdown.
		'category_id' => array(
			'label'      => LAN_RACEREG_CATEGORY,
			'type'       => 'dropdown',
			'required'   => false,
			'writeParms' => array(
				'optArray'     => $categories,
				'defaultValue' => 0,
				'class'        => 'form-control',
			),
		),
		'first_name' => array(
			'label' => LAN_RACEREG_FIRST_NAME, 'type' => 'text', 'required' => true,
			'writeParms' => array('maxlength' => 100, 'class' => 'form-control'),
		),
		'last_name' => array(
			'label' => LAN_RACEREG_LAST_NAME, 'type' => 'text', 'required' => true,
			'writeParms' => array('maxlength' => 100, 'class' => 'form-control'),
		),
		'email' => array(
			'label' => LAN_RACEREG_EMAIL, 'type' => 'email', 'required' => true,
			'writeParms' => array('maxlength' => 255, 'class' => 'form-control'),
		),
		// Birth date - native e107 datepicker (NOT type=date). Display format
		// follows the `inputdate` pref (SK, centrally configured), and the field
		// posts back a Unix timestamp by default (datepicker useUnix).
		'birth_date' => array(
			'label' => LAN_RACEREG_BIRTH_DATE, 'type' => 'datestamp', 'required' => true,
			'writeParms' => array('mode' => 'date'),
		),
		'street' => array(
			'label' => LAN_RACEREG_STREET, 'type' => 'text',
			'writeParms' => array('maxlength' => 255, 'class' => 'form-control'),
		),
		'city' => array(
			'label' => LAN_RACEREG_CITY, 'type' => 'text',
			'writeParms' => array('maxlength' => 100, 'class' => 'form-control'),
		),
		'postal_code' => array(
			'label' => LAN_RACEREG_POSTAL, 'type' => 'text',
			'writeParms' => array('maxlength' => 20, 'class' => 'form-control'),
		),
		// Kept as free text (not the native `country` type) so the stored value
		// stays a name string, not an ISO code - no change to stored data.
		'country' => array(
			'label' => LAN_RACEREG_COUNTRY, 'type' => 'text',
			'writeParms' => array('maxlength' => 100, 'class' => 'form-control'),
		),
		// Nationality - optional dropdown from the racers/states pref; renders an
		// empty dropdown if the pref is absent (left as-is, per decision).
		'nationality' => array(
			'label' => LAN_RACEREG_NATIONALITY, 'type' => 'dropdown',
			'writeParms' => array('optArray' => $natOpts, 'class' => 'form-control'),
		),
		// Local racer - optional toggle (mirrors registracia's "Miestny").
		'local' => array(
			'label' => LAN_RACEREG_LOCAL, 'type' => 'checkbox',
			'writeParms' => array('value' => 1),
		),
		'phone' => array(
			'label' => LAN_RACEREG_PHONE, 'type' => 'text',
			'writeParms' => array('maxlength' => 50, 'class' => 'form-control'),
		),
		'club' => array(
			'label' => LAN_RACEREG_CLUB, 'type' => 'text',
			'writeParms' => array('maxlength' => 255, 'class' => 'form-control'),
		),
	);

	// Category is a guarded soft-read of the racers plugin: when racers (and its
	// race_category table) isn't installed, drop the field entirely.
	if (!$racersInstalled)
	{
		unset($fields['category_id']);
	}

	$rows = '';
	foreach ($fields as $key => $fld)
	{
		// Raw posted value for repopulation (renderElement escapes it). For
		// birth_date this is the datepicker's Unix timestamp.
		$value  = isset($_POST[$key]) ? $_POST[$key] : '';
		$widget = $frm->renderElement($key, $value, $fld);

		$rows .= racereg_render_row(
			$frm->name2id($key), $fld['label'], $widget,
			!empty($fld['required']), $err($key)
		);
	}

	// Honeypot: hidden from humans, attractive to bots. Must stay empty.
	$honeypot = "<div class='racereg-hp' style='position:absolute;left:-9999px;top:-9999px;' aria-hidden='true'>"
		. "<label>Website</label>"
		. "<input type='text' name='website' value='' tabindex='-1' autocomplete='off' />"
		. "</div>";

	// GDPR consent (required).
	$gdprChecked = !empty($_POST['gdpr_consent']);
	$gdprErr     = $err('gdpr_consent');
	$gdpr = "<div class='form-group " . ($gdprErr !== '' ? 'has-error' : '') . "'>"
		. "<div class='col-sm-9 col-sm-offset-3'>"
		. "<label class='checkbox-inline'>"
		. $frm->checkbox('gdpr_consent', 1, $gdprChecked) . ' ' . LAN_RACEREG_GDPR_LABEL
		. "</label>"
		. ($gdprErr !== '' ? "<span class='help-block text-danger'>" . $gdprErr . "</span>" : '')
		. "</div></div>";

	$tmpl = e107::getTemplate('racereg', null, 'signup_form');

	return str_replace(
		array('{MESSAGES}', '{FORM_ACTION}', '{TOKEN}', '{INTRO}', '{FIELDS}', '{HONEYPOT}', '{GDPR}', '{SUBMIT}'),
		array(
			'',                                   // messages rendered by tablerender wrapper
			$tp->toAttribute(e_REQUEST_URI),
			$frm->token(),
			LAN_RACEREG_INTRO,
			$rows,
			$honeypot,
			$gdpr,
			$frm->submit('racereg_submit', LAN_RACEREG_SUBMIT),
		),
		$tmpl
	);
}

/**
 * Render the confirmation page from a process() result. All stored values are
 * output through toHTML(); money is formatted. The payment block (table + PAY by
 * square QR) is produced by the shared renderer (racereg_payment_view, issue #40)
 * so the confirmation, the admin "Show payment details" action and the tokenized
 * public pay page emit identical markup. A short note + the applicant's tokenized
 * pay link is appended so they can save it / pay later.
 */
function racereg_render_confirm($r, $tp)
{
	switch ($r['state'])
	{
		case racereg_signup::STATE_STARTLIST:
			$stateMsg = "<div class='alert alert-success'>" . cl(LAN_RACEREG_STATE_STARTLIST) . "</div>";
			break;
		case racereg_signup::STATE_SUBSTITUTE:
			$stateMsg = "<div class='alert alert-warning'>" . cl(LAN_RACEREG_STATE_SUBSTITUTE) . "</div>";
			break;
		default:
			$stateMsg = "<div class='alert alert-info'>" . cl(LAN_RACEREG_STATE_PENDING) . "</div>";
			break;
	}

	$name = $tp->toHTML($r['first_name'], false, 'defs') . ' ' . $tp->toHTML($r['last_name'], false, 'defs');

	$summary = "<table class='table table-striped'>"
		. "<tr><th>" . LAN_RACEREG_FIRST_NAME . " / " . LAN_RACEREG_LAST_NAME . "</th><td>" . $name . "</td></tr>"
		. "<tr><th>" . LAN_RACEREG_TRACK . "</th><td>" . $tp->toHTML($r['track_name'], false, 'defs') . "</td></tr>"
		. "<tr><th>" . LAN_RACEREG_EMAIL . "</th><td>" . $tp->toHTML($r['email'], false, 'defs') . "</td></tr>"
		. "</table>";

	// Shared payment block (table + PAY by square QR island) - issue #40.
	$payment = racereg_payment_view::render($r, $tp);

	// Tokenized pay link so the applicant can save it / return to pay later (#40).
	$payUrl = isset($r['pay_token']) ? racereg_payment_view::payUrl($r['pay_token']) : '';
	if ($payUrl !== '')
	{
		$payment .= "<p class='racereg-pay-link text-muted'>"
			. LAN_RACEREG_PAY_LINK_NOTE
			. "<br><a href='" . $payUrl . "'>" . $payUrl . "</a>"
			. "</p>";
	}

	$tmpl = e107::getTemplate('racereg', null, 'signup_confirm');

	return str_replace(
		array('{STATE_MESSAGE}', '{SUMMARY}', '{PAYMENT}'),
		array($stateMsg, $summary, $payment),
		$tmpl
	);
}
