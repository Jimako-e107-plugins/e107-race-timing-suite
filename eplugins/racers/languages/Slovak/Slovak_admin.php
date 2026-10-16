<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - languages/Slovak/Slovak_admin.php
 *
*/

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_RACERS_ADMIN_001'      => 'Zoznam',
    'LAN_RACERS_ADMIN_002'      => 'Pridať pretekára',
    'LAN_RACERS_ADMIN_003'      => 'Pretekári',
    'LAN_RACERS_ADMIN_004'      => 'Nastavenia',
    'LAN_RACERS_ADMIN_005'      => 'Anonymizovať mená?',
    'LAN_RACERS_ADMIN_005_HELP' => 'Nahradí osobné údaje (mená) nečitateľnými znakmi',
    'LAN_RACERS_ADMIN_006'      => 'Zobrazovať pri mene team?',
    'LAN_RACERS_ADMIN_006_HELP' => 'Doplní k menu zobrazenie tímu',
    'LAN_RACERS_ADMIN_007'      => 'Zobrazovať pri mene príznak lokálny?',
    'LAN_RACERS_ADMIN_007_HELP' => 'Ak je pretekár označený ako lokálny, zobrazí sa pri jeho mene kód zadaný nižšie.',
    'LAN_RACERS_ADMIN_008'      => 'Príklad pre označenie lokálny',
    'LAN_RACERS_ADMIN_009'      => 'Krstné meno:',
    'LAN_RACERS_ADMIN_010'      => 'Priezvisko:',
    'LAN_RACERS_ADMIN_011'      => 'Dátum narodenia (d.m.yyyy):',
    'LAN_RACERS_ADMIN_011_HELP' => 'Zadajte dátum narodenia vo formáte D.M.RRRR alebo vyberte z kalendára',
    'LAN_RACERS_ADMIN_012'      => 'Neplatný dátum narodenia',
    'LAN_RACERS_ADMIN_013'      => 'Ext.ID',
    'LAN_RACERS_ADMIN_013_HELP' => 'ID externej aplikácie (terminovka.sk) pre API',
    'LAN_RACERS_ADMIN_014'      => 'Typ (Jednotlivec/Štafeta):',
    'LAN_RACERS_ADMIN_015'      => 'Muž',
    'LAN_RACERS_ADMIN_016'      => 'Žena',
    'LAN_RACERS_ADMIN_017'      => 'Národnosť',
    'LAN_RACERS_ADMIN_018'      => 'Miestny',
    'LAN_RACERS_ADMIN_019'      => 'Team/Klub',
    'LAN_RACERS_ADMIN_020'      => 'Špeciálne štítky / Zoznam členov štafety',
    'LAN_RACERS_ADMIN_021'      => 'Text pre miestneho pretekára',
    'LAN_RACERS_ADMIN_022'      => 'Kódy štátov (pre štatistiky)',
    'LAN_RACERS_ADMIN_023'      => 'Trať',
    'LAN_RACERS_ADMIN_023_HELP' => 'Na ktorej trati pobeží',
    'LAN_RACERS_ADMIN_024'      => 'Ešte neurčené',
    'LAN_RACERS_ADMIN_025'      => 'Kategória',
    'LAN_RACERS_ADMIN_026'      => 'Štartovacie číslo',
    'LAN_RACERS_ADMIN_027'      => 'Spôsob výpočtu veku',
    'LAN_RACERS_ADMIN_028'      => 'Dnes',
    'LAN_RACERS_ADMIN_029'      => 'K 1.1.',
    'LAN_RACERS_ADMIN_030'      => 'Povoliť manuálne zadávanie účastníkov.',
    // admin-scoped duplicates of LAN_RACERS_FRONT_001/002 (admin_registration.php)
    'LAN_RACERS_ADMIN_031'      => 'Manuálne zadávanie účastníkov nie je povolené.',
    'LAN_RACERS_ADMIN_032'      => 'Pozor, manuálne zadávanie účastníkov bez kontroly prístupu je povolené.',
    // racers/list page (racers.php) tablerender heading
    'LAN_RACERS_ADMIN_033'      => 'Zoznam pretekárov',
    // Start-lists (admin_startlists.php) + racer-overview (admin_racerlist.php) screens
    'LAN_RACERS_ADMIN_034'      => 'Štartovacie listiny',
    'LAN_RACERS_ADMIN_035'      => 'Prehľad pretekárov',
    'LAN_RACERS_ADMIN_036'      => 'Všetky trate (všetky kategórie)',
    'LAN_RACERS_ADMIN_037'      => 'Štartovacia listina — všetky kategórie',
    'LAN_RACERS_ADMIN_038'      => 'Žiadne trate.',
    'LAN_RACERS_ADMIN_039'      => 'Žiadne kategórie pre túto trať.',
    'LAN_RACERS_ADMIN_040'      => 'Štartovacia listina',
    'LAN_RACERS_ADMIN_041'      => 'Štartovacie listiny podľa trate a kategórie',
    'LAN_RACERS_ADMIN_042'      => 'Otvoriť prehľad pretekárov',
    'LAN_RACERS_ADMIN_043'      => 'Registrácia na mieste',
    'LAN_RACERS_ADMIN_044'      => 'Zobraziť vlajku krajiny pri mene?',
    'LAN_RACERS_ADMIN_044_HELP' => 'Pri mene zobrazí vlajku podľa národnosti pretekára, podľa prevodnej tabuľky kódov IOC na ISO nižšie. Pretekári, ktorých národnosť v tabuľke nie je, vlajku nemajú.',
    'LAN_RACERS_ADMIN_045'      => 'Umiestnenie vlajky',
    'LAN_RACERS_ADMIN_045_HELP' => 'Pred priezviskom, alebo hneď za „priezvisko meno“ (pred štartovým číslom, textom pre domáceho a tímom).',
    'LAN_RACERS_ADMIN_046'      => 'Pred menom',
    'LAN_RACERS_ADMIN_047'      => 'Za menom',
    'LAN_RACERS_ADMIN_048'      => 'Veľkosť vlajky',
    'LAN_RACERS_ADMIN_048_HELP' => 'Veľkosť vlajky v pixeloch (8 až 64).',
    'LAN_RACERS_ADMIN_049'      => 'Prevod národnosti na vlajku',
    'LAN_RACERS_ADMIN_049_HELP' => 'Ako vyplniť: ľavé pole je kód z poľa Národnosť (IOC, 3 písmená, napr. GER), pravé pole je kód krajiny, podľa ktorého sa kreslí vlajka (ISO, 2 písmená, napr. DE). Dvojicu nájdete v „Prehľade kódov IOC → ISO“ nižšie; oba kódy prepíšte do prázdneho riadku. Vymazaním oboch polí riadok odstránite.',
    'LAN_RACERS_ADMIN_050'      => 'Tieto riadky prevodnej tabuľky vlajok sa neuložili (IOC musí mať 3 písmená, ISO 2 písmená): [x]',
    'LAN_RACERS_ADMIN_051'      => 'Kód IOC',
    'LAN_RACERS_ADMIN_052'      => 'Kód ISO',
    'LAN_RACERS_ADMIN_053'      => 'Vlajky krajín',
    'LAN_RACERS_ADMIN_054'      => 'Prehľad kódov IOC → ISO (kliknutím otvoríte)',
    'LAN_RACERS_ADMIN_055'      => 'Krajina',
    'LAN_RACERS_ADMIN_056'      => 'Tieto kódy z „Kódy štátov“ (karta Nastavenie) zatiaľ nemajú vlajku – doplňte ich do tabuľky: [x]',
    'LAN_RACERS_ADMIN_CATEGORIES'     => 'Zoznam kategórií',
    'LAN_RACERS_ADMIN_CATEGORIES_ADD' => 'Pridať kategóriu',
    // Cross-plugin admin-menu shortcuts: raceevent_admin_links (LAN_RACEEVENT_LINK_*).
);
