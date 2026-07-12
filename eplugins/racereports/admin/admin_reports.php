<?php
/*
 * e107 website system
 *
 * racereports plugin - admin entry: bootstraps and runs the dispatcher.
 *
 * Modelled on raceevent/admin/admin_config.php. This single entry file defines
 * the dispatcher's controllers and runs the page; both admin areas live here as
 * native e107 e_admin_ui controllers (empty $table/$pid - no DB-table CRUD),
 * exactly like raceevent's maintenance / checklinks screens. The shared base
 * racereports_reports_ui holds the link-list render helpers so each area's
 * <action>Page() just assembles panels and returns the HTML string, which the
 * framework renders inside the admin theme.
 *
 * Three admin areas (see admin_menu.php $modes):
 *   - overview (overviewPage): INFO ONLY — a short static list of the supported
 *     report types (Online, checkpoint times). NO live links, no e107::url().
 *   - online   (onlinePage):   per race, the Online report links only.
 *   - point    (pointPage):    per race, the checkpoint-time report links only
 *     (per checkpoint, skipping start/finish, + "komplet") — exposed the SAME
 *     native way as the online screen.
 *
 * SECURITY / conventions (native e107 only):
 *   - Bootstrap via class2.php, load the LAN (so the dispatcher captions and the
 *     pluginTitle constants resolve), require the dispatcher, then gate the whole
 *     screen on the plugin's OWN admin permission with getperms('P'). No
 *     top-level redirect anywhere (the dispatcher's per-mode perm 'P' and
 *     auth.php enforce access too).
 *   - READ-ONLY: data is read via the db class; every value placed in markup is
 *     escaped (text via $tp->toHTML, hrefs via $tp->toAttribute) and every report
 *     link is built with e107::url() so SEF and non-SEF installs both resolve.
 *
 * Front report routes (racereports/e_url.php), plugin-scoped keys:
 *   online -> report_online.php ?r=<race_sef>&c=<race_category_sef>
 *   point  -> report_point.php  ?r=<race_sef>&p=<race_point_sef>
 * race_category / race_point are tied to a race via FIND_IN_SET(race_id,
 * race_category_race) / (race_id, race_point_race); race_point has no sef column
 * so its identifier is race_point_code (the value the {race_point_sef} token
 * carries).
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
 * Shared base for both admin areas. Extends e_admin_ui as a custom controller
 * screen (no DB table); the concrete classes set only their default action. The
 * render helpers build Bootstrap panels of report links - the same return-an-
 * HTML-string mechanism raceevent's maintenance screen uses.
 */
abstract class racereports_reports_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_ADMIN_RACEREPORTS_001;
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
 * Overview area: INFO ONLY — a short static list of the report types this plugin
 * supports. NO live per-race/category/checkpoint links (those live on the Online
 * and Checkpoint screens). Pure informational markup, no e107::url() here.
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
