<?php
/*
 * e107 website system
 *
 * racers plugin - shared admin dispatcher / menu.
 *
 * Holds the admin dispatcher (racers_adminArea). Each admin area is its own
 * mode pointing at its own controller/UI defined in admin/admin_config.php.
 * Structure mirrors raceevent/admin/admin_menu.php.
 *
 * Bootstrap: load the framework, check the plugin's OWN admin permission
 * (getperms('P')), load the LAN files, then declare the dispatcher.
 */

if (!defined('e107_INIT')) { exit; }

require_once("../../../class2.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// Sub-folder array LAN (EN base with per-key Slovak override / fallback): admin
// strings only. The strings reused by admin_config.php's renderHelp now have
// admin-scoped duplicates (LAN_RACERS_ADMIN_031/032), so no cross-scope _front
// load is needed here.
// _global is NOT loaded here: the core loads every installed plugin's _global
// automatically on every page via the lan_global_list pref, so a manual 'global'
// load would be redundant.
e107::lan('racers', true, true);



$code = "

 
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('racer-birthday');
    const ageDisplay = document.getElementById('xy');

    if (!dateInput || !ageDisplay) {
        console.log('Required elements not found!');
        return; // Exit if the required elements are not found.
    }

    // Function to calculate the age based on the birth date
    function calculateAge(birthday) {
        const today = new Date();
        const birthDate = new Date(birthday); // Convert the input date to a Date object
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDifference = today.getMonth() - birthDate.getMonth();

        // Adjust age if the birthday hasn't occurred yet this year
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    // Function to update the age display based on the input value
    function updateAgeDisplay() {
        const inputValue = dateInput.value.trim();
        console.log('Date input value:', inputValue);

        if (!inputValue) {
            ageDisplay.textContent = '';
            return;
        }

        // Parse the input date (dd.mm.yyyy) into a proper Date object
        const [day, month, year] = inputValue.split('.').map(Number);

        // Validate date parts
        if (!isNaN(day) && !isNaN(month) && !isNaN(year)) {
            const formattedDate = new Date(year, month - 1, day); // JavaScript Date expects 0-based month

            // Check if the date is in the future
            const today = new Date();
            if (formattedDate > today) {
                ageDisplay.textContent = ''; // Display nothing if the date is in the future
                return;
            }

            const age = calculateAge(formattedDate);
            ageDisplay.textContent = age >= 0 ? age : ''; // If age is valid, display it, otherwise nothing
        } else {
            ageDisplay.textContent = ''; // Display nothing for invalid dates
        }
    }

    // Attach the event listener for changes in the input field
    dateInput.addEventListener('input', updateAgeDisplay);

    // Initialize the display if the input already has a value
    updateAgeDisplay();
});

";
e107::js('footer-inline', $code, $jquery, 1);

class racers_adminArea extends e_admin_dispatcher
{

	protected $modes = array(

		'main'	=> array(
			'controller' 	=> 'racer_ui',
			'path' 			=> null,
			'ui' 			=> 'racer_form_ui',
			'uipath' 		=> null
		),


		'cat'	=> array(
			'controller' 	=> 'race_category_ui',
			'path' 			=> null,
			'ui' 			=> 'race_category_form_ui',
			'uipath' 		=> null
		),

		// Štartovacie listiny: the start-list link directory (per track komplet +
		// per category -> racers' own startlist front page). Controller defined in
		// admin/admin_startlists.php, its OWN self-contained entry file; the
		// 'startlists/*' $adminMenu item carries 'url' => 'admin_startlists.php'.
		'startlists'	=> array(
			'controller' 	=> 'racers_startlists_ui',
			'path' 			=> null,
			'ui' 			=> 'racers_startlists_form_ui',
			'uipath' 		=> null
		),

		// Prehľad pretekárov: a single reach-link to the racers/list front page.
		// Controller defined in admin/admin_racerlist.php, its OWN entry file; the
		// 'racerlist/*' $adminMenu item carries 'url' => 'admin_racerlist.php'.
		'racerlist'	=> array(
			'controller' 	=> 'racers_racerlist_ui',
			'path' 			=> null,
			'ui' 			=> 'racers_racerlist_form_ui',
			'uipath' 		=> null
		),

		// Registrácia na mieste: a single info/link screen for on-site registration
		// (conditional on the racers/manualinput pref). Controller defined in
		// admin/admin_registration.php, its OWN entry file; the 'registration/*'
		// $adminMenu item carries 'url' => 'admin_registration.php'.
		'registration'	=> array(
			'controller' 	=> 'racers_registration_ui',
			'path' 			=> null,
			'ui' 			=> 'racers_registration_form_ui',
			'uipath' 		=> null
		),


	);


	protected $adminMenu = array(

		'main/prefs' 		=> array('caption' => LAN_RACERS_ADMIN_004, 'perm' => 'P', 'url' => 'admin_config.php'),
		'main/list'			=> array('caption' => LAN_RACERS_ADMIN_001, 'perm' => 'P', 'url' => 'admin_config.php'),
		'main/create'		=> array('caption' => LAN_RACERS_ADMIN_002, 'perm' => 'P', 'url' => 'admin_config.php'),

		'cat/list'			=> array('caption' => LAN_RACERS_ADMIN_CATEGORIES, 'perm' => '0'),
		'cat/create'		=> array('caption' => LAN_RACERS_ADMIN_CATEGORIES_ADD, 'perm' => '0'),

		'startlists/startlists'	=> array('caption' => LAN_RACERS_ADMIN_034, 'perm' => 'P', 'url' => 'admin_startlists.php'),
		'racerlist/racerlist'	=> array('caption' => LAN_RACERS_ADMIN_035, 'perm' => 'P', 'url' => 'admin_racerlist.php'),
		'registration/registration'	=> array('caption' => LAN_RACERS_ADMIN_043, 'perm' => 'P', 'url' => 'admin_registration.php'),

		// 'main/div0'      => array('divider'=> true),
		// 'main/custom'		=> array('caption'=> 'Custom Page', 'perm' => 'P'),

	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'
	);

	protected $menuTitle = LAN_RACERS_ADMIN_003;

	/**
	 * Append the centralized cross-plugin admin-menu shortcuts. The canonical
	 * nav map lives in raceevent/includes/admin_links.php; we pass our own
	 * plugin name so racers' own entries are excluded from the shortcut list.
	 * Guarded on raceevent being installed so racers degrades cleanly without it.
	 */
	public function init()
	{
		if (e107::isInstalled('raceevent'))
		{
			require_once(e_PLUGIN . 'raceevent/includes/admin_links.php');
			$this->adminMenu = array_merge(
				$this->adminMenu,
				raceevent_admin_links::get(array('racers'))
			);
		}
	}
}
