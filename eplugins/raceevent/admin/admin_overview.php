<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - admin/admin_overview.php
 *
*/

/*
 * Admin event overview. Renders the shared include includes/event_overview.php
 * (the same output as the public page_overview.php) inside the admin chrome.
 * Custom controller screen: action 'view' -> viewPage(), no DB table.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


class raceevent_overview_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEEVENT_001;
	protected $pluginName  = 'raceevent';

	// Custom controller screen - no DB table.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'view';


	/**
	 * Default screen. Includes the shared event-overview include and tablerenders
	 * its returned HTML - the identical output the front page shows.
	 *
	 * @return string
	 */
	public function viewPage()
	{
		require_once(e_PLUGIN . 'raceevent/includes/event_overview.php');

		$text = raceevent_event_overview();

		return e107::getRender()->tablerender(LAN_RACEEVENT_OV_CAPTION, $text, 'raceevent-overview', true);
	}


	public function renderHelp()
	{
		$caption = LAN_HELP;
		$text    = LAN_RACEEVENT_OV_HELP;

		return array('caption' => $caption, 'text' => $text);
	}
}


class raceevent_overview_form_ui extends e_admin_form_ui
{
}


new raceevent_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
