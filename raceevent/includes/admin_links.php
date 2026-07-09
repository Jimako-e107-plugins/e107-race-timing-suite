<?php
/*
 * e107 website system
 *
 * raceevent base plugin - centralized admin-menu cross-links.
 *
 * Single source of truth for the cross-plugin shortcuts shown in every
 * race-suite admin dispatcher. Each plugin's admin/admin_menu.php (or root
 * admin_config.php) calls raceevent_admin_links::get() from its init() and
 * array_merge()s the result into $adminMenu, passing its own plugin name to
 * exclude self.
 *
 * The captions live in raceevent's admin LAN (raceevent owns them); the URLs
 * are static {e_PLUGIN} strings plus a fixed mode/action query - no user input.
 * Each target admin page enforces its own getperms('P'), so a shortcut never
 * bypasses access control. Missing plugins are hidden silently (no warning) so
 * the leaf plugins (racerfid, terminovka) stay independently installable.
 */

if (!defined('e107_INIT')) { exit; }

class raceevent_admin_links
{
	/**
	 * Build the cross-link admin-menu entries.
	 *
	 * @param array $exclude accepts BOTH plugin names (drops the whole plugin's
	 *                       entries) AND individual item keys (drops one entry).
	 *                       Pass your own plugin name to exclude self; pass e.g.
	 *                       'racers_cat' to drop a single link from one menu.
	 * @return array associative array ready for array_merge() into $adminMenu.
	 */
	public static function get(array $exclude = array())
	{
		// raceevent owns the cross-link captions; load its admin LAN before the
		// map references the LAN_RACEEVENT_LINK_* constants.
		e107::lan('raceevent', true, true);

		// ONE canonical nav map, grouped by plugin in a stable order. URLs are
		// the verified PRIMARY admin entry path from each plugin.xml <adminLinks>:
		// every suite plugin lives under admin/admin_config.php EXCEPT terminovka,
		// which still keeps admin_config.php at its plugin root.
		// Icons are FontAwesome 4 glyph names (e107 2.4 renders the 'icon' key as
		// image_src + '.glyph'). Queries point at each dispatcher's default mode/
		// action; an empty query lands on the plugin's own default admin page.
		$nav = array(
			'raceevent' => array(
				'url'   => '{e_PLUGIN}raceevent/admin/admin_config.php',
				'items' => array(
					'raceevent_cfg' => array('caption' => LAN_RACEEVENT_LINK_EVENT,   'query' => '?mode=main&action=prefs'),
				),
			),
			'racetrack' => array(
				'url'   => '{e_PLUGIN}racetrack/admin/admin_config.php',
				'items' => array(
					'racetrack_list'    => array('caption' => LAN_RACEEVENT_LINK_TRACKS,  'query' => '?mode=main&action=list'),
					// Archive is its OWN entry file (admin_archive.php); admin_config.php
					// no longer serves the archive mode, so this item carries its own
					// 'url' (per-item override below) instead of the block's admin_config.
					'racetrack_archive' => array('caption' => LAN_RACEEVENT_LINK_ARCHIVE, 'url' => '{e_PLUGIN}racetrack/admin/admin_archive.php', 'query' => '?mode=archive&action=list'),
				),
			),
			'racers' => array(
				'url'   => '{e_PLUGIN}racers/admin/admin_config.php',
				'items' => array(
					'racers_list' => array('caption' => LAN_RACEEVENT_LINK_RACERS,       'query' => '?mode=main&action=list'),
					'racers_cat'  => array('caption' => LAN_RACEEVENT_LINK_CATEGORIES,  'query' => '?mode=cat&action=list'),
				),
			),
			'racereg' => array(
				'url'   => '{e_PLUGIN}racereg/admin/admin_config.php',
				'items' => array(
					'racereg_link' => array('caption' => LAN_RACEEVENT_LINK_REGISTRATION,  'query' => '?mode=main&action=list'),
				),
			),
			'racerfid' => array(
				'url'   => '{e_PLUGIN}racerfid/admin/admin_config.php',
				'items' => array(
					// Dispatcher declares no defaultMode/defaultAction - empty query
					// lands on its own default admin page (no guessed mode/action).
					'racerfid_link' => array('caption' => LAN_RACEEVENT_LINK_RFID,   'query' => ''),
				),
			),
			'racetiming' => array(
				'url'   => '{e_PLUGIN}racetiming/admin/admin_config.php',
				'items' => array(
					// Dispatcher declares no defaultMode/defaultAction - empty query
					// lands on its own default admin page (no guessed mode/action).
					'racetiming_link' => array('caption' => LAN_RACEEVENT_LINK_TIMING,   'query' => ''),
				),
			),
			'racereports' => array(
				'url'   => '{e_PLUGIN}racereports/admin/admin_config.php',
				'items' => array(
					'racereports_link' => array('caption' => LAN_RACEEVENT_LINK_REPORTS,  'query' => ''),
				),
			),
			'terminovka' => array(
				'url'   => '{e_PLUGIN}terminovka/admin_config.php',
				'items' => array(
					'terminovka_link' => array('caption' => LAN_RACEEVENT_LINK_TERMINOVKA,   'query' => '?mode=main&action=prefs'),
				),
			),
		);

		$menu  = array();
		$first = true;

		foreach ($nav as $plugin => $def)
		{
			// Drop the whole plugin when its name is excluded (self-exclusion).
			if (in_array($plugin, $exclude, true)) { continue; }

			// Silently hide a plugin that is not installed (no warning) - keeps
			// the leaf plugins installable without their siblings.
			if (!e107::isInstalled($plugin)) { continue; }

			// Collect this plugin's items whose KEY is not excluded.
			$items = array();
			foreach ($def['items'] as $key => $item)
			{
				if (in_array($key, $exclude, true)) { continue; }

				$items[$key] = array(
					'caption' => $item['caption'],
					'perm'    => 'P',
					// No item defines 'icon'; guard the key so PHP 8 does not warn on
					// the undefined index (the menu renders fine with an empty icon).
					'icon'    => varset($item['icon'], ''),
					// A per-item 'url' wins over the plugin block's shared url - the
					// archive item points at its own entry file (admin_archive.php).
					'url'     => varset($item['url'], $def['url']),
					'query'   => $item['query'],
				);
			}

			// Nothing left for this plugin - skip it entirely.
			if (empty($items)) { continue; }

			// ONE divider, above the first emitted cross-link only. Unique key so
			// the array_merge() into the host $adminMenu never collides.
			if ($first)
			{
				$menu['divider_crosslinks'] = array('divider' => true);
				$first = false;
			}

			foreach ($items as $key => $entry)
			{
				$menu[$key] = $entry;
			}
		}

		return $menu;
	}
}
