<?php
/*
 * e107 website system
 *
 * racereports plugin - FINISH (results) report area (mode 'finish').
 *
 * ONE admin file per report area: this file owns the finish-links screen only -
 * the all-races (komplet) link, plus per race the finish report links (all
 * categories + one per category tied to the race), built natively via the shared
 * base's renderFinishLinks(). Exposed the SAME way as the online/point/stu screens.
 *
 * NO export, NO DataTables here (the finish report needs neither).
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base, then gate the whole screen on the plugin's OWN admin permission
 * with getperms('P'). The dispatcher routes here for mode=finish (admin_menu.php
 * $adminMenu 'finish/finish' url points at this file).
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
 * Finish area: the all-races (komplet) results link, then per race the finish
 * report links (all categories + one per category) via renderFinishLinks().
 */
class racereports_finish_ui extends racereports_reports_ui
{
	protected $defaultAction = 'finish';

	public function finishPage()
	{
		$tp    = e107::getParser();
		$races = $this->getRaces();

		if (empty($races))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_010, false) . "</div>";
		}

		// All tracks on one page (r=overview, c=komplet) - renders every race in
		// turn with the site chrome suppressed. The FIRST param is 'overview' (the
		// all-tracks trigger); the SECOND param stays 'komplet' (= all categories).
		$overview = $this->linkItem(
			e107::url('racereports', 'finish', array(
				'race_sef'          => self::OVERVIEW,
				'race_category_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_082
		);
		$text = $this->panel(
			$tp->toHTML(LAN_ADMIN_RACEREPORTS_080, false),
			$overview
		);

		// Per race: the finish links (all categories + one per category).
		foreach ($races as $race)
		{
			$raceId  = (int) $race['race_id'];
			$raceSef = (string) $race['race_sef'];

			$text .= $this->panel(
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_ADMIN_RACEREPORTS_080, false),
				$this->renderFinishLinks($raceId, $raceSef)
			);
		}

		return $text;
	}
}


class racereports_finish_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
