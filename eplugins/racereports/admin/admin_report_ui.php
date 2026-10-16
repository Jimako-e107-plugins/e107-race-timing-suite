<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - admin/admin_report_ui.php
 *
*/

/*
 * Shared base for the report-link admin screens (admin_online.php, admin_point.php,
 * ...): read-only data accessors and render helpers. Pure class definition; the entry
 * files require it after bootstrapping class2.php and loading the LAN.
 *
 * Every value placed in markup is escaped (text via toHTML, hrefs via toAttribute);
 * report links are built with e107::url() so SEF and non-SEF installs both resolve.
 * race_category / race_point are tied to a race via FIND_IN_SET(race_id,
 * race_category_race / race_point_race); race_point has no sef column, so the
 * {race_point_sef} token carries race_point_code.
 */

if (!defined('e107_INIT')) { exit; }


/**
 * Shared base for the report-link admin screens: custom controller screens (no DB
 * table) whose <action>Page() returns an HTML string of Bootstrap panels.
 */
abstract class racereports_reports_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_GLOBAL_RACEREPORTS_001;
	protected $pluginName  = 'racereports';

	// Custom controller screens - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	// SEF placeholder meaning "all categories / all checkpoints" (the SECOND
	// {race_category_sef}/{race_point_sef} token; see e_url.php).
	const KOMPLET = 'komplet';

	// FIRST {race_sef}-token value for the finish/start "all tracks on one page"
	// mode (distinct from the second-param c=komplet = "all categories").
	const OVERVIEW = 'overview';

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
	 * One link row (<tr><td>): a PLAIN <a> in a cell - not a.list-group-item, whose
	 * admin-theme colour (#c6c6c6) is unreadable on the light panel. The containing
	 * <table> is emitted by panel().
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

		return "<tr><td><a href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. $tp->toHTML($label, false) . $suffix . "</a></td></tr>";
	}

	/**
	 * CSV + XLS export buttons as two separate narrow cells (width:1%, nowrap) beside
	 * the link cell, so the row keeps the normal admin table height (a btn-group in
	 * the link cell made the rows tall). $exportBase is the report URL with its query
	 * params but without &export; &export=csv / &export=xls are appended here. The
	 * caller supplies the link cell and the surrounding <tr>.
	 *
	 * @param string $exportBase report URL incl. r/c (or p) params, no &export
	 * @return string two <td> cells: CSV column, then XLS column
	 */
	protected function exportButtonCells($exportBase)
	{
		$tp = e107::getParser();

		$csvUrl = $exportBase . '&export=csv';
		$xlsUrl = $exportBase . '&export=xls';

		$cellStyle = "width:1%;white-space:nowrap;vertical-align:middle;";

		return "<td class='text-right' style='" . $cellStyle . "'>"
			. "<a class='btn btn-default btn-xs btn-csv' href='" . $tp->toAttribute($csvUrl) . "' target='_blank' rel='noopener'>CSV</a></td>"
			. "<td class='text-right' style='" . $cellStyle . "'>"
			. "<a class='btn btn-default btn-xs btn-excel' href='" . $tp->toAttribute($xlsUrl) . "' target='_blank' rel='noopener'>XLS</a></td>";
	}

	/**
	 * Plugin-path export base URL for a report (e.g. report_finish.php). Built on the
	 * plugin path, not e107::url(), so the r/c (or p) params survive regardless of SEF
	 * (the report pages read $_GET directly).
	 *
	 * @param string $reportFile bare report filename, e.g. 'report_finish.php'
	 * @param array  $params     query params, e.g. array('r'=>$raceSef,'c'=>$catSef)
	 * @return string
	 */
	protected function reportExportBase($reportFile, array $params)
	{
		return e_PLUGIN_ABS . 'racereports/' . $reportFile . '?' . http_build_query($params);
	}

	/**
	 * A link row (as linkItem) plus the CSV / XLS cells from exportButtonCells():
	 * link | CSV | XLS. The containing <table> is emitted by panel().
	 *
	 * @param string $url        report link href (e107::url)
	 * @param string $label      link label
	 * @param string $sef        optional muted SEF suffix
	 * @param string $exportBase report URL incl. params, no &export (exportButtonCells())
	 * @return string one table row: link | CSV | XLS
	 */
	protected function linkItemWithExport($url, $label, $sef, $exportBase)
	{
		$tp = e107::getParser();

		$suffix = ($sef !== '')
			? " <small class='text-muted'>(" . $tp->toHTML($sef, false) . ")</small>"
			: '';

		$link = "<a href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. $tp->toHTML($label, false) . $suffix . "</a>";

		return "<tr><td style='vertical-align:middle;'>" . $link . "</td>"
			. $this->exportButtonCells($exportBase) . "</tr>";
	}

	/**
	 * A muted "nothing here" table row. The export tables (finish/start) pass
	 * $colspan = 3 so the row spans link + CSV + XLS.
	 *
	 * @param string $label
	 * @param int    $colspan number of columns to span (default 1)
	 * @return string
	 */
	protected function emptyItem($label, $colspan = 1)
	{
		$span = ((int) $colspan > 1) ? " colspan='" . (int) $colspan . "'" : '';

		return "<tr><td" . $span . " class='text-muted'><em>"
			. e107::getParser()->toHTML($label, false) . "</em></td></tr>";
	}

	/**
	 * Wrap a body in a titled panel. $heading is HTML the caller has already escaped.
	 * By default $body is <tr> rows and is wrapped in a table; pass $asTable=false for
	 * other bodies (e.g. the segment screen's GET form).
	 *
	 * @param string $heading pre-escaped heading HTML
	 * @param string $body    table rows (default) or arbitrary panel body
	 * @param bool   $asTable wrap $body in a links table (true) or emit it as-is (false)
	 * @return string
	 */
	protected function panel($heading, $body, $asTable = true)
	{
		$inner = $asTable
			? "<table class='table table-striped table-bordered' style='margin-bottom:0;'>" . $body . "</table>"
			: $body;

		return "<div class='panel panel-default'>"
			. "<div class='panel-heading'>" . $heading . "</div>"
			. "<div class='panel-body'>" . $inner . "</div>"
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
	 * Finish report links for a race: the "all categories" (komplet) row plus one per
	 * category tied to the race, each with CSV / XLS buttons.
	 *
	 * @param int $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderFinishLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// komplet: all categories of this race (c=komplet) - a single-race scope, so
		// it carries CSV/XLS export buttons (the export streams that one table).
		$items = $this->linkItemWithExport(
			e107::url('racereports', 'finish', array(
				'race_sef'          => $raceSef,
				'race_category_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_081,
			'',
			$this->reportExportBase('report_finish.php', array(
				'r' => $raceSef,
				'c' => self::KOMPLET,
			))
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
				$items .= $this->linkItemWithExport(
					e107::url('racereports', 'finish', array(
						'race_sef'          => $raceSef,
						'race_category_sef' => $catSef,
					)),
					$cat['race_category_name'],
					$catSef,
					$this->reportExportBase('report_finish.php', array(
						'r' => $raceSef,
						'c' => $catSef,
					))
				);
			}
		}
		else
		{
			// Export table (link | CSV | XLS) - span all 3 columns so the row aligns.
			$items .= $this->emptyItem(LAN_ADMIN_RACEREPORTS_011, 3);
		}

		return $items;
	}

	/**
	 * Start list links for a race: same layout as renderFinishLinks(), on the 'start'
	 * route.
	 *
	 * @param int $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderStartLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// komplet: all categories of this race (c=komplet) - a single-race scope, so
		// it carries CSV/XLS export buttons (the export streams that one table).
		$items = $this->linkItemWithExport(
			e107::url('racereports', 'start', array(
				'race_sef'          => $raceSef,
				'race_category_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_091,
			'',
			$this->reportExportBase('report_start.php', array(
				'r' => $raceSef,
				'c' => self::KOMPLET,
			))
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
				$items .= $this->linkItemWithExport(
					e107::url('racereports', 'start', array(
						'race_sef'          => $raceSef,
						'race_category_sef' => $catSef,
					)),
					$cat['race_category_name'],
					$catSef,
					$this->reportExportBase('report_start.php', array(
						'r' => $raceSef,
						'c' => $catSef,
					))
				);
			}
		}
		else
		{
			// Export table (link | CSV | XLS) - span all 3 columns so the row aligns.
			$items .= $this->emptyItem(LAN_ADMIN_RACEREPORTS_011, 3);
		}

		return $items;
	}

	/**
	 * Checkpoint-time report links for a race: the "all checkpoints" (komplet) link
	 * plus one per checkpoint in race_point_order, skipping start/finish (the report
	 * drops them for p=komplet too).
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

	/**
	 * Arrivals board links for a race: same shape as renderCheckpointLinks(), on the
	 * 'dobeh' route. No export (live board).
	 *
	 * @param int $raceId
	 * @param string $raceSef
	 * @return string list rows
	 */
	protected function renderDobehLinks($raceId, $raceSef)
	{
		$sql = e107::getDb();

		// komplet: all checkpoints (p=komplet).
		$items = $this->linkItem(
			e107::url('racereports', 'dobeh', array(
				'race_sef'       => $raceSef,
				'race_point_sef' => self::KOMPLET,
			)),
			LAN_ADMIN_RACEREPORTS_109
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
					e107::url('racereports', 'dobeh', array(
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

	/**
	 * SUT row for one track: link | CSV | XLS (same layout as linkItemWithExport()).
	 * The 'stu' route takes the race_id ({race_id} token -> ?p).
	 *
	 * @param int    $raceId
	 * @param string $raceName label shown on the link
	 * @param string $raceSef  muted suffix
	 * @return string one table row: link | CSV | XLS
	 */
	protected function renderStuLink($raceId, $raceName, $raceSef)
	{
		$tp = e107::getParser();

		$reportUrl = e107::url('racereports', 'stu', array('race_id' => (int) $raceId));

		// Plugin-path export base so ?p and ?export survive regardless of SEF.
		$exportBase = e_PLUGIN_ABS . 'racereports/report_stu.php?p=' . (int) $raceId;

		$suffix = ($raceSef !== '')
			? " <small class='text-muted'>(" . $tp->toHTML($raceSef, false) . ")</small>"
			: '';

		$link = "<a href='" . $tp->toAttribute($reportUrl) . "' target='_blank' rel='noopener'>"
			. $tp->toHTML($raceName, false) . $suffix . "</a>";

		return "<tr><td style='vertical-align:middle;'>" . $link . "</td>"
			. $this->exportButtonCells($exportBase) . "</tr>";
	}

	/**
	 * Full results row for one race: a plain link (no export). Like 'stu', the
	 * 'aktualne' route takes the race_id ({race_id} token -> ?p).
	 *
	 * @param int    $raceId
	 * @param string $raceName label shown on the link
	 * @param string $raceSef  muted suffix
	 * @return string one list row
	 */
	protected function renderAktualneLink($raceId, $raceName, $raceSef)
	{
		$url = e107::url('racereports', 'aktualne', array('race_id' => (int) $raceId));

		return $this->linkItem($url, $raceName, $raceSef);
	}

	/**
	 * Every racer (all tracks), full rows (getRacerName needs them), ordered by
	 * racer_number - a VARCHAR, so string order. No user input in the query.
	 *
	 * @return array racer rows (each a full #racer row)
	 */
	protected function getRacersByNumber()
	{
		$rows = e107::getDb()->retrieve(
			"SELECT * FROM #racer ORDER BY racer_number",
			true
		);

		return is_array($rows) ? $rows : array();
	}

	/**
	 * One racer progression row: "<bib> — <name>". The bib is escaped here;
	 * $nameHtml is the already-safe getRacerName() output (it carries its own
	 * markup), so it is NOT re-escaped. The bib stays a STRING.
	 *
	 * @param string $bib      racer_number (STRING - leading zeros preserved)
	 * @param string $nameHtml pre-escaped display name (race_report::getRacerName)
	 * @return string one table row
	 */
	protected function renderNumberLink($bib, $nameHtml)
	{
		$tp  = e107::getParser();
		$bib = (string) $bib;

		$url = e107::url('racereports', 'number', array('race_number' => $bib));

		$label = "<strong>" . $tp->toHTML($bib, false) . "</strong> — " . $nameHtml;

		return "<tr><td><a href='" . $tp->toAttribute($url) . "' target='_blank' rel='noopener'>"
			. $label . "</a></td></tr>";
	}
}
