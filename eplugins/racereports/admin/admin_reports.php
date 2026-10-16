<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_reports.php
 *
*/

/*
 * Old single admin entry from before the one-file-per-screen split. Not linked
 * from the admin menu; it is the only file serving the info-only 'overview' mode,
 * plus older copies of the online and point screens. It carries its own copy of the
 * racereports_reports_ui base, so it must not be loaded together with
 * admin_report_ui.php.
 */

require_once("../../../class2.php");

// LAN first: admin_menu.php's $adminMenu/$menuTitle and the controllers'
// $pluginTitle reference these constants at class-definition time.
e107::lan('racereports', true, true);

require_once("admin_menu.php"); // shared dispatcher / menu (pure class def)

if (!getperms('P'))
{
	exit;
}


/**
 * Old copy of the shared base; the current one is admin_report_ui.php.
 */
abstract class racereports_reports_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEREPORTS_001;
	protected $pluginName  = 'racereports';

	// Custom controller screens - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	// SEF placeholder meaning "all categories / all checkpoints" (see e_url.php).
	const KOMPLET = 'komplet';

	/**
	 * The event's races, ordered, via the db class. Empty array on no rows.
	 *
	 * @return array
	 */
	protected function getRaces()
	{
		$races = e107::getDb()->retrieve(
			"SELECT race_id, race_sef, race_name FROM #race ORDER BY race_id",
			true
		);

		return is_array($races) ? $races : array();
	}

	/**
	 * One clickable link row (Bootstrap list-group item). $label and the optional
	 * $sef suffix are escaped via toHTML; the href via toAttribute.
	 *
	 * @param string $url
	 * @param string $label
	 * @param string $sef optional SEF/code shown as a muted suffix
	 * @return string
	 */
	protected function linkItem($url, $label, $sef = '')
	{
		$tp = e107::getParser();

		$suffix = ($sef !== '')
			? " <small class='text-muted'>(" . $tp->toHTML($sef, false) . ")</small>"
			: '';

		return "<a class='list-group-item' href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. $tp->toHTML($label, false) . $suffix . "</a>";
	}

	/**
	 * A muted "nothing here" list row.
	 *
	 * @param string $label
	 * @return string
	 */
	protected function emptyItem($label)
	{
		return "<div class='list-group-item disabled'><em>"
			. e107::getParser()->toHTML($label, false) . "</em></div>";
	}

	/**
	 * Wrap a body of list rows in a titled panel. $heading is HTML the caller has
	 * already escaped.
	 *
	 * @param string $heading pre-escaped heading HTML
	 * @param string $body list rows
	 * @return string
	 */
	protected function panel($heading, $body)
	{
		return "<div class='panel panel-default'>"
			. "<div class='panel-heading'>" . $heading . "</div>"
			. "<div class='panel-body'><div class='list-group'>" . $body . "</div></div>"
			. "</div>";
	}

	/**
	 * Per-race heading: name + (sef), both escaped.
	 *
	 * @param array $race
	 * @return string
	 */
	protected function raceHeading(array $race)
	{
		$tp = e107::getParser();

		return $tp->toHTML($race['race_name'], false)
			. " <small>(" . $tp->toHTML((string) $race['race_sef'], false) . ")</small>";
	}

	/**
	 * Online report links for a race: the "all categories" (komplet) link plus
	 * one per category tied to the race (FIND_IN_SET on race_category_race).
	 *
	 * @param int $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderOnlineLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// komplet: all categories (c=komplet).
		$items = $this->linkItem(
			e107::url('racereports', 'online', array(
				'race_sef'          => $raceSef,
				'race_category_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_021
		);

		$categories = $sql->retrieve(
			"SELECT race_category_id, race_category_sef, race_category_name
			 FROM #race_category AS rc
			 WHERE FIND_IN_SET(" . (int) $raceId . ", rc.race_category_race)
			 ORDER BY race_category_name",
			true
		);

		if (is_array($categories) && !empty($categories))
		{
			foreach ($categories as $cat)
			{
				$items .= $this->linkItem(
					e107::url('racereports', 'online', array(
						'race_sef'          => $raceSef,
						'race_category_sef' => (string) $cat['race_category_sef'],
					)),
					$cat['race_category_name'],
					(string) $cat['race_category_sef']
				);
			}
		}
		else
		{
			$items .= $this->emptyItem(LAN_ADMIN_RACEREPORTS_011);
		}

		return $items;
	}

	/**
	 * Checkpoint-time report links for a race: the "all checkpoints" (komplet)
	 * link plus one per checkpoint tied to the race (FIND_IN_SET on
	 * race_point_race, ORDER BY race_point_order), SKIPPING start/finish (the
	 * point report itself drops them for p=komplet). race_point has no sef column
	 * so the {race_point_sef} token carries race_point_code.
	 *
	 * @param int $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderCheckpointLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// komplet: all checkpoints (p=komplet).
		$items = $this->linkItem(
			e107::url('racereports', 'point', array(
				'race_sef'       => $raceSef,
				'race_point_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_031
		);

		$points = $sql->retrieve(
			"SELECT race_point_id, race_point_code, race_point_name, race_point_order
			 FROM #race_point AS rp
			 WHERE FIND_IN_SET(" . (int) $raceId . ", rp.race_point_race)
			 ORDER BY race_point_order",
			true
		);

		$count = 0;

		if (is_array($points) && !empty($points))
		{
			foreach ($points as $point)
			{
				$code = (string) $point['race_point_code'];

				if ($code === 'start' || $code === 'finish')
				{
					continue;
				}

				$items .= $this->linkItem(
					e107::url('racereports', 'point', array(
						'race_sef'       => $raceSef,
						'race_point_sef' => $code,
					)),
					$point['race_point_name'],
					$code
				);
				$count++;
			}
		}

		if ($count === 0)
		{
			$items .= $this->emptyItem(LAN_ADMIN_RACEREPORTS_012);
		}

		return $items;
	}
}


/**
 * Overview: a static, info-only list of report types (no links).
 */
class racereports_overview_ui extends racereports_reports_ui
{
	protected $defaultAction = 'overview';

	public function overviewPage()
	{
		$tp = e107::getParser();

		// Static, non-clickable list of the supported report types.
		$body = "<div class='list-group-item disabled'>"
			. $tp->toHTML(LAN_ADMIN_RACEREPORTS_051, false) . "</div>";
		$body .= "<div class='list-group-item disabled'>"
			. $tp->toHTML(LAN_ADMIN_RACEREPORTS_052, false) . "</div>";

		return $this->panel($tp->toHTML(LAN_ADMIN_RACEREPORTS_050, false), $body);
	}
}


/**
 * Checkpoint area: per race, the checkpoint-time report links.
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


/**
 * Online area: per race, the Online report links only.
 */
class racereports_online_ui extends racereports_reports_ui
{
	protected $defaultAction = 'online';

	public function onlinePage()
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
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_ADMIN_RACEREPORTS_020, false),
				$this->renderOnlineLinks($raceId, $raceSef)
			);
		}

		return $text;
	}
}


class racereports_overview_form_ui extends e_admin_form_ui
{
}


class racereports_online_form_ui extends e_admin_form_ui
{
}


class racereports_point_form_ui extends e_admin_form_ui
{
}


new racereports_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
