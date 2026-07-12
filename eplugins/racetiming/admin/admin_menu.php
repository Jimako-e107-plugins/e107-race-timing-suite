<?php
/*
 * e107 website system
 *
 * racetiming plugin - admin dispatcher / menu.
 *
 * Shared admin dispatcher for the racetiming plugin, mirroring the sibling
 * layout (racereg / racetrack / timetracker): one mode per admin area pointing
 * at its own e_admin_ui controller. racetiming owns the race_time table, so the
 * single `main` mode dispatches to the race_time CRUD controller defined in
 * admin/admin_config.php.
 *
 * SECURITY / conventions (native e107 only):
 *   - The whole screen is gated on the plugin's OWN admin permission with
 *     getperms('P'); for a file under the plugins folder 'P' resolves to this
 *     plugin's permission, registered by the adminLinks block in plugin.xml.
 *   - CRUD input passes through $tp->toDB() and output through $tp->toHTML();
 *     forms are CSRF-protected by e107's e_token (handled by the admin
 *     framework); queries go through the db class. No raw SQL.
 */

if (!defined('e107_INIT'))
{
	require_once("../../../class2.php");
}

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// LAN load order: raceevent admin terms first (shared event base), then
// racetiming's own admin terms. 3-arg loader uses the sub-folder layout and
// array-return files so missing Slovak keys fall back to English. Loaded here
// (before the dispatcher class is declared) because the class property defaults
// below reference racetiming LAN constants.
// NOTE: _global is NOT loaded here — the core loads every installed plugin's
// _global automatically on every page (admin included) via the lan_global_list
// pref, so a manual 'global' load would be redundant.
e107::lan('raceevent', true, true);
e107::lan('racetiming', true, true);

class racetiming_adminArea extends e_admin_dispatcher
{
	protected $modes = [
		'main' => [
			'controller' => 'race_time_ui',
			'path'       => null,
			'ui'         => 'race_time_form_ui',
			'uipath'     => null
		],
		// Bulk start-generation (relocated from timetracker). Controller lives in
		// admin/admin_generujstart.php; only that file loads the class, so listing
		// the mode here is safe (e_admin_ui only instantiates the active mode).
		'generate' => [
			'controller' => 'race_generate_ui',
			'path'       => null,
			'ui'         => 'race_generate_form_ui',
			'uipath'     => null
		],
	];

	protected $adminMenu = [
		'main/list' => [
			'caption' => LAN_ADMIN_RACETIMING_010,
			'perm'    => 'P',
			'url'     => 'admin_config.php'
		],
		'main/create' => [
			'caption' => LAN_ADMIN_RACETIMING_011,
			'perm'    => 'P',
			'url'     => 'admin_config.php'
		],

		'divider1' => ['divider' => true],

		// Start-generation: the default-time pref, then the generation page.
		'generate/prefs' => [
			'caption' => LAN_ADMIN_RACETIMING_052,
			'perm'    => 'P',
			'url'     => 'admin_generujstart.php'
		],
		'generate/generujStart' => [
			'caption' => LAN_ADMIN_RACETIMING_043,
			'perm'    => 'P',
			'url'     => 'admin_generujstart.php'
		],
	];

	protected $adminMenuAliases = [];

	protected $menuTitle = LAN_ADMIN_RACETIMING_001;

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racetrack's own entries are excluded from the shortcut list.
	 * Guarded on raceevent being installed so racetrack degrades cleanly without it.
	 */
	public function init()
	{
		if (e107::isInstalled('racetiming'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racetiming'))
			);
		}
	}
}
