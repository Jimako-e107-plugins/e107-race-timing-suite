<?php
/*
 * e107 website system
 *
 * racers plugin - PREHĽAD PRETEKÁROV (racer overview reach-link) admin screen.
 *
 * ONE output = ONE admin file, mirroring racereports' one-screen-per-file structure.
 * This entry exists only so admins can REACH the racers/list front page (racers.php)
 * from the admin menu - it is a SINGLE link, not a per-track list. The link targets
 * racers' OWN 'index' e_url route (racers/list/ -> racers.php), built with e107::url()
 * so SEF and non-SEF installs both resolve, and opens in a NEW TAB.
 *
 * Why a controller screen and NOT the bare $adminMenu 'url' mechanism: the e107
 * dispatcher's $adminMenu 'url' renders a normal in-frame admin link with no per-item
 * target support, so pointing it at a FRONT SEF route would navigate the admin frame
 * away into the public page (jarring, no new tab). A tiny controller screen renders a
 * proper button that opens the overview in a new tab - the cleaner option the task
 * allows. It also keeps the racers way: NO 'url' on the menu item, so the mode is
 * served by the single admin entry (admin_config.php) like every other racers mode.
 *
 * Routing the racers way: this file is a self-contained admin ENTRY POINT (mirroring the
 * sibling area files and the racetrack archive fix): it bootstraps class2, requires the
 * shared dispatcher (admin_menu.php, which loads class2 + the admin LAN), gates on the
 * plugin's OWN admin permission, defines this area's controller + form classes, then runs
 * the page. racers' admin_menu.php routes the 'racerlist' mode straight here via its
 * $adminMenu 'url' => 'admin_racerlist.php'.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


/**
 * Racer-overview area: a single screen rendering ONE button link to the racers/list
 * front page (racers.php) via the 'index' e_url route. Extends e_admin_ui as a custom
 * controller screen (no DB-table CRUD).
 */
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
