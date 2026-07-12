<?php
/*
 * e107 website system
 *
 * raceevent base plugin - "Prehľad preteku" admin screen (event overview).
 *
 * Admin face of the cross-suite link directory. It renders the SAME output as
 * the public front page (raceevent/page_overview.php) by including the one
 * shared include (raceevent/includes/event_overview.php) - so the two stay
 * identical. The front HTML is plain classic links, so it sits fine inside the
 * admin chrome; this screen just tablerenders the include's returned HTML.
 *
 * This is a custom controller screen: the default action 'view' is served by
 * the controller method viewPage() (e107's admin action->method convention),
 * with an EMPTY $table/$pid (no DB-table CRUD), exactly like the plugin's
 * checklinks / prefs controllers. Bootstrap mirrors admin_checklinks.php.
 *
 * READ-ONLY: the shared include does db reads + link building only, no writes.
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
	protected $pluginTitle = LAN_RACEEVENT_PLUGIN;
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
