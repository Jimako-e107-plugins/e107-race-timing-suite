<?php
/*
 * e107 website system
 *
 * racereports plugin - shared base for the report-link admin screens.
 *
 * Each report area (overview / online / point / stu) lives in its OWN admin
 * entry file (admin_overview.php, admin_online.php, admin_point.php,
 * admin_stu.php) and defines exactly one concrete controller. They all share
 * this abstract base, which holds the render helpers (Bootstrap link panels)
 * and the read-only data accessors - the same return-an-HTML-string mechanism
 * raceevent's maintenance screen uses.
 *
 * This file is a pure class def (guarded by e107_INIT): the per-area entry files
 * require it AFTER bootstrapping class2.php and loading the LAN, then declare
 * their own controller on top of it.
 *
 * SECURITY / conventions (native e107 only):
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

if (!defined('e107_INIT')) { exit; }


/**
 * Shared base for the report-link admin areas. Extends e_admin_ui as a custom
 * controller screen (no DB table); the concrete classes set only their default
 * action. The render helpers build Bootstrap panels of report links - the same
 * return-an-HTML-string mechanism raceevent's maintenance screen uses.
 */
abstract class racereports_reports_ui extends e_admin_ui
{
	protected $pluginTitle = LAN_ADMIN_RACEREPORTS_001;
	protected $pluginName  = 'racereports';

	// Custom controller screens - no DB-table CRUD.
	protected $table = '';
	protected $pid   = '';

	// SEF placeholder meaning "all categories / all checkpoints" (the SECOND
	// {race_category_sef}/{race_point_sef} token; see e_url.php).
	const KOMPLET = 'komplet';

	// FIRST {race_sef}-token value that triggers the finish/start "all tracks on one
	// page" mode (stacked per-track tables). Renamed from the legacy 'komplet' to
	// avoid confusion with the second-param c=komplet ("all categories"). See e_url.php.
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
	 * One clickable link row, rendered as a table row (<tr><td>) so the link is a
	 * PLAIN <a> in a cell - deliberately NOT a.list-group-item, whose admin-theme
	 * color (#c6c6c6) is unreadable on the light panel background. $label and the
	 * optional $sef suffix are escaped via toHTML; the href via toAttribute. The
	 * containing <table> is emitted by panel().
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
	 * Per-track/-race/-category CSV + XLS export buttons as TWO SEPARATE table cells -
	 * the CSV button in its own <td>, the XLS button in its own <td>, so each lines up
	 * in its own column beside the report link on the SAME compact row (no btn-group
	 * span stretching the link cell, which is what made the rows tall). Each cell is
	 * narrow (width:1%, nowrap) and vertical-align:middle so the row collapses to the
	 * normal admin table height. $exportBase is the plugin-path report URL WITH its r/c
	 * (or p) query params already set but WITHOUT &export - built the dedup-safe way per
	 * race/per category (distinct params); &export=csv / &export=xls are appended here.
	 * Same buttons / same look (btn-csv / btn-excel) as before; hrefs escaped via
	 * toAttribute. Caller (linkItemWithExport / renderStuLink) supplies the link cell
	 * and the surrounding <tr>.
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
	 * Build the plugin-path export base URL for a report (e.g. report_finish.php),
	 * with the r/c (or p) query params attached via http_build_query so both params
	 * survive regardless of SEF (the report pages read $_GET['r']/$_GET['c'] directly).
	 * The caller appends &export=csv / &export=xls (see exportButtonCells()).
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
	 * One clickable link row (like linkItem) PLUS CSV + XLS export buttons, as a compact
	 * THREE-COLUMN table row: a PLAIN <a> link in the first <td> (NOT a.list-group-item -
	 * see linkItem), then the CSV button in its OWN <td> and the XLS button in its OWN
	 * <td> (separate columns, side by side on the SAME row), so the buttons sit beside
	 * the link without nesting <a> inside <a> and without stretching the link cell. With
	 * each button in a narrow, vertical-align:middle cell the row stays at normal admin
	 * table height (no tall empty rows). The report link href is escaped via toAttribute
	 * and the label/sef via toHTML; the two button cells come from exportButtonCells().
	 * Mirrors renderStuLink()'s row layout. The containing <table> is emitted by panel().
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
	 * A muted "nothing here" table row. $colspan lets the cell span the export tables'
	 * extra button columns: link-only tables (online/point/dobeh) use the default 1, so
	 * their rows are unchanged; the export tables (finish/start) pass 3 so the fallback
	 * row spans link + CSV + XLS and the columns stay aligned.
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
	 * For link lists (the default) $body is a string of <tr> rows and is wrapped in a
	 * <table class='table table-striped table-bordered'> - so the links render as plain
	 * <a> in table cells, dodging the admin theme's unreadable a.list-group-item color.
	 * Pass $asTable=false for non-list bodies (e.g. the custom screen's GET form) to
	 * drop straight into the panel body with no table.
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
	 * Finish (results) report links for a race: the "all categories" (komplet) link
	 * plus one per category tied to the race (FIND_IN_SET on race_category_race).
	 * Same query contract / category resolution as renderOnlineLinks(), but built on
	 * the 'finish' route (report_finish.php ?r=<race_sef>&c=<race_category_sef>).
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
	 * Start (start-point standings) report links for a race: the "all categories"
	 * (komplet) link plus one per category tied to the race (FIND_IN_SET on
	 * race_category_race). Same query contract / category resolution as
	 * renderFinishLinks(), but built on the 'start' route (report_start.php
	 * ?r=<race_sef>&c=<race_category_sef>).
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

	/**
	 * Dobeh (checkpoint arrivals board) report links for a race: the "all
	 * checkpoints" (komplet) link plus one per checkpoint tied to the race
	 * (FIND_IN_SET on race_point_race, ORDER BY race_point_order), SKIPPING
	 * start/finish (the dobeh report itself drops them for p=komplet). Same shape /
	 * checkpoint resolution as renderCheckpointLinks(), but built on the 'dobeh'
	 * route (report_dobeh.php ?r=<race_sef>&p=<race_point_sef>). NO export buttons -
	 * the dobeh board is a live, auto-refreshing view with no export.
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
	 * SUT report row for ONE race(track): the link to the per-track finishers-only
	 * results list PLUS per-track CSV + XLS export buttons ("v riadku tlačítka na
	 * export zostavy"). The report link uses the 'stu' route ({race_id} token, legacy
	 * parity - ?p selects a track by race_id). The export buttons point at the SAME
	 * report with &export=csv / &export=xls appended, built on the plugin path so both
	 * the ?p (track) and ?export params survive regardless of SEF.
	 *
	 * Rendered as a compact THREE-COLUMN table row (link | CSV | XLS) - a PLAIN <a> in
	 * the first <td> (NOT a.list-group-item, whose admin-theme color is unreadable),
	 * then the CSV button in its OWN <td> and the XLS button in its OWN <td> (separate
	 * columns, side by side on the SAME row), so the buttons sit beside the link without
	 * nesting <a> inside <a> and without stretching the link cell - the row stays at
	 * normal admin table height. Same layout as linkItemWithExport(); the two button
	 * cells come from the shared exportButtonCells(). The containing <table> is emitted
	 * by panel().
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

		// Plugin-path export base so both the ?p (track) and ?export params survive
		// regardless of SEF; exportButtonCells() appends &export=csv / &export=xls.
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
	 * AKTUALNE report row for ONE race: a plain link to the FULL per-race results
	 * matrix (report_aktualne.php). Like the 'stu' route, the report's ?p selects a
	 * RACE by race_id (legacy parity), so the 'aktualne' route's {race_id} token is
	 * the int race id. Phase 1 is the on-screen report only - NO export buttons yet
	 * (the CSV/XLS + archive snapshot are Phase 2), so this is the plain linkItem()
	 * shape, not the flex link+buttons row.
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
	 * EVERY racer (all tracks), full rows, ordered by bib. The number screen lists
	 * one row per bib, so it needs the full racer row (getRacerName reads
	 * surname/firstname/team/local). racer_number is a VARCHAR (leading zeros are
	 * data), so ORDER BY racer_number is the natural bib order. Empty array on no
	 * rows. Read-only via the db class; no user input is interpolated.
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
	 * One navigation row for the number (racer progression) screen: a PLAIN <a>
	 * (NOT a.list-group-item - see linkItem) to the single-racer whole-course report
	 * (report_number.php?n=<bib>), opened in a NEW TAB. The label is
	 * "<bib> — <name>": the bib is escaped here (toHTML), while $nameHtml is the
	 * ALREADY-SAFE output of race_report::getRacerName (it routes surname/firstname
	 * through toHTML and emits its own markup), so it is inserted as-is and NOT
	 * re-escaped (which would show literal tags). The href is built with e107::url()
	 * on the plugin-scoped 'number' route (so SEF and non-SEF installs both resolve)
	 * and escaped via toAttribute; the bib stays a STRING. NO export buttons - this
	 * is a navigation list only. The containing <table> is emitted by panel().
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
