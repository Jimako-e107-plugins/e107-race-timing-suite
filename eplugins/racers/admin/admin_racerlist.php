<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - admin/admin_racerlist.php
 *
*/

/*
 * Racer overview screen (docs/plugins/racers.md, "Racer overview"): one button
 * opening the racer list (e_url 'index') in a new tab.
 *
 * A controller screen, not a bare $adminMenu 'url' to the front route: the
 * dispatcher's 'url' has no per-item target, so the admin frame would navigate
 * away into the public page instead of opening a new tab.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class racers_racerlist_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACERS_ADMIN_035;
	protected $pluginName  = 'racers';

	// Custom controller screen - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'racerlist';

	/**
	 * The single reach-link to the racers/list front page, opened in a new tab.
	 *
	 * @return string
	 */
	public function racerlistPage()
	{
		$tp  = e107::getParser();
		$url = e107::url('racers', 'index');

		$button = "<a class='btn btn-primary' href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. "<i class='fas fa-users'></i> " . $tp->toHTML(LAN_RACERS_ADMIN_042, false) . "</a>";

		return "<div class='panel panel-default'>"
			. "<div class='panel-heading'>" . $tp->toHTML(LAN_RACERS_ADMIN_035, false) . "</div>"
			. "<div class='panel-body'>" . $button . "</div>"
			. "</div>";
	}
}


class racers_racerlist_form_ui extends e_admin_form_ui
{
}


new racers_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
