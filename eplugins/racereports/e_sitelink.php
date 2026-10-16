<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racereports plugin - e_sitelink.php
 *
*/

/*
 * Site-navigation builders (docs/plugins/racereports.md, "Front-end"). Read-only; every
 * href is escaped with toAttribute().
 *
 * e107 dedup pitfall: manage_link()/compile() collapse function-based sitelinks of
 * one plugin that share the same url to the first entry. Every generated link
 * therefore gets a DISTINCT e107::url() (distinct sef params).
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

		$links[] = array(
			'name'        => "Online výsledky",
			'function'    => "racereports_online",
			'description' => "",
		);

		$links[] = array(
			'name'        => "Časy na kontrolách",
			'function'    => "racereports_points",
			'description' => "",
		);

		$links[] = array(
			'name'        => "Výsledková listina",
			'function'    => "racereports_finish",
			'description' => "",
		);

		$links[] = array(
			'name'        => "Štartová listina",
			'function'    => "racereports_start",
			'description' => "",
		);

		 
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
	 * @param string|null $type unused (sitelink builder signature)
	 * @return array sublink rows
	 */
	function racereports_points($type = null)
	{
		$tp       = e107::getParser();
		$sql      = e107::getDb();
		$sublinks = array();

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
	 * Build the FINISH-report sublinks: same shape as racereports_online(), on the
	 * 'finish' route.
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
	 * Build the START-report sublinks: same shape as racereports_online(), on the
	 * 'start' route.
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

 
}
