<?php
/*
 * e107 website system
 *
 * racers plugin - REGISTRÁCIA NA MIESTE (on-site registration reach-link) admin screen.
 *
 * ONE output = ONE admin file, mirroring admin_racerlist.php: a single info/link screen.
 * The on-site registration info used to clutter the prefs page help box (admin_config.php
 * renderHelp()); it now has its OWN clean admin item. The content is conditional on the
 * racers/manualinput pref:
 *   * manualinput == 1 -> the "enabled" caution text (LAN_RACERS_ADMIN_032) plus a NEW-TAB
 *     link button to racers' OWN 'registracia' e_url route (registracia.php), captioned
 *     LAN_RACERS_GLOBAL_029 ("Registrácia na mieste"). Built with e107::url() so SEF and
 *     non-SEF installs both resolve.
 *   * else -> the "not enabled" text (LAN_RACERS_ADMIN_031).
 * The message LAN constants are REUSED from the old renderHelp() block; only the menu-item
 * caption (LAN_RACERS_ADMIN_043) is new.
 *
 * Routing the racers way: this file is a self-contained admin ENTRY POINT (mirroring the
 * sibling area files and the racetrack archive fix): it bootstraps class2, requires the
 * shared dispatcher (admin_menu.php, which loads class2 + the admin LAN), gates on the
 * plugin's OWN admin permission, defines this area's controller + form classes, then runs
 * the page. racers' admin_menu.php routes the 'registration' mode straight here via its
 * $adminMenu 'url' => 'admin_registration.php'.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


/**
 * On-site-registration area: a single screen rendering the conditional registration
 * info/link, driven by the racers/manualinput pref. Extends e_admin_ui as a custom
 * controller screen (no DB-table CRUD).
 */
class racers_registration_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACERS_ADMIN_043;
	protected $pluginName  = 'racers';

	// Custom controller screen - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'registration';

	/**
	 * The on-site registration screen: when manual input is enabled, the caution text
	 * plus a new-tab link to the registracia front page; otherwise the "not enabled"
	 * notice. Reuses the same LAN constants the old renderHelp() block used.
	 *
	 * @return string
	 */
	public function registrationPage()
	{
		$tp = e107::getParser();

		if (e107::pref('racers', 'manualinput') == 1)
		{
			$url = e107::url('racers', 'registracia');

			$body = "<p>" . $tp->toHTML(LAN_RACERS_ADMIN_032, false) . "</p>"
				. "<a class='btn btn-danger' href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
				. $tp->toHTML(LAN_RACERS_GLOBAL_029, false) . "</a>";
		}
		else
		{
			$body = "<p>" . $tp->toHTML(LAN_RACERS_ADMIN_031, false) . "</p>";
		}

		return "<div class='panel panel-default'>"
			. "<div class='panel-heading'>" . $tp->toHTML(LAN_RACERS_ADMIN_043, false) . "</div>"
			. "<div class='panel-body'>" . $body . "</div>"
			. "</div>";
	}
}


class racers_registration_form_ui extends e_admin_form_ui
{
}


new racers_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
