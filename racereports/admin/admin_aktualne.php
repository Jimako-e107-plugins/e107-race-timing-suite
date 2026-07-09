<?php
/*
 * e107 website system
 *
 * racereports plugin - AKTUALNE (full per-race results matrix) report area
 * (mode 'aktualne').
 *
 * ONE admin file per report area: this file owns the AKTUALNE screen only - ONE
 * screen listing one link per race to the FULL per-race results matrix
 * (report_aktualne.php?p=race_id), built natively via the shared base's
 * renderAktualneLink(). Exposed the SAME way as the online/point/stu screens.
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base, then gate the whole screen on the plugin's OWN admin permission
 * with getperms('P'). The dispatcher routes here for mode=aktualne (admin_menu.php
 * $adminMenu 'aktualne/aktualne' url points at this file).
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
 * AKTUALNE area: ONE screen listing one link per race to the FULL per-race results
 * matrix - one link per race to report_aktualne.php (?p selects a race by race_id,
 * legacy parity). Exposed natively the SAME way as the stu screen, sharing the
 * base's renderAktualneLink().
 */
class racereports_aktualne_ui extends racereports_reports_ui
{
	protected $defaultAction = 'aktualne';

	public function aktualnePage()
	{
		$tp    = e107::getParser();
		$races = $this->getRaces();

		if (empty($races))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_010, false) . "</div>";
		}

		// One panel, one link per race (aktualne is per-race only - the matrix
		// already lists every checkpoint as a column, so no sub-links).
		$items = '';
		foreach ($races as $race)
		{
			$items .= $this->renderAktualneLink(
				(int) $race['race_id'],
				(string) $race['race_name'],
				(string) $race['race_sef']
			);
		}

		return $this->panel($tp->toHTML(LAN_ADMIN_RACEREPORTS_101, false), $items);
	}
}


class racereports_aktualne_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
