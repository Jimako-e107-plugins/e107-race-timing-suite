<?php
/*
 * e107 website system
 *
 * racereports plugin - Slovak admin language file.
 *
 * Array-style LAN file loaded via e107::lan('racereports', true, true). Overrides
 * the English_admin.php base per key; missing terms fall back to English.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	'LAN_ADMIN_RACEREPORTS_001' => 'Zostavy',
	'LAN_ADMIN_RACEREPORTS_002' => 'Kostra reportov výsledkov - tento plugin vlastní tabuľky race_result a race_archive. Logika reportov, poradia a zmrazovania archívnych snímok ešte nie je implementovaná.',
	'LAN_ADMIN_RACEREPORTS_003' => 'Nastavenie zostáv',

	// Left admin-nav menu items + page captions.
	'LAN_ADMIN_RACEREPORTS_004' => 'Prehľad preteku',
	'LAN_ADMIN_RACEREPORTS_005' => 'Online výsledky',
	'LAN_ADMIN_RACEREPORTS_006' => 'Časy na kontrolách',
	'LAN_ADMIN_RACEREPORTS_007' => 'Výsledky (SUT)',
	'LAN_ADMIN_RACEREPORTS_008' => 'Výsledky (cieľ)',
	'LAN_ADMIN_RACEREPORTS_009' => 'Štartová listina',
	'LAN_ADMIN_RACEREPORTS_105' => 'Úsek (Od-Do)',
	'LAN_ADMIN_RACEREPORTS_107' => 'Dobeh',
	'LAN_ADMIN_RACEREPORTS_116' => 'Priebeh pretekára',

	// Landing-page report links.
	'LAN_ADMIN_RACEREPORTS_010' => 'Zatiaľ žiadne preteky.',
	'LAN_ADMIN_RACEREPORTS_011' => 'Zatiaľ žiadne kategórie.',
	'LAN_ADMIN_RACEREPORTS_012' => 'Zatiaľ žiadne kontrolné body.',
	'LAN_ADMIN_RACEREPORTS_020' => 'Zostavy Online',
	'LAN_ADMIN_RACEREPORTS_021' => 'online - všetky kategórie',
	'LAN_ADMIN_RACEREPORTS_030' => 'Časy po kontrolných bodoch',
	'LAN_ADMIN_RACEREPORTS_031' => 'online - všetky kontrolné body',
	'LAN_ADMIN_RACEREPORTS_040' => 'Parity test',
	'LAN_ADMIN_RACEREPORTS_041' => 'Parity test (čistý engine vs. legacy komparátor)',
	'LAN_ADMIN_RACEREPORTS_042' => 'Parity komparátor zostáva chránený admin oprávnením. Sprievodný engine self-test (parity/engine_selftest.php) je len pre CLI a nie je prelinkovaný do webu.',

	// Overview screen: info-only list of the supported report types.
	'LAN_ADMIN_RACEREPORTS_050' => 'Zoznam podporovaných výsledkov',
	'LAN_ADMIN_RACEREPORTS_051' => 'Online',
	'LAN_ADMIN_RACEREPORTS_052' => 'Časy na kontrolách',

	// SUT screen: per-track finishers-only results.
	'LAN_ADMIN_RACEREPORTS_060' => 'Výsledky podľa trate (v cieli)',

	// NUMBER screen: priebeh pretekára - každé číslo -> zostava jedného pretekára.
	'LAN_ADMIN_RACEREPORTS_117' => 'Pretekári (podľa štartového čísla)',

	// FINISH screen: post-race results list (finishers + DNF/DSQ/DNS).
	'LAN_ADMIN_RACEREPORTS_080' => 'Výsledky v cieli',
	'LAN_ADMIN_RACEREPORTS_081' => 'cieľ - všetky kategórie',
	'LAN_ADMIN_RACEREPORTS_082' => 'cieľ - všetky trate na jednej strane',

	// START screen: štartová listina (štartujúci + skupina neštartujúcich).
	'LAN_ADMIN_RACEREPORTS_090' => 'Štartová listina',
	'LAN_ADMIN_RACEREPORTS_091' => 'štart - všetky kategórie',
	'LAN_ADMIN_RACEREPORTS_092' => 'štart - všetky trate na jednej strane',

	// DOBEH screen: per-checkpoint arrivals board links.
	'LAN_ADMIN_RACEREPORTS_108' => 'Tabuľa dobehov',
	'LAN_ADMIN_RACEREPORTS_109' => 'dobeh - všetky kontrolné body',

	// SETTINGS page (admin/admin_config.php) - plugin preferences.
	'LAN_ADMIN_RACEREPORTS_070' => 'SUT – počet desatinných miest',
	'LAN_ADMIN_RACEREPORTS_071' => 'Počet desatinných miest pre časy v cieli v reporte SUT (v cieli). 0 = celé sekundy (HH:MM:SS), ako doteraz.',
	'LAN_ADMIN_RACEREPORTS_072' => 'SUT – podfarbiť kategórie',
	'LAN_ADMIN_RACEREPORTS_073' => 'Vypnuté = čistá zostava bez farieb pozadia. Zapnuté = pozadie riadkov podľa kategórie v reporte SUT (v cieli).',
	'LAN_ADMIN_RACEREPORTS_074' => 'Online – interval obnovenia (s)',
	'LAN_ADMIN_RACEREPORTS_075' => '0 = bez automatického obnovenia. Hodnota v sekundách. Parameter ?refresh v adrese má prednosť.',
	'LAN_ADMIN_RACEREPORTS_076' => 'Cieľ – podfarbiť kategórie',
	'LAN_ADMIN_RACEREPORTS_077' => 'Zapnuté (predvolené) = pozadie riadkov podľa kategórie pri riadkoch v cieli v reporte výsledkov. Vypnuté = čistá zostava bez farieb. Riadky DNF/DSQ/DNS sa nikdy nepodfarbujú.',
	'LAN_ADMIN_RACEREPORTS_078' => 'Štart – podfarbiť kategórie',
	'LAN_ADMIN_RACEREPORTS_079' => 'Zapnuté (predvolené) = pozadie riadkov podľa kategórie pri štartujúcich v štartovej listine. Vypnuté = čistá zostava bez farieb. Riadky neštartujúcich sa nikdy nepodfarbujú.',

	// AKTUALNE (úplná matica výsledkov pretek) – report (admin/admin_aktualne.php).
	'LAN_ADMIN_RACEREPORTS_100' => 'Priebežné výsledky',
	'LAN_ADMIN_RACEREPORTS_101' => 'Úplné výsledky preteku (všetky kontroly)',
	// CUSTOM (úsek) obrazovka: dvojica rozbaľovacích zoznamov Od/Do (admin/admin_custom.php).
	'LAN_ADMIN_RACEREPORTS_106' => 'Report úseku (medzi dvoma bodmi)',
	'LAN_ADMIN_RACEREPORTS_102' => 'Od',
	'LAN_ADMIN_RACEREPORTS_103' => 'Do',
	'LAN_ADMIN_RACEREPORTS_104' => 'Otvoriť report úseku',
	'LAN_ADMIN_RACEREPORTS_093' => 'Dobeh – interval obnovenia (s)',
	'LAN_ADMIN_RACEREPORTS_094' => '0 = bez automatického obnovenia. Hodnota v sekundách. Parameter ?refresh v adrese má prednosť. Nezávislé od online intervalu – tabuľa dobehov sa obnovuje vlastným tempom.',
	'LAN_ADMIN_RACEREPORTS_095' => 'Cieľ – stĺpec kategória',
	'LAN_ADMIN_RACEREPORTS_096' => 'Vypnuté (predvolené) = bez stĺpca kategórie. Zapnuté = zobraziť stĺpec s názvom kategórie vo výsledkovej listine v cieli a v jej exporte (CSV/XLS).',
	'LAN_ADMIN_RACEREPORTS_110' => 'Výsledky – počet desatín sekundy',
	'LAN_ADMIN_RACEREPORTS_111' => '0–3. Platí pre výsledkové zostavy (online, kontroly, cieľ, štart, úsek, dobeh). Údaje sú vždy na 3 (ms); toto je len zobrazenie (oreže sa, nezaokrúhľuje). SUT má vlastné nastavenie; zostava Aktuálne sa neriadi týmto.',
	// Popisky kariet na obrazovke nastavení (e_admin_ui $preftabs). Reťazcové kľúče
	// kariet (dec/colors/refresh/custom) viažu každú voľbu na identitu karty;
	// preusporiadanie zoznamu kariet sa nikdy nedotkne volieb.
	'LAN_ADMIN_RACEREPORTS_112' => 'Desatiny',
	'LAN_ADMIN_RACEREPORTS_113' => 'Podfarbenie',
	'LAN_ADMIN_RACEREPORTS_114' => 'Obnovenie',
	'LAN_ADMIN_RACEREPORTS_115' => 'Ostatné',
);
