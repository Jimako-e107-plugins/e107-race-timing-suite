<?php
/*
 * e107 website system
 *
 * racereports plugin - sitelink (site-navigation) generator.
 *
 * Re-homes FOUR reports into the site navigation, each modelled on its timetracker
 * e_sitelink.php counterpart but pointed at racereports:
 *   - racereports_online (ONLINE report)     mirrors race_online();
 *   - racereports_points (CHECKPOINT report) mirrors race_points_list();
 *   - racereports_finish (FINISH report)     mirrors timetracker's finish builder;
 *   - racereports_start  (START report)      mirrors racereports_finish, 'start' key.
 * In all four:
 *   - links are built with e107::url('racereports', <key>, …) ('online' / 'point' /
 *     'finish', the renamed plugin-scoped keys from the e_url restructure), NOT
 *     timetracker's keys;
 *   - the function names and sitelink entries are racereports-scoped
 *     (racereports_online / racereports_points / racereports_finish) so they do NOT
 *     collide with timetracker's still-active sitelinks during the strangler
 *     transition (racereports_finish in particular is plugin-scoped so it does NOT
 *     clash with timetracker's still-active finish sitelink).
 *
 * READ-ONLY: the event structure is read straight via the db class (the same
 * #race / #race_category data source the legacy race_online() uses), so this
 * builder has NO runtime dependency on timetracker's singleton. No writes. The
 * generated href is escaped with $tp->toAttribute().
 *
 * e107 DEDUP PITFALL (handled): manage_link()/compile() collapse function-based
 * sitelinks from one plugin that all share url="#" to the first entry. The
 * workaround (as in the legacy builder) is to give EACH generated link a DISTINCT
 * link_url via e107::url() with distinct sef params - the per-race komplet entry
 * and every per-category entry resolve to different URLs, so all of them install.
 * See racereports/NOTES.md.
 *
 * Route integrity: every e107::url('racereports', 'online'|'point'|'finish', …) here
 * is covered by the part-2 route-integrity check (parity/engine_selftest.php +
 * parity/parity_check.php assert the keys resolve). The SEF aliases are NOT shared
 * with timetracker (e_url.php keeps distinct aliases - including for 'finish', whose
 * alias is not shared with timetracker's still-active finish route).
 */

if (!defined('e107_INIT'))
{
	exit;
}

class racereports_sitelink // include plugin-folder in the name.
{
	function config()
	{
		$links = array();

		// racereports-scoped (distinct from timetracker's race_online entry).
		$links[] = array(
			'name'        => "Online výsledky",
			'function'    => "racereports_online",
			'description' => "",
		);

		// racereports-scoped checkpoint list (distinct from timetracker's
		// race_points_list entry) so it does NOT collide while timetracker's
		// own sitelinks are still active during the strangler transition.
		$links[] = array(
			'name'        => "Časy na kontrolách",
			'function'    => "racereports_points",
			'description' => "",
		);

		// racereports-scoped finish report (distinct from timetracker's still-active
		// finish entry). Function/entry name kept plugin-scoped (racereports_finish)
		// so it does NOT clash with timetracker's finish sitelink during the
		// strangler transition.
		$links[] = array(
			'name'        => "Výsledková listina",
			'function'    => "racereports_finish",
			'description' => "",
		);

		// racereports-scoped START report (start-point standings). Function/entry name
		// kept plugin-scoped (racereports_start) so it does NOT clash with anything in
		// timetracker during the strangler transition.
		$links[] = array(
			'name'        => "Štartová listina",
			'function'    => "racereports_start",
			'description' => "",
		);

		// racereports-scoped AKTUALNE report (the FULL per-race results matrix).
		// Function/entry name kept plugin-scoped (racereports_aktualne) so it does
		// NOT clash with timetracker's still-active aktualne route/sitelink during
		// the strangler transition.
		 
		return $links;
	}

	/**
	 * Build the ONLINE-report sublinks: one per-race "all categories" (komplet)
	 * entry plus one entry per race_category, every link with a DISTINCT URL.
	 *
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
	function racereports_online($type = null)
	{
		$tp       = e107::getParser();
		$sql      = e107::getDb();
		$sublinks = array();

		// ---- per-race "all categories" (komplet) -----------------------------
		$races = $sql->retrieve("SELECT * FROM #race ORDER BY race_name DESC", true);
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$params = array(
					'race_sef'          => $race['race_sef'],
					'race_category_sef' => 'komplet',
				);
				// Distinct URL per race -> dedup-safe (see header).
				$url = e107::url('racereports', 'online', $params);

				$sublinks[] = array(
					'link_name'        => $race['race_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => '',
					'link_parent'      => '',
					'link_open'        => '',
					'link_class'       => 0,
				);
			}
		}

		// ---- one entry per race_category -------------------------------------
		// Same data source as the legacy race_online() ($racecats): #race joined
		// to #race_category via FIND_IN_SET, read directly (no timetracker dep).
		$query = "SELECT * FROM #race AS r, #race_category AS rc
			WHERE FIND_IN_SET(r.race_id, rc.race_category_race)
			ORDER BY race_id, race_category_sef DESC";
		$racecats = $sql->retrieve($query, true);
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				// The joined row already carries race_sef + race_category_sef, the
				// two tokens the 'online' route consumes -> distinct URL per category.
				$url = e107::url('racereports', 'online', $cat);

				$sublinks[] = array(
					'link_name'        => $cat['race_name'] . " - " . $cat['race_category_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => null,
					'link_parent'      => '',
					'link_open'        => '_blank',
					'link_class'       => 0,
				);
			}
		}

		return $sublinks;
	}

	/**
	 * Build the CHECKPOINT-report sublinks: one per race_point tied to a race
	 * (skipping start/finish), every link with a DISTINCT URL.
	 *
	 * Modelled EXACTLY on timetracker's e_sitelink.php::race_points_list() —
	 * same data source (#race ⋈ #race_point via FIND_IN_SET, ordered by
	 * race_point_order), same skip of the 'start'/'finish' codes, same
	 * $point['race_point_sef'] = race_point_code mapping and the same sublink
	 * field shape — but pointed at racereports: the link is built with
	 * e107::url('racereports', 'point', …) (the renamed plugin-scoped key from
	 * the e_url restructure), NOT timetracker's key, and both the function and
	 * its config entry are racereports-scoped (racereports_points) so nothing
	 * collides while timetracker's copy still exists. Read-only via the db
	 * class; the href is escaped with $tp->toAttribute().
	 *
	 * e107 DEDUP PITFALL (handled): each checkpoint resolves to a DISTINCT
	 * e107::url() (distinct {race_sef}/{race_point_sef} tokens), so manage_link()
	 * installs ALL of them rather than collapsing the function-based links that
	 * would otherwise share url="#" to the first entry.
	 *
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
	function racereports_points($type = null)
	{
		$tp       = e107::getParser();
		$sql      = e107::getDb();
		$sublinks = array();

		// Same join/order as timetracker's race_points_list().
		$query = "SELECT * FROM #race AS r, #race_point AS rc
			WHERE FIND_IN_SET(r.race_id, rc.race_point_race) ORDER BY race_point_order";
		$point_data = $sql->retrieve($query, true);

		if (is_array($point_data))
		{
			foreach ($point_data as $point)
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

				// Distinct URL per checkpoint -> dedup-safe (see header).
				$url = e107::url('racereports', 'point', $point);

				$sublinks[] = array(
					'link_name'        => $point['race_name'] . " - " . $point['race_point_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => null,
					'link_parent'      => '',
					'link_open'        => '_blank',
					'link_class'       => 0,
				);
			}
		}

		return $sublinks;
	}

	/**
	 * Build the FINISH-report (Výsledková listina) sublinks: one per-race "all
	 * categories" (komplet) entry plus one entry per race_category, every link with
	 * a DISTINCT URL.
	 *
	 * Mirrors racereports_online() EXACTLY (same #race / #race ⋈ #race_category data
	 * source, same dedup-safe distinct-URL approach, same sublink field shape) but
	 * pointed at the 'finish' e_url key. The finish route shares the online query
	 * contract (?r=<race_sef>&c=<race_category_sef>), so the same race_sef +
	 * race_category_sef params drive it. Both the function and its config entry are
	 * racereports-scoped (racereports_finish) so nothing collides while timetracker's
	 * own finish sitelink is still active during the strangler transition.
	 *
	 * e107 DEDUP PITFALL (handled): each entry resolves to a DISTINCT e107::url()
	 * (distinct {race_sef}/{race_category_sef} tokens), so manage_link() installs ALL
	 * of them rather than collapsing the function-based links that would otherwise
	 * share url="#" to the first entry. Read-only via the db class; the href is
	 * escaped with $tp->toAttribute().
	 *
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
	function racereports_finish($type = null)
	{
		$tp       = e107::getParser();
		$sql      = e107::getDb();
		$sublinks = array();

		// ---- per-race "all categories" (komplet) -----------------------------
		$races = $sql->retrieve("SELECT * FROM #race ORDER BY race_name DESC", true);
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$params = array(
					'race_sef'          => $race['race_sef'],
					'race_category_sef' => 'komplet',
				);
				// Distinct URL per race -> dedup-safe (see header).
				$url = e107::url('racereports', 'finish', $params);

				$sublinks[] = array(
					'link_name'        => $race['race_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => '',
					'link_parent'      => '',
					'link_open'        => '',
					'link_class'       => 0,
				);
			}
		}

		// ---- one entry per race_category -------------------------------------
		// Same data source as racereports_online(): #race joined to #race_category
		// via FIND_IN_SET, read directly (no timetracker dep).
		$query = "SELECT * FROM #race AS r, #race_category AS rc
			WHERE FIND_IN_SET(r.race_id, rc.race_category_race)
			ORDER BY race_id, race_category_sef DESC";
		$racecats = $sql->retrieve($query, true);
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				// The joined row already carries race_sef + race_category_sef, the
				// two tokens the 'finish' route consumes -> distinct URL per category.
				$url = e107::url('racereports', 'finish', $cat);

				$sublinks[] = array(
					'link_name'        => $cat['race_name'] . " - " . $cat['race_category_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => null,
					'link_parent'      => '',
					'link_open'        => '_blank',
					'link_class'       => 0,
				);
			}
		}

		return $sublinks;
	}

	/**
	 * Build the START-report (Štartová listina) sublinks: one per-race "all
	 * categories" (komplet) entry plus one entry per race_category, every link with a
	 * DISTINCT URL.
	 *
	 * Mirrors racereports_finish() / racereports_online() EXACTLY (same #race / #race
	 * ⋈ #race_category data source, same dedup-safe distinct-URL approach, same
	 * sublink field shape) but pointed at the 'start' e_url key. The start route
	 * shares the online/finish query contract (?r=<race_sef>&c=<race_category_sef>),
	 * so the same race_sef + race_category_sef params drive it. Both the function and
	 * its config entry are racereports-scoped (racereports_start) so nothing collides
	 * during the strangler transition.
	 *
	 * e107 DEDUP PITFALL (handled): each entry resolves to a DISTINCT e107::url()
	 * (distinct {race_sef}/{race_category_sef} tokens), so manage_link() installs ALL
	 * of them rather than collapsing the function-based links that would otherwise
	 * share url="#" to the first entry. Read-only via the db class; the href is
	 * escaped with $tp->toAttribute().
	 *
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
	function racereports_start($type = null)
	{
		$tp       = e107::getParser();
		$sql      = e107::getDb();
		$sublinks = array();

		// ---- per-race "all categories" (komplet) -----------------------------
		$races = $sql->retrieve("SELECT * FROM #race ORDER BY race_name DESC", true);
		if (is_array($races))
		{
			foreach ($races as $race)
			{
				$params = array(
					'race_sef'          => $race['race_sef'],
					'race_category_sef' => 'komplet',
				);
				// Distinct URL per race -> dedup-safe (see header).
				$url = e107::url('racereports', 'start', $params);

				$sublinks[] = array(
					'link_name'        => $race['race_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => '',
					'link_parent'      => '',
					'link_open'        => '',
					'link_class'       => 0,
				);
			}
		}

		// ---- one entry per race_category -------------------------------------
		// Same data source as racereports_online()/racereports_finish(): #race joined
		// to #race_category via FIND_IN_SET, read directly (no timetracker dep).
		$query = "SELECT * FROM #race AS r, #race_category AS rc
			WHERE FIND_IN_SET(r.race_id, rc.race_category_race)
			ORDER BY race_id, race_category_sef DESC";
		$racecats = $sql->retrieve($query, true);
		if (is_array($racecats))
		{
			foreach ($racecats as $cat)
			{
				// The joined row already carries race_sef + race_category_sef, the
				// two tokens the 'start' route consumes -> distinct URL per category.
				$url = e107::url('racereports', 'start', $cat);

				$sublinks[] = array(
					'link_name'        => $cat['race_name'] . " - " . $cat['race_category_name'],
					'link_url'         => $tp->toAttribute($url),
					'link_description' => '',
					'link_button'      => '',
					'link_category'    => '',
					'link_order'       => null,
					'link_parent'      => '',
					'link_open'        => '_blank',
					'link_class'       => 0,
				);
			}
		}

		return $sublinks;
	}

	/**
	 * Build the AKTUALNE-report (Priebežné výsledky / full results matrix) sublinks:
	 * ONE entry per race, every link with a DISTINCT URL.
	 *
	 * The 'aktualne' route is per-RACE (its ?p selects a race by race_id, legacy
	 * parity - the {race_id} token), so unlike the online/finish/start builders this
	 * has NO per-category sublinks: the matrix already lists every checkpoint as a
	 * column and every racer as a row. Each race resolves to a DISTINCT e107::url()
	 * ({race_id} differs per race), so manage_link() installs ALL of them rather than
	 * collapsing the function-based links that would otherwise share url="#" to the
	 * first entry (the e107 dedup pitfall handled the same way as the other builders).
	 * Read-only via the db class; the href is escaped with $tp->toAttribute().
	 * Build the DOBEH-report (checkpoint arrivals board) sublinks: one entry per
	 * race_point tied to a race (skipping start/finish), every link with a DISTINCT
	 * URL.
	 *
	 * Modelled EXACTLY on racereports_points() — same data source (#race ⋈
	 * #race_point via FIND_IN_SET, ordered by race_point_order), same skip of the
	 * 'start'/'finish' codes, same $point['race_point_sef'] = race_point_code
	 * mapping and the same sublink field shape — but pointed at the 'dobeh' e_url
	 * key (report_dobeh.php ?r=<race_sef>&p=<race_point_sef>). Both the function and
	 * its config entry are racereports-scoped (racereports_dobeh) so nothing collides
	 * while timetracker's still-active dobeh sitelink exists during the transition.
	 *
	 * e107 DEDUP PITFALL (handled): each checkpoint resolves to a DISTINCT
	 * e107::url() (distinct {race_sef}/{race_point_sef} tokens), so manage_link()
	 * installs ALL of them rather than collapsing the function-based links that
	 * would otherwise share url="#" to the first entry. Read-only via the db class;
	 * the href is escaped with $tp->toAttribute().
	 *
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
 
}
