<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - admin/admin_registration.php
 *
*/

/*
 * On-site registration screen (docs/plugins/racers.md, "On-site registration"):
 * content depends on the manualinput pref.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racers_registration_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACERS_ADMIN_043;
	protected $pluginName  = 'racers';

	// Custom controller screen - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'registration';

	/**
	 * Caution text + new-tab link to the registracia page when manualinput is 1,
	 * otherwise the "not enabled" notice.
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
