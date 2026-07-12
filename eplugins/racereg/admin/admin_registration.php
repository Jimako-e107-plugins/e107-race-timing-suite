<?php
/*
 * e107 website system
 *
 * racereg plugin - "More info" admin page (read-only dispatcher mode).
 *
 * Controller-only mode in the shared dispatcher (admin/admin_menu.php): the page
 * body is returned by ListPage(), which the dispatcher wraps with the left admin
 * menu + chrome (header/footer). Reached via the 'reginfo/list' menu entry.
 *
 * For now it holds a single button linking the organizer to the public sign-up
 * form; it is the intended future home for registration help info and free-
 * capacity figures - add those sections in ListPage().
 *
 * SECURITY: admin-only (dispatcher auth.php + getperms('P')). The link target is
 * built by e107::url() (safe); no user input, no SQL. target="_blank" carries
 * rel="noopener noreferrer". Caption / label come from LAN constants.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu / perms / LAN

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racereg_reginfo_ui extends e_admin_controller
{
	/**
	 * "More info" landing body. Returns the HTML; the dispatcher renders it
	 * inside the admin menu + chrome (same mechanism as
	 * racereg_regtracks_ui::ListPage()).
	 */
	public function ListPage()
	{
		$tp = e107::getParser();

		// Existing: link to the public sign-up form (/prihlaska/ -> signup.php).
		$url = e107::url('racereg', 'signup');
		$btn = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary">'
			. LAN_RACEREG_REG_FORM_LINK . '</a>';

		$text = $btn;

		// Admin-only inert preview of the confirmation page. Each link opens the real
		// front-end confirm render (real theme + QR) in a NEW TAB via
		// signup.php?preview_confirm=<state>. State keys mirror racereg_signup::STATE_*
		// on purpose (kept as literals so this admin page stays decoupled; the
		// authoritative whitelist lives in racereg_preview::build()). The URL is built
		// from e_PLUGIN_ABS so it works regardless of the plugin directory name.
		$base   = e_PLUGIN_ABS . 'racereg/signup.php?preview_confirm=';
		$states = array(
			'startlist'  => LAN_RACEREG_PREVIEW_STARTLIST,
			'substitute' => LAN_RACEREG_PREVIEW_SUBSTITUTE,
			'pending'    => LAN_RACEREG_PREVIEW_PENDING,
		);

		$links = '';
		foreach ($states as $key => $label)
		{
			$href   = $tp->toAttribute($base . $key);
			$links .= '<a href="' . $href . '" target="_blank" rel="noopener noreferrer" '
				. 'class="btn btn-default">' . $label . '</a> ';
		}

		// The QR is skipped by racereg_payment_view::renderQr() when the payee IBAN
		// or name is missing; warn the admin so the preview doesn't look broken.
		$cfg       = e107::getPlugConfig('raceevent');
		$payeeIban = (string) $cfg->get('payeeIban', '');
		$payeeName = (string) $cfg->get('payeeName', '');

		$warn = '';
		if ($payeeIban === '' || $payeeName === '')
		{
			$warn = '<div class="alert alert-warning">' . LAN_RACEREG_PREVIEW_NOQR . '</div>';
		}

		$preview = '<p class="text-muted">' . LAN_RACEREG_PREVIEW_INFO . '</p>'
			. $warn
			. '<div class="btn-group" role="group">' . $links . '</div>';

		$text .= e107::getRender()->tablerender(LAN_RACEREG_PREVIEW_TITLE, $preview, 'default', true);

		return e107::getRender()->tablerender(LAN_RACEREG_REG_INFO, $text, 'default', true);
	}
}


new racereg_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
