<?php
/*
 * e107 website system
 *
 * racereports plugin - Checkpoint-times report area (mode 'point').
 *
 * ONE admin file per report area: this file owns the checkpoint-times screen
 * only - per race, the per-checkpoint report links (komplet + one per checkpoint
 * tied to the race, skipping start/finish), built natively via the shared base's
 * renderCheckpointLinks(). Exposed the SAME way as the online screen.
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base, then gate the whole screen on the plugin's OWN admin permission
 * with getperms('P'). The dispatcher routes here for mode=point (admin_menu.php
 * $adminMenu 'point/point' url points at this file).
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
 * Checkpoint area: per race, the checkpoint-time report links only. Exposed
 * natively the SAME way as the Online area (its own dispatcher mode/screen,
 * sharing the base's renderCheckpointLinks()).
 */
class racereports_point_ui extends racereports_reports_ui
{
	protected $defaultAction = 'point';

	public function pointPage()
	{
		$tp    = e107::getParser();
		$races = $this->getRaces();

		if (empty($races))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_010, false) . "</div>";
		}

		$text = '';

		foreach ($races as $race)
		{
			$raceId  = (int) $race['race_id'];
			$raceSef = (string) $race['race_sef'];

			$text .= $this->panel(
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_ADMIN_RACEREPORTS_030, false),
				$this->renderCheckpointLinks($raceId, $raceSef)
			);
		}

		return $text;
	}
}


class racereports_point_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
