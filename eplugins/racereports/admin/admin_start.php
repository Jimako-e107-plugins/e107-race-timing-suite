<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_start.php
 *
*/

/*
 * Start list link screen with CSV/XLS buttons (docs/plugins/racereports.md,
 * "Report screens").
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
 * Start area: the all-tracks link, then per race renderStartLinks().
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

		// All tracks on one page: r=overview (first param), c=komplet. No export.
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
