<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * raceevent plugin - includes/event_overview.php
 *
*/

/*
 * Shared event-overview include (user view: docs/plugins/raceevent.md, "Event
 * overview"). Used by page_overview.php and admin/admin_overview.php, so both
 * render the same output.
 *
 * Sections and their routes:
 *   1. Tracks       -> e107::url('racetrack','race', ...)     (per race)
 *   2. Checkpoints  -> e107::url('racereports','point', ...)  (per checkpoint)
 *   3. Categories   -> e107::url('racereports','start', ...)  (per category; START report)
 *   4. Start lists  -> e107::url('racers','startlist', ...)   (per category + per-race komplet)
 *   5. Results      -> e107::url('racereports','finish', ...) (per category + per-race komplet)
 *
 * Sections 2 / 3 / 5 MIRROR the racereports e_sitelink.php builders - same query,
 * same start/finish skip, same race_point_sef mapping:
 *   - section 2 == racereports_sitelink::racereports_points()
 *   - section 5 == racereports_sitelink::racereports_finish()
 *   - section 3 reuses racereports_finish()'s #race x #race_category source but
 *     points at the 'start' key (the START report).
 * Keep these in lock-step with the sitelink builders. stu and online are
 * intentionally excluded.
 *
 * The alive-check resolves each e_url route's redirect target file and flags a
 * missing file or an unregistered route; it never fails the page.
 *
 * READ-ONLY: db class reads + link building only. Every label is escaped with
 * $tp->toHTML() and every href with $tp->toAttribute().
 *
 * Usage:
 *   require_once(e_PLUGIN . 'raceevent/includes/event_overview.php');
 *   $html = raceevent_event_overview();   // returns the HTML, renders nothing
 */

if (!defined('e107_INIT'))
{
	exit;
}

// Front LAN carries the shared section headings + status labels this include
// renders. Loading it here makes the include self-contained: it works the same
// whether the caller is the front page (front LAN already loaded) or the admin
// screen (admin LAN loaded, front LAN not) - define() is load-once via the LAN
// registry, so a second call is a harmless no-op.
e107::lan('raceevent');


if (!function_exists('raceevent_event_overview'))
{
	/**
	 * Build and RETURN the event-overview HTML (the shared link directory with
	 * the per-link alive-check markers). Renders nothing itself - the caller
	 * decides the chrome (HEADERF/FOOTERF page or admin tablerender).
	 *
	 * @return string
	 */
	function raceevent_event_overview()
	{
		$tp  = e107::getParser();
		$sql = e107::getDb();

		// Resolve the registered e_url routes ONCE. getUrlConfig() is the source
		// e107::url() itself consults (the scanned e_url_list), so an unregistered
		// route is simply absent here -> flagged red by the alive-check.
		$routes = e107::getUrlConfig();

		// Pre-compute the target-file status for each route used below. Every link
		// in a section shares one route, so one lookup drives all its markers.
		$st_race      = raceevent_overview_route_status($routes, 'racetrack',   'race');
		$st_point     = raceevent_overview_route_status($routes, 'racereports', 'point');
		$st_start     = raceevent_overview_route_status($routes, 'racereports', 'start');
		$st_startlist = raceevent_overview_route_status($routes, 'racers',      'startlist');
		$st_finish    = raceevent_overview_route_status($routes, 'racereports', 'finish');

		// Shared data sources - the SAME queries the racereports e_sitelink.php
		// builders use.
		$races = $sql->retrieve("SELECT * FROM #race ORDER BY race_name DESC", true);

		$racecats = $sql->retrieve(
			"SELECT * FROM #race AS r, #race_category AS rc
				WHERE FIND_IN_SET(r.race_id, rc.race_category_race)
				ORDER BY race_id, race_category_sef DESC",
			true
		);

		$points = $sql->retrieve(
			"SELECT * FROM #race AS r, #race_point AS rc
				WHERE FIND_IN_SET(r.race_id, rc.race_point_race)
				ORDER BY race_point_order",
			true
		);

		$text = '';

		// ---- 1. Zoznam tratí (per race) -------------------------------------
		$rows = array();
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$rows[] = raceevent_overview_li(
					$tp,
					$race['race_name'],
					'racetrack', 'race', $race,
					$st_race
				);
			}
		}
		$text .= raceevent_overview_section(LAN_RACEEVENT_OV_TRACKS, $rows);

		// ---- 2. Zoznam kontrolných bodov (per checkpoint) -------------------
		// Mirrors racereports_sitelink::racereports_points() exactly: same join
		// /order, same start/finish skip, same race_point_sef = race_point_code.
		$rows = array();
		if (is_array($points))
		{
			foreach ($points as $point)
			{
				if ($point['race_point_code'] == "start")
				{
					continue;
				}

				if ($point['race_point_code'] == "finish")
				{
					continue;
				}

				// race_point has no sef column -> the {race_point_sef} token
				// carries race_point_code (matches report_point.php's lookup).
				$point['race_point_sef'] = $point['race_point_code'];

				$rows[] = raceevent_overview_li(
					$tp,
					$point['race_name'] . " - " . $point['race_point_name'],
					'racereports', 'point', $point,
					$st_point
				);
			}
		}
		$text .= raceevent_overview_section(LAN_RACEEVENT_OV_POINTS, $rows);

		// ---- 3. Zoznam kategórií (per category; START report) ---------------
		// Reuses racereports_finish()'s #race ⋈ #race_category source but points
		// at the 'start' key (the START report).
		$rows = array();
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				$rows[] = raceevent_overview_li(
					$tp,
					$cat['race_name'] . " - " . $cat['race_category_name'],
					'racereports', 'start', $cat,
					$st_start
				);
			}
		}
		$text .= raceevent_overview_section(LAN_RACEEVENT_OV_CATEGORIES, $rows);

		// ---- 4. Štartovacie listiny (per category + per-race komplet) -------
		// The roster (racers plugin). Same race_sef + race_category_sef tokens.
		$rows = array();
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				$rows[] = raceevent_overview_li(
					$tp,
					$cat['race_name'] . " - " . $cat['race_category_name'],
					'racers', 'startlist', $cat,
					$st_startlist
				);
			}
		}
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$params = array(
					'race_sef'          => $race['race_sef'],
					'race_category_sef' => 'komplet',
				);
				$rows[] = raceevent_overview_li(
					$tp,
					$race['race_name'] . " - " . LAN_RACEEVENT_OV_KOMPLET,
					'racers', 'startlist', $params,
					$st_startlist
				);
			}
		}
		$text .= raceevent_overview_section(LAN_RACEEVENT_OV_STARTLISTS, $rows);

		// ---- 5. Výsledkové listiny (per category + per-race komplet) --------
		// Mirrors racereports_sitelink::racereports_finish() exactly: per-race
		// komplet (race_category_sef = 'komplet') + one entry per race_category.
		$rows = array();
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$params = array(
					'race_sef'          => $race['race_sef'],
					'race_category_sef' => 'komplet',
				);
				$rows[] = raceevent_overview_li(
					$tp,
					$race['race_name'] . " - " . LAN_RACEEVENT_OV_KOMPLET,
					'racereports', 'finish', $params,
					$st_finish
				);
			}
		}
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				$rows[] = raceevent_overview_li(
					$tp,
					$cat['race_name'] . " - " . $cat['race_category_name'],
					'racereports', 'finish', $cat,
					$st_finish
				);
			}
		}
		$text .= raceevent_overview_section(LAN_RACEEVENT_OV_RESULTS, $rows);

		return $text;
	}


	/**
	 * Resolve a registered e_url route to its on-disk redirect target and report
	 * whether that target file exists. Cheap: route key -> redirect path,
	 * file_exists. An unregistered route (absent from getUrlConfig()) returns
	 * registered=false; the page is never failed on a miss, only flagged.
	 *
	 * @param array  $routes getUrlConfig() output, keyed by plugin folder
	 * @param string $plugin plugin folder (e.g. 'racereports')
	 * @param string $key    e_url route key (e.g. 'finish')
	 * @return array array('registered'=>bool, 'exists'=>bool, 'target'=>string)
	 */
	function raceevent_overview_route_status($routes, $plugin, $key)
	{
		if (empty($routes[$plugin][$key]) || empty($routes[$plugin][$key]['redirect']))
		{
			return array('registered' => false, 'exists' => false, 'target' => '');
		}

		$path = (string) $routes[$plugin][$key]['redirect'];

		// Drop the query string - only the file path matters for the check.
		if (($qpos = strpos($path, '?')) !== false)
		{
			$path = substr($path, 0, $qpos);
		}

		// Expand the path constants e107 uses in redirect targets.
		$path = str_replace(array('{e_PLUGIN}', '{e_BASE}'), array(e_PLUGIN, e_BASE), $path);

		return array(
			'registered' => true,
			'exists'     => file_exists($path),
			'target'     => $path,
		);
	}


	/**
	 * Render the per-link status marker. Green = route registered AND its target
	 * file is present; red = route not registered OR the target file is missing.
	 * The tooltip carries the resolved target (or "route not registered").
	 *
	 * @param array $status raceevent_overview_route_status() output
	 * @return string
	 */
	function raceevent_overview_marker($status)
	{
		$tp = e107::getParser();

		if (!empty($status['registered']) && !empty($status['exists']))
		{
			return '<span class="label label-success" title="'
				. $tp->toAttribute($status['target']) . '">'
				. LAN_RACEEVENT_OV_ALIVE . '</span>';
		}

		if (empty($status['registered']))
		{
			return '<span class="label label-danger" title="'
				. $tp->toAttribute(LAN_RACEEVENT_OV_NO_ROUTE) . '">'
				. LAN_RACEEVENT_OV_DEAD . '</span>';
		}

		return '<span class="label label-danger" title="'
			. $tp->toAttribute($status['target']) . '">'
			. LAN_RACEEVENT_OV_DEAD . '</span>';
	}


	/**
	 * Build one classic list-link <li>: status marker + escaped link. The href
	 * comes from e107::url() (escaped with toAttribute()); the label is escaped
	 * with toHTML(). Plain links - no racereports visual effects.
	 *
	 * @param object       $tp     parser
	 * @param string       $label  link text
	 * @param string       $plugin e_url plugin folder
	 * @param string       $key    e_url route key
	 * @param array        $params row / params for e107::url()
	 * @param array        $status pre-computed route status for the marker
	 * @return string
	 */
	function raceevent_overview_li($tp, $label, $plugin, $key, $params, $status)
	{
		$url    = e107::url($plugin, $key, $params);
		$marker = raceevent_overview_marker($status);

		return "<li class='list-group-item'>" . $marker
			. " <a href='" . $tp->toAttribute($url) . "' target='_blank'>"
			. $tp->toHTML($label, false) . "</a></li>";
	}


	/**
	 * Wrap a section's <li> rows in a heading + classic <ul>. An empty section
	 * shows a "nothing here" note instead of an empty list (never fails).
	 *
	 * @param string $heading section caption (LAN)
	 * @param array  $rows    list of <li> strings
	 * @return string
	 */
	function raceevent_overview_section($heading, $rows)
	{
		$tp = e107::getParser();

		$body = empty($rows)
			? "<p class='text-muted'>" . $tp->toHTML(LAN_RACEEVENT_OV_EMPTY, false) . "</p>"
			: "<ul class='list-group'>" . implode('', $rows) . "</ul>";

		return "<div class='raceevent-overview-section' style='margin-bottom:25px;'>"
			. "<h3>" . $tp->toHTML($heading, false) . "</h3>"
			. $body
			. "</div>";
	}
}
