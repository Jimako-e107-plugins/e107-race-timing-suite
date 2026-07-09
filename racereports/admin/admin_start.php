<?php
/*
 * e107 website system
 *
 * racereports plugin - START (start-point standings) report area (mode 'start').
 *
 * ONE admin file per report area: this file owns the start-links screen only - the
 * all-races (komplet) link, plus per race the start report links (all categories +
 * one per category tied to the race), built natively via the shared base's
 * renderStartLinks(). Exposed the SAME way as the online/point/stu/finish screens.
 *
 * NO export, NO DataTables here (the start report needs neither, like finish).
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base, then gate the whole screen on the plugin's OWN admin permission
 * with getperms('P'). The dispatcher routes here for mode=start (admin_menu.php
 * $adminMenu 'start/start' url points at this file).
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
 * Start area: the all-races (komplet) start-list link, then per race the start
 * report links (all categories + one per category) via renderStartLinks().
 */
class racereports_start_ui extends racereports_reports_ui
{
	protected $defaultAction = 'start';

	public function startPage()
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
			e107::url('racereports', 'start', array(
				'race_sef'          => self::OVERVIEW,
				'race_category_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_092
		);
		$text = $this->panel(
			$tp->toHTML(LAN_ADMIN_RACEREPORTS_090, false),
			$overview
		);

		// Per race: the start links (all categories + one per category).
		foreach ($races as $race)
		{
			$raceId  = (int) $race['race_id'];
			$raceSef = (string) $race['race_sef'];

			$text .= $this->panel(
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_ADMIN_RACEREPORTS_090, false),
				$this->renderStartLinks($raceId, $raceSef)
			);
		}

		return $text;
	}
}


class racereports_start_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
