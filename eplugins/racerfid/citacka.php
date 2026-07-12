<?php

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
