<?php
/*
 * e107 website system
 *
 * racers plugin - ŠTARTOVACIE LISTINY (start-lists link directory) admin screen.
 *
 * ONE output = ONE admin file, mirroring racereports' admin_finish.php: this file
 * owns the start-list-links screen only - the all-tracks (komplet) link, plus per
 * track the start-list links (all categories + one per category tied to the race).
 * All links target racers' OWN front start list (startlist.php) via the plugin's
 * 'startlist' e_url route, so SEF and non-SEF installs both resolve.
 *
 * Routing the racers way: this file is a self-contained admin ENTRY POINT (mirroring
 * the sibling area files and the racetrack archive fix): it bootstraps class2, requires
 * the shared dispatcher (admin_menu.php, which loads class2 + the admin LAN), gates on
 * the plugin's OWN admin permission, defines this area's controller + form classes, then
 * runs the page. racers' admin_menu.php routes the 'startlists' mode straight here via
 * its $adminMenu 'url' => 'admin_startlists.php'.
 *
 * SECURITY (native e107 only): READ-ONLY. Data is read via the db class; every value
 * placed in markup is escaped (text via $tp->toHTML, hrefs via $tp->toAttribute) and
 * every link is built with e107::url(). The screen is gated by getperms('P').
 *
 * startlist param contract (racers/e_url.php 'startlist'):
 *   startlist -> startlist.php ?r=<race_sef>&c=<race_category_sef>
 * The "all categories" / "all tracks" sentinel is the literal 'all' (startlist.php
 * lines 66/81/96) - racers' own token, NOT racereports' 'komplet'/'overview'. The
 * mechanism is identical; only the sentinel string differs.
 */

require_once("../../../class2.php");

require_once("admin_menu.php"); // shared dispatcher / menu

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}


/**
 * Start-lists area: the all-tracks (all categories) link, then per track the start
 * list links (all categories + one per category tied to the race via FIND_IN_SET on
 * race_category_race). Extends e_admin_ui as a custom controller screen (no DB-table
 * CRUD); the render helpers build readable Bootstrap link panels - plain <a> in table
 * cells, NOT a.list-group-item (whose admin-theme color is unreadable) - replicated
 * from racereports' shared admin_report_ui.php link table.
 */
class racers_startlists_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_RACERS_ADMIN_034;
	protected $pluginName  = 'racers';

	// Custom controller screen - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	protected $defaultAction = 'startlists';

	// startlist sentinel meaning "all categories" / "all tracks" (the literal token
	// startlist.php matches; see e_url.php + the file header above).
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
	 * One clickable link row, rendered as a table row (<tr><td>) so the link is a
	 * PLAIN <a> in a cell - deliberately NOT a.list-group-item, whose admin-theme
	 * color (#c6c6c6) is unreadable on the light panel background. $label and the
	 * optional $sef suffix are escaped via toHTML; the href via toAttribute. The
	 * containing <table> is emitted by panel(). Mirrors racereports' linkItem().
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
	 * is HTML the caller has already escaped. Mirrors racereports' panel().
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
	 * Per-track heading: name + (sef), both escaped. Mirrors racereports' raceHeading().
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
	 * Start-list links for a track: the "all categories" (all) link plus one per
	 * category tied to the track (FIND_IN_SET on race_category_race). Same query
	 * contract / category resolution as racereports' renderFinishLinks(), built on
	 * the racers 'startlist' route (startlist.php ?r=<race_sef>&c=<race_category_sef>).
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

		// All tracks, all categories on one start list (r=all, c=all) - the same link
		// startlist.php's own "ALL" tab uses.
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
