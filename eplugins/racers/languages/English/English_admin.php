<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - languages/English/English_admin.php
 *
*/

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_RACERS_ADMIN_001'      => 'List',
    'LAN_RACERS_ADMIN_002'      => 'Add racer',
    'LAN_RACERS_ADMIN_003'      => 'Racers',
    'LAN_RACERS_ADMIN_004'      => 'Settings',
    'LAN_RACERS_ADMIN_005'      => 'Anonymize names?',
    'LAN_RACERS_ADMIN_005_HELP' => 'Replaces personal data (names) with unreadable characters',
    'LAN_RACERS_ADMIN_006'      => 'Show team next to the name?',
    'LAN_RACERS_ADMIN_006_HELP' => 'Adds the team to the displayed name',
    'LAN_RACERS_ADMIN_007'      => 'Show the local flag next to the name?',
    'LAN_RACERS_ADMIN_007_HELP' => 'If a racer is marked as local, the code entered below is shown next to their name.',
    'LAN_RACERS_ADMIN_008'      => 'Example of the local marker',
    'LAN_RACERS_ADMIN_009'      => 'First name:',
    'LAN_RACERS_ADMIN_010'      => 'Surname:',
    'LAN_RACERS_ADMIN_011'      => 'Date of birth (d.m.yyyy):',
    'LAN_RACERS_ADMIN_011_HELP' => 'Enter the date of birth in D.M.YYYY format or pick it from the calendar',
    'LAN_RACERS_ADMIN_012'      => 'Invalid date of birth',
    'LAN_RACERS_ADMIN_013'      => 'Ext. ID',
    'LAN_RACERS_ADMIN_013_HELP' => 'External application ID (terminovka.sk) for the API',
    'LAN_RACERS_ADMIN_014'      => 'Type (Individual/Relay):',
    'LAN_RACERS_ADMIN_015'      => 'Male',
    'LAN_RACERS_ADMIN_016'      => 'Female',
    'LAN_RACERS_ADMIN_017'      => 'Nationality',
    'LAN_RACERS_ADMIN_018'      => 'Local',
    'LAN_RACERS_ADMIN_019'      => 'Team/Club',
    'LAN_RACERS_ADMIN_020'      => 'Special tags / Relay member list',
    'LAN_RACERS_ADMIN_021'      => 'Text for a local racer',
    'LAN_RACERS_ADMIN_022'      => 'Country codes (for statistics)',
    'LAN_RACERS_ADMIN_023'      => 'Track',
    'LAN_RACERS_ADMIN_023_HELP' => 'Which track they will run on',
    'LAN_RACERS_ADMIN_024'      => 'Not yet assigned',
    'LAN_RACERS_ADMIN_025'      => 'Category',
    'LAN_RACERS_ADMIN_026'      => 'Bib number',
    'LAN_RACERS_ADMIN_027'      => 'Age calculation method',
    'LAN_RACERS_ADMIN_028'      => 'Today',
    'LAN_RACERS_ADMIN_029'      => 'As of Jan 1',
    'LAN_RACERS_ADMIN_030'      => 'Allow manual entry of participants.',
    // admin-scoped duplicates of LAN_RACERS_FRONT_001/002 (admin_registration.php)
    'LAN_RACERS_ADMIN_031'      => 'Manual entry of participants is not allowed.',
    'LAN_RACERS_ADMIN_032'      => 'Caution: manual entry of participants without access control is allowed.',
    // racers/list page (racers.php) tablerender heading
    'LAN_RACERS_ADMIN_033'      => 'Racers list',
    // Start-lists (admin_startlists.php) + racer-overview (admin_racerlist.php) screens
    'LAN_RACERS_ADMIN_034'      => 'Start lists',
    'LAN_RACERS_ADMIN_035'      => 'Racer overview',
    'LAN_RACERS_ADMIN_036'      => 'All tracks (all categories)',
    'LAN_RACERS_ADMIN_037'      => 'Start list — all categories',
    'LAN_RACERS_ADMIN_038'      => 'No tracks found.',
    'LAN_RACERS_ADMIN_039'      => 'No categories for this track.',
    'LAN_RACERS_ADMIN_040'      => 'Start list',
    'LAN_RACERS_ADMIN_041'      => 'Start lists by track and category',
    'LAN_RACERS_ADMIN_042'      => 'Open the racer overview',
    'LAN_RACERS_ADMIN_043'      => 'On-site registration',
    'LAN_RACERS_ADMIN_044'      => 'Show the country flag next to the name?',
    'LAN_RACERS_ADMIN_044_HELP' => 'Shows the flag of the racer\'s nationality next to the name, using the IOC to ISO code map below. Racers whose nationality is not in the map get no flag.',
    'LAN_RACERS_ADMIN_045'      => 'Flag position',
    'LAN_RACERS_ADMIN_045_HELP' => 'Before the surname, or directly after "surname firstname" (before the bib number, the local text and the team).',
    'LAN_RACERS_ADMIN_046'      => 'Before the name',
    'LAN_RACERS_ADMIN_047'      => 'After the name',
    'LAN_RACERS_ADMIN_048'      => 'Flag size',
    'LAN_RACERS_ADMIN_048_HELP' => 'Flag size in pixels (8 to 64).',
    'LAN_RACERS_ADMIN_049'      => 'Nationality to flag map',
    'LAN_RACERS_ADMIN_049_HELP' => 'How to fill it in: the left box is the code used in the Nationality field (IOC, 3 letters, e.g. GER), the right box is the country code that draws the flag (ISO, 2 letters, e.g. DE). Look the pair up in the "IOC to ISO code reference" below and copy both codes into an empty row. Clear both boxes to remove a row.',
    'LAN_RACERS_ADMIN_050'      => 'These flag map rows were not saved (IOC needs 3 letters, ISO 2 letters): [x]',
    'LAN_RACERS_ADMIN_051'      => 'IOC code',
    'LAN_RACERS_ADMIN_052'      => 'ISO code',
    'LAN_RACERS_ADMIN_053'      => 'Country flags',
    'LAN_RACERS_ADMIN_054'      => 'IOC to ISO code reference (click to open)',
    'LAN_RACERS_ADMIN_055'      => 'Country',
    'LAN_RACERS_ADMIN_056'      => 'These codes from "Country codes" (Settings tab) have no flag yet - add them to the table: [x]',
    'LAN_RACERS_ADMIN_CATEGORIES'     => 'Categories list',
    'LAN_RACERS_ADMIN_CATEGORIES_ADD' => 'Add category',
    // Cross-plugin admin-menu shortcuts: raceevent_admin_links (LAN_RACEEVENT_LINK_*).
);
