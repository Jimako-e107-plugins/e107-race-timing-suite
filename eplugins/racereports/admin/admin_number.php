<?php
/*
 * e107 website system
 *
 * racereports plugin - NUMBER (racer progression) report area (mode 'number').
 *
 * ONE admin file per report area: this file owns the NUMBER screen only - ONE
 * screen listing EVERY bib (one row per #racer, ordered by racer_number), each a
 * LINK to the single-racer whole-course report (report_number.php?n=<bib>) opened
 * in a NEW TAB. It is a navigation list only - NO export buttons. The name is
 * built with the shared race_report::getRacerName (the single name-composition
 * place, identical to every other report). Exposed the SAME way as the
 * online/point/stu screens.
 *
 * Bootstrap: load the framework, load the LAN (so the dispatcher captions and the
 * pluginTitle constants resolve), require the shared dispatcher + the shared
 * report base + the shared report helper (for getRacerName), then gate the whole
 * screen on the plugin's OWN admin permission with getperms('P'). The dispatcher
 * routes here for mode=number (admin_menu.php $adminMenu 'number/number' url points
 * at this file).
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
 * NUMBER area: ONE screen listing every bib -> a link to the single-racer
 * whole-course report (report_number.php?n=<bib>), opened in a new tab. Exposed
 * natively the SAME way as the stu screen; the name is composed via the shared
 * race_report::getRacerName and the row by the base's renderNumberLink().
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
