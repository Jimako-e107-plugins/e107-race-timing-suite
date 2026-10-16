<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - admin/admin_menu.php
 *
*/

/*
 * Shared admin dispatcher / menu (racers_adminArea). Modes main + cat are served
 * by admin_config.php; every other mode is its own entry file, reached via the
 * menu item's 'url'. Menu items: docs/plugins/racers.md, "Admin area".
 */

if (!defined('e107_INIT')) { exit; }

require_once("../../../class2.php");

if (!getperms('P'))
{
	e107::redirect('admin');
	exit;
}

// Admin strings only; _global is loaded by the core on every page
// (lan_global_list pref).
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

		// Start lists - admin/admin_startlists.php (own entry file, menu 'url').
		'startlists'	=> array(
			'controller' 	=> 'racers_startlists_ui',
			'path' 			=> null,
			'ui' 			=> 'racers_startlists_form_ui',
			'uipath' 		=> null
		),

		// Racer overview - admin/admin_racerlist.php (own entry file, menu 'url').
		'racerlist'	=> array(
			'controller' 	=> 'racers_racerlist_ui',
			'path' 			=> null,
			'ui' 			=> 'racers_racerlist_form_ui',
			'uipath' 		=> null
		),

		// On-site registration - admin/admin_registration.php (own entry file, menu 'url').
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

		'cat/list'			=> array('caption' => LAN_RACERS_ADMIN_CATEGORIES, 'perm' => '0', 'url' => 'admin_config.php'),
		'cat/create'		=> array('caption' => LAN_RACERS_ADMIN_CATEGORIES_ADD, 'perm' => '0', 'url' => 'admin_config.php'),

		'startlists/startlists'	=> array('caption' => LAN_RACERS_ADMIN_034, 'perm' => 'P', 'url' => 'admin_startlists.php'),
		'racerlist/racerlist'	=> array('caption' => LAN_RACERS_ADMIN_035, 'perm' => 'P', 'url' => 'admin_racerlist.php'),
		'registration/registration'	=> array('caption' => LAN_RACERS_ADMIN_043, 'perm' => 'P', 'url' => 'admin_registration.php'),

	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'
	);

	protected $menuTitle = LAN_RACERS_ADMIN_003;

	/**
	 * Append the cross-plugin admin-menu shortcuts (raceevent/includes/admin_links.php),
	 * excluding racers' own entries. Skipped when raceevent is not installed.
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
