<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_number.php
 *
*/

/*
 * Racer progression link screen (docs/plugins/racereports.md, "Report screens").
 */

require_once("../../../class2.php");

// LAN first: admin_menu.php's captions and the controller's $pluginTitle
// reference these constants at class-definition time.
e107::lan('racereports', true, true);

require_once("admin_menu.php");      // shared dispatcher / menu (pure class def)
require_once("admin_report_ui.php"); // shared report-link base (pure class def)
require_once(e_PLUGIN . "racereports/includes/race_report.php"); // getRacerName

if (!getperms('P'))
{
	exit;
}


/**
 * Racer progression area: one link per bib; the name comes from the shared
 * race_report::getRacerName(), the row from renderNumberLink().
 */
class racereports_number_ui extends racereports_reports_ui
{
	protected $defaultAction = 'number';

	public function numberPage()
	{
		$tp     = e107::getParser();
		$report = new race_report();
		$racers = $this->getRacersByNumber();

		if (empty($racers))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_010, false) . "</div>";
		}

		// One panel, one navigation link per bib (no komplet / category / checkpoint
		// sub-links - the bib alone selects the racer and therefore the track).
		$items = '';
		foreach ($racers as $racer)
		{
			$items .= $this->renderNumberLink(
				(string) $racer['racer_number'],
				$report->getRacerName($racer)
			);
		}

		return $this->panel($tp->toHTML(LAN_ADMIN_RACEREPORTS_117, false), $items);
	}
}


class racereports_number_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
