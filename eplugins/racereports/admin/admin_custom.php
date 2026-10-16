<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_custom.php
 *
*/

/*
 * Segment report screen: a From/To picker per track instead of a link list (any
 * pair of checkpoints is allowed). See docs/plugins/racereports.md, "Report screens".
 *
 * The picker is a plain GET form pointed at report_custom.php's plugin path, so it
 * works the same on SEF and non-SEF installs (the report reads trasa/od/do from
 * $_GET).
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
 * Custom (segment) area: per race, a two-dropdown Od/Do picker that opens the
 * segment report for any chosen pair of the race's checkpoints.
 */
class racereports_custom_ui extends racereports_reports_ui
{
	protected $defaultAction = 'custom';

	public function customPage()
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

			// asTable=false: the body is a GET picker form, not a link list, so it goes
			// straight into the panel body with no links table around it.
			$text .= $this->panel(
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_ADMIN_RACEREPORTS_106, false),
				$this->renderSegmentPicker($raceId, $raceSef),
				false
			);
		}

		return $text;
	}

	/**
	 * The per-race From/To picker. All checkpoints are offered, start/finish
	 * included. Values are escaped (toAttribute on option values / action, toHTML
	 * on labels).
	 *
	 * @param int    $raceId
	 * @param string $raceSef
	 * @return string the picker markup (a native GET form)
	 */
	protected function renderSegmentPicker($raceId, $raceSef)
	{
		$tp  = e107::getParser();
		$sql = e107::getDb();

		$points = $sql->retrieve(
			"SELECT race_point_code, race_point_name, race_point_order
			 FROM #race_point AS rp
			 WHERE FIND_IN_SET(" . (int) $raceId . ", rp.race_point_race)
			 ORDER BY race_point_order",
			true
		);

		if (!is_array($points) || empty($points))
		{
			return "<div class='text-muted'><em>"
				. $tp->toHTML(LAN_ADMIN_RACEREPORTS_012, false) . "</em></div>";
		}

		// Build the shared <option> set once (same list for Od and Do).
		$options = '';
		foreach ($points as $point)
		{
			$code = (string) $point['race_point_code'];
			$name = (string) $point['race_point_name'];
			$options .= "<option value='" . $tp->toAttribute($code) . "'>"
				. $tp->toHTML($name, false) . " (" . $tp->toHTML($code, false) . ")</option>";
		}

		$action = e_PLUGIN_ABS . 'racereports/report_custom.php';

		$form  = "<div>";
		$form .= "<form action='" . $tp->toAttribute($action) . "' method='get' target='_blank' rel='noopener' class='form-inline'>";
		$form .= "<input type='hidden' name='trasa' value='" . $tp->toAttribute($raceSef) . "' />";

		$form .= "<label class='control-label' style='margin-right:6px;'>"
			. $tp->toHTML(LAN_ADMIN_RACEREPORTS_102, false) . "</label> ";
		$form .= "<select name='od' class='form-control' style='margin-right:12px;'>" . $options . "</select> ";

		$form .= "<label class='control-label' style='margin-right:6px;'>"
			. $tp->toHTML(LAN_ADMIN_RACEREPORTS_103, false) . "</label> ";
		$form .= "<select name='do' class='form-control' style='margin-right:12px;'>" . $options . "</select> ";

		$form .= "<button type='submit' class='btn btn-primary'>"
			. $tp->toHTML(LAN_ADMIN_RACEREPORTS_104, false) . "</button>";
		$form .= "</form>";
		$form .= "</div>";

		return $form;
	}
}


class racereports_custom_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
