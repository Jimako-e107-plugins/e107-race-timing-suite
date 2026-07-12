<?php
/*
 * e107 website system
 *
 * racereg plugin - shared admin dispatcher / menu.
 *
 * Registration + payments plugin for the race-timing suite. This holds the
 * SHARED admin menu; each admin area is its own mode pointing at its own
 * controller/UI (mode-per-file), so adding a feature = adding a new entry script
 * plus a new entry in $modes and $adminMenu here, with no change to existing
 * modes. Structure mirrors race / timetracker / githubSync.
 *
 * Issue #22 adds two CRUD modes:
 *   - main     -> admin/admin_config.php   (registrations)
 *   - payments -> admin/admin_payments.php (payments, 1:N per registration)
 *
 * racereg holds the heaviest PII in the suite, so the whole admin area is gated
 * on the plugin's OWN admin permission (getperms('P'), which resolves to this
 * plugin's permission for files under the plugins folder). Grant it narrowly to
 * the organizer; there is NO front-end exposure in this issue.
 */

if (!defined('e107_INIT')) { exit; }

require_once("../../../class2.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// Admin LAN, flat-language layout: languages/<lang>/<lang>_admin.php.
e107::lan('racereg', true, true);


class racereg_adminArea extends e_admin_dispatcher
{
	protected $defaultMode   = 'main';
	protected $defaultAction = 'list';

	protected $modes = array(
 		'main' => array(
			'controller' => 'racereg_registration_ui',
			'path'       => null,
			'ui'         => 'racereg_registration_form_ui',
			'uipath'     => null,
		),
 
		'reginfo' => array('controller' => 'racereg_reginfo_ui', 'path' => null),
 
		'payments' => array(
			'controller' => 'racereg_payment_ui',
			'path'       => null,
			'ui'         => 'racereg_payment_form_ui',
			'uipath'     => null,
		),

		'translation' => [
			'controller' => 'translation_ui',
			'path' => null,
			'ui' => 'translation_form_ui',
			'uipath' => null
		],

		'regtracks' => array('controller' => 'racereg_regtracks_ui', 'path' => null),
	);

	// Every item carries an explicit 'url' so cross-mode navigation works no
	// matter which entry script is currently loaded (mode-per-file).
	protected $adminMenu = array(
		'main/list'       => array('caption' => LAN_RACEREG_REG_LIST,   'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_config.php'),
		'main/create'     => array('caption' => LAN_RACEREG_REG_CREATE, 'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_config.php'),

		// "More info" landing page (link to the public registration form for now;
		// future home for help info / free capacity). Routes via mode/action key.
		'reginfo/list'    => array('caption' => LAN_RACEREG_REG_INFO, 'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_registration.php'),

		'divider1' => array('divider' => true),

		'payments/list'   => array('caption' => LAN_RACEREG_PAY_LIST,   'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_payments.php'),
		'payments/create' => array('caption' => LAN_RACEREG_PAY_CREATE, 'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_payments.php'),

		'divider2' => array('divider' => true),

		// Read-only registration-by-track overview. A real dispatcher mode now:
		// the mode/action key routes to racereg_regtracks_ui::ListPage(), whose
		// table the dispatcher renders inside the admin menu + chrome.
		'regtracks/list'  => array('caption' => LAN_RACEREG_RT_TITLE, 'perm' => 'P', 'url' => '{e_PLUGIN}racereg/admin/admin_regtracks.php'),
		'translation/prefs' => array(
				'caption' => LAN_MESSAGES,
				'perm' => 'P',
				'url' => "admin_lans.php"
	));

	protected $adminMenuAliases = array(
		'main/edit'     => 'main/list',
		'payments/edit' => 'payments/list',
	);

	protected $menuTitle = LAN_GLOBAL_RACEREG_001;

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racereg's own entries are excluded from the shortcut list.
	 * Guarded on raceevent being installed so racereg degrades cleanly without it.
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racereg'))
			);
		}
	}
}
