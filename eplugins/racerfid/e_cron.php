<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racerfid plugin - e_cron.php
 *
*/

e107::lan('racerfid', true, true);

class racerfid_cron       // plugin-folder name + '_cron'.
{

    private $plugPrefs;
 

    function config() // Setup
    {
        $cron = array();

        $cron[] = array(
            'name'            => "Import RFID Tag Data",
            'function'        => "raceTracking",
            'category'        => 'content',
            'description'     => LAN_RACETRACKING_CRON_001
        );

        return $cron;
    }

    public function raceTracking()
    {
        $plugPrefs = e107::pref('racerfid');

        // "Cron disabled" - see docs/plugins/racerfid.md, "Plugin settings".
        if (!empty($plugPrefs['cron_disabled']))
        {
            return '';
        }

        if (!empty($plugPrefs['tracking_active'])) {
            $import = new plugin_racerfid_import();
            $import->importTracking();
            return;
        }
        else {
            echo LAN_RACETRACKING_NOT_ACTIVATED;
            return;
        }
    }

}
