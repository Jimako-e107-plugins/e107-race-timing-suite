<?php
/*
 * e107 website system
 *
 * racereports plugin - SUT (per-track finishers) report area (mode 'stu').
 *
 * ONE admin file per report area: this file owns the SUT screen only - ONE
 * screen listing one link per race(track) to the per-track finishers-only
 * results list (report_stu.php), built natively via the shared base's
 * renderStuLink(). Exposed the SAME way as the online/point screens.
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base, then gate the whole screen on the plugin's OWN admin permission
 * with getperms('P'). The dispatcher routes here for mode=stu (admin_menu.php
 * $adminMenu 'stu/stu' url points at this file).
 */

require_once("../../../class2.php");

// LAN first: admin_menu.php's captions and the controller's $pluginTitle
// reference these constants at class-definition time.
e107::lan('racereports', true, true);

require_once("admin_menu.php");      // shared dispatcher / menu (pure class def)
require_once("admin_report_ui.php"); // shared report-link base (pure class def)

if (!getperms('P'))
{
	exit;
}


/**
 * SUT area: ONE screen listing per-track finishers-only results links - one link
 * per race(track) to report_stu.php. Exposed natively the SAME way as the
 * online/point screens (its own dispatcher mode/screen), sharing the base's
 * renderStuLink().
 */
class racereports_stu_ui extends racereports_reports_ui
{
	protected $defaultAction = 'stu';

	public function stuPage()
	{
		$tp    = e107::getParser();
		$races = $this->getRaces();

		if (empty($races))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_010, false) . "</div>";
		}

		// One panel, one link per track (stu is per-track only - no komplet /
		// category / checkpoint sub-links).
		$items = '';
		foreach ($races as $race)
		{
			$items .= $this->renderStuLink(
				(int) $race['race_id'],
				(string) $race['race_name'],
				(string) $race['race_sef']
			);
		}

		return $this->panel($tp->toHTML(LAN_ADMIN_RACEREPORTS_060, false), $items);
	}
}


class racereports_stu_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
