<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racerfid plugin - citacka.php
 *
*/

/*
 * Manual import page (user view: docs/plugins/racerfid.md, "The import itself").
 * Re-runs the import every refresh_interval seconds via an HTTP Refresh header.
 */

require_once('../../class2.php');
if (!getperms('P'))
{
    e107::redirect('admin');
    exit;
}

e107::lan('racerfid', true, true);

$plugPrefs = e107::pref('racerfid');

$interval = (int) varset($plugPrefs['refresh_interval'], 0);

if ($interval > 0)
{
    if (!empty($plugPrefs['tracking_active']))
    {
        $import = new plugin_racerfid_import();
        $import->importTracking();
        echo LAN_RACETRACKING_IMPORT_DONE . ": " . date("d.m.Y H:i:s", time());
        header("Refresh: $interval");
    }
    else
    {
        echo LAN_RACETRACKING_NOT_ACTIVATED;
    }
}
else
{
    echo LAN_RACETRACKING_NO_INTERVAL;
}
