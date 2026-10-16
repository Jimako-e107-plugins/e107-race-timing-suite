<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - admin/admin_startlists.php
 *
*/

/*
 * Start lists screen (docs/plugins/racers.md, "Start lists"): a link directory
 * to the public start list (startlist.php, e_url 'startlist'). Read-only; every
 * value placed in markup is escaped (toHTML / toAttribute).
 *
 * The "all tracks" / "all categories" token is the literal 'all' (what
 * startlist.php matches) - not racereports' 'komplet' / 'overview'.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


/**
 * Custom controller screen (no DB-table CRUD). Links are plain <a> in table cells,
 * not a.list-group-item (unreadable admin-theme colour) - same as racereports'
 * admin_report_ui.php link table.
 */
class racers_startlists_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACERS_ADMIN_034;
	protected $pluginName  = 'racers';

	// Custom controller screen - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'startlists';

	// "all categories" / "all tracks" token (see the file header).
	const ALL = 'all';

	/**
	 * The event's tracks, ordered, via the db class. Empty array on no rows.
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
	 * One link row (<tr><td><a>). $label and the optional $sef suffix are escaped
	 * via toHTML, the href via toAttribute. The <table> is emitted by panel().
	 *
	 * @param string $url
	 * @param string $label
	 * @param string $sef optional SEF shown as a muted suffix
	 * @return string
	 */
	protected function linkItem($url, $label, $sef = '')
	{
		$tp = e107::getParser();

		$suffix = ($sef !== '')
			? " <small class='text-muted'>(" . $tp->toHTML($sef, false) . ")</small>"
			: '';

		return "<tr><td><a href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. $tp->toHTML($label, false) . $suffix . "</a></td></tr>";
	}

	/**
	 * A muted "nothing here" table row.
	 *
	 * @param string $label
	 * @return string
	 */
	protected function emptyItem($label)
	{
		return "<tr><td class='text-muted'><em>"
			. e107::getParser()->toHTML($label, false) . "</em></td></tr>";
	}

	/**
	 * Wrap a body of <tr> rows in a titled panel with a readable links table. $heading
	 * is HTML the caller has already escaped.
	 *
	 * @param string $heading pre-escaped heading HTML
	 * @param string $body    table rows
	 * @return string
	 */
	protected function panel($heading, $body)
	{
		return "<div class='panel panel-default'>"
			. "<div class='panel-heading'>" . $heading . "</div>"
			. "<div class='panel-body'>"
			. "<table class='table table-striped table-bordered' style='margin-bottom:0;'>" . $body . "</table>"
			. "</div></div>";
	}

	/**
	 * Per-track heading: name + (sef), both escaped.
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
	 * Start-list links for a track: the "all categories" link plus one per category
	 * tied to the track (FIND_IN_SET on race_category_race; $raceId int-cast).
	 *
	 * @param int    $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderStartlistLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// all categories of this track (c=all).
		$items = $this->linkItem(
			e107::url('racers', 'startlist', array(
				'race_sef'          => $raceSef,
				'race_category_sef' => self::ALL,
			)),
			LAN_RACERS_ADMIN_037
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
				$catSef = (string) $cat['race_category_sef'];
				$items .= $this->linkItem(
					e107::url('racers', 'startlist', array(
						'race_sef'          => $raceSef,
						'race_category_sef' => $catSef,
					)),
					$cat['race_category_name'],
					$catSef
				);
			}
		}
		else
		{
			$items .= $this->emptyItem(LAN_RACERS_ADMIN_039);
		}

		return $items;
	}

	/**
	 * The start-lists directory: the all-tracks (all categories) link, then per track
	 * a panel with the track's start-list links (all categories + one per category).
	 *
	 * @return string
	 */
	public function startlistsPage()
	{
		$tp    = e107::getParser();
		$races = $this->getRaces();

		if (empty($races))
		{
			return "<div class='alert alert-info'>"
				. $tp->toHTML(LAN_RACERS_ADMIN_038, false) . "</div>";
		}

		// All tracks, all categories (r=all, c=all) - same as startlist.php's ALL tab.
		$overview = $this->linkItem(
			e107::url('racers', 'startlist', array(
				'race_sef'          => self::ALL,
				'race_category_sef' => self::ALL,
			)),
			LAN_RACERS_ADMIN_036
		);
		$text = $this->panel(
			$tp->toHTML(LAN_RACERS_ADMIN_041, false),
			$overview
		);

		// Per track: the start-list links (all categories + one per category).
		foreach ($races as $race)
		{
			$raceId  = (int) $race['race_id'];
			$raceSef = (string) $race['race_sef'];

			$text .= $this->panel(
				$this->raceHeading($race) . " &mdash; " . $tp->toHTML(LAN_RACERS_ADMIN_040, false),
				$this->renderStartlistLinks($raceId, $raceSef)
			);
		}

		return $text;
	}
}


class racers_startlists_form_ui extends e_admin_form_ui
{
}


new racers_adminArea();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN . "footer.php");
exit;
