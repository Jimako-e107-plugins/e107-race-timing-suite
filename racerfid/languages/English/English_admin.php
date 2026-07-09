<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2012 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Language file
 */

/**
 *	RFID import plugin (folder/id: racerfid) - admin language file
 *
 *	Imports RFID chip-reader records from an external database into
 *	timetracker's race_time table.
 *
 *	@package	e107_plugins
 *	@subpackage	racerfid
 */


define('LAN_RACETRACKING_TESTACCESS', 'Test database access');
define('LAN_RACETRACKING_002', 'Configure ');
define('LAN_RACETRACKING_CONFIG', 'Main configuration');
define('LAN_RACETRACKING_SERVER', 'Server:');
define('LAN_RACETRACKING_USERNAME', 'Username:');
define('LAN_RACETRACKING_PASSWORD', 'Password:');
define('LAN_RACETRACKING_DATABASE', 'Database:');
define('LAN_RACETRACKING_TABLE',   'Table:');
define('LAN_RACETRACKING_PREFIX', 'Table Prefix:');
define('LAN_RACETRACKING_PORT', "Port: ");
define('LAN_RACETRACKING_FIELDNAME', 'Name Field:');
define('LAN_RACETRACKING_FIELDSTART', 'Start Time Field:');
define('LAN_RACETRACKING_FIELDFINISH', 'Finish Time Field:');
define('LAN_RACETRACKING_FIELDNUMBER', 'Start Number Field:');

define('LAN_RACETRACKING_HELP_001', "Configure external database access here. Run Test connection. If it fails and you are sure about settings, check password value. Try log manually and don't use html entities. ");

define('LAN_RACETRACKING_HELP_002', "Only main admin has access to DB configuration. As plugin owner you can only set it off and disable cron content (cron is still running but nothing is done). Main admin can manually run cron action from Schedule Tasks Manager. ");


define('LAN_RACETRACKING_CRON_001', 'Imports records from the reader for the start and finish if they do not already exist.');

define('LAN_RACETRACKING_PREF_001', 'Plugin Active');
define('LAN_RACETRACKING_PREF_001_HELP', "When disabled, the import cron does not run and the manual import cannot be started.");
define('LAN_RACETRACKING_PREF_002', 'Cron disabled');
define('LAN_RACETRACKING_PREF_002_HELP', "When enabled, the cron returns quietly without doing anything, even if the plugin is active.");

/* ---- Manual import / refresh interval (admin_config renderHelp + prefs) --- */
define('LAN_RACETRACKING_PREF_REFRESH',      'Seconds for manual import from the reader');
define('LAN_RACETRACKING_PREF_REFRESH_HELP', '0 - import will not run');
define('LAN_RACETRACKING_HELP_MANUAL_LINK',  'Link for manual import:');
define('LAN_RACETRACKING_HELP_MANUAL_BTN',   'Manual refresh');
define('LAN_RACETRACKING_HELP_INTERVAL_NOTE','The refresh interval must be greater than 0');

/* ---- Test-connection / import output (import.php) ------------------------ */
define('LAN_RACETRACKING_USED_TABLE',         'Used table name:');
define('LAN_RACETRACKING_TOTAL_RECORDS',      'Total records:');
define('LAN_RACETRACKING_RECORDS_WITH',       'Records with');
define('LAN_RACETRACKING_POINT',              'Point:');
define('LAN_RACETRACKING_NOT_IMPORTED',       'not imported');
define('LAN_RACETRACKING_IMPORTED_FROM_FIELD','imported from field');
define('LAN_RACETRACKING_INVALID_IDENTIFIER', 'Invalid SQL identifier (only letters, numbers and underscore allowed):');
define('LAN_RACETRACKING_DEPS_MISSING',       'The timetracker and race plugins must be installed for the import to run.');

/* ---- Batch worker / cron (citacka.php, e_cron.php) ----------------------- */
define('LAN_RACETRACKING_IMPORT_DONE',   'Import finished');
define('LAN_RACETRACKING_NO_INTERVAL',   'Refresh interval is not set');
define('LAN_RACETRACKING_NOT_ACTIVATED', 'Import is not activated');
