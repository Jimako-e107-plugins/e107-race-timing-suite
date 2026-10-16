<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_stu.php
 *
*/

/*
 * Results (SUT) link screen with CSV/XLS buttons (docs/plugins/racereports.md,
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
 * SUT area: one row per track (link + CSV/XLS), via renderStuLink().
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

		// One panel, one row per track (SUT is per track only).
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
