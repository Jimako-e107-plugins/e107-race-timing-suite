<?php
/*
 * e107 website system
 *
 * racereg plugin - front-end templates (issue #24).
 *
 * Loaded via e107::getTemplate('racereg', null, '<key>'), which reads this file
 * and the $RACEREG_TEMPLATE array. Placeholders use {TOKENS} replaced directly
 * by signup.php (the values are built server-side with e107::getForm() and are
 * already toHTML()-escaped where they originate from stored input). Keeping the
 * markup here makes the page themeable without touching the controller.
 */

if (!defined('e107_INIT')) { exit; }

// --- Sign-up form ---------------------------------------------------------- //
// {MESSAGES}   - validation / gating messages block
// {FORM_ACTION}- form target URL
// {TOKEN}      - native e107 CSRF hidden field (e-token)
// {INTRO}      - short intro text
// {FIELDS}     - the generated field rows (track + PII)
// {HONEYPOT}   - hidden anti-spam field (must stay empty)
// {GDPR}       - required consent row
// {SUBMIT}     - submit button
$RACEREG_TEMPLATE['signup_form'] = '
<div class="racereg-signup">
	{MESSAGES}
	<form method="post" action="{FORM_ACTION}" class="form-horizontal" autocomplete="on">
		{TOKEN}
		<p class="racereg-intro">{INTRO}</p>
		<fieldset>
			<div class="row">
				{FIELDS}
			</div>
		</fieldset>
		{HONEYPOT}
		{GDPR}
		<div class="form-group">
			<div class="col-sm-9 col-sm-offset-3">{SUBMIT}</div>
		</div>
	</form>
</div>
';

// --- A single field row (used by signup.php to build {FIELDS}) -------------- //
// Two-column layout: each field is a col-md-6 cell within the {FIELDS} row
// container (mirrors racers/registracia.php).
// {ROW_CLASS} - "has-error" when the field failed validation, else empty
// {LABEL}     - field label
// {REQ}       - " *" for required fields, else empty
// {INPUT}     - the form control
// {ERROR}     - inline error help text, else empty
$RACEREG_TEMPLATE['signup_row'] = '
<div class="form-group mb-3 col-md-6 {ROW_CLASS}">
	<label class="control-label form-label" for="{FIELD_ID}">{LABEL}{REQ}</label>
	{INPUT}{ERROR}
</div>
';

// --- Registration closed / out-of-window ----------------------------------- //
$RACEREG_TEMPLATE['signup_closed'] = '
<div class="racereg-signup-closed alert alert-warning">
	{MESSAGE}
</div>
';

// --- Confirmation ---------------------------------------------------------- //
// {STATE_MESSAGE} - start-list / substitute / pending result banner
// {SUMMARY}       - registrant + track summary table
// {PAYMENT}       - textual payment details (IBAN / amount / VS)
$RACEREG_TEMPLATE['signup_confirm'] = '
<div class="racereg-confirm">
	{STATE_MESSAGE}
	<h3>' . (defined('LAN_RACEREG_CONFIRM_SUMMARY') ? LAN_RACEREG_CONFIRM_SUMMARY : 'Summary') . '</h3>
	{SUMMARY}
	<h3>' . (defined('LAN_RACEREG_CONFIRM_PAYMENT') ? LAN_RACEREG_CONFIRM_PAYMENT : 'Payment details') . '</h3>
	{PAYMENT}
</div>
';
