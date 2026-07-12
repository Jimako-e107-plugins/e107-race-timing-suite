<?php
/*
 * e107 website system
 *
 * racereports plugin - Slovak front language file.
 *
 * Array-style LAN file loaded on the front-end via
 * e107::lan('racereports', '', true). Overrides the English front strings per
 * key; any key missing here falls back to languages/English/English_front.php.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	'LAN_RACEREPORTS_LIVE_STATE'     => 'aktuálny stav',
	'LAN_RACEREPORTS_ALL_CATEGORIES' => 'všetky kategórie',

	// Online report - auto-refresh page help (collapsible note at the top). Trusted
	// HTML body, echoed raw so the headings/list/<code> markup render.
	'LAN_RACEREPORTS_HELP_REFRESH_TITLE' => 'Online výsledky — automatické obnovovanie',
	'LAN_RACEREPORTS_HELP_REFRESH_BODY'  =>
		'<p>Zostava sa môže sama obnovovať, aby hlásateľ či divák videli aktuálne poradie'
		. ' bez ručného načítania stránky.</p>'
		. '<p><strong>Východiskový interval (nastavenia pluginu):</strong> V'
		. ' „Online – interval obnovenia“ zadáte počet sekúnd. Platí pre všetky online'
		. ' okná, kde nie je v adrese uvedené inak. Napríklad 30 = obnova každých 30'
		. ' sekúnd. 0 = bez automatického obnovovania.</p>'
		. '<p><strong>Iný interval pre konkrétne okno (parameter v adrese):</strong>'
		. ' Pridaním <code>?refresh=</code> s počtom sekúnd do adresy nastavíte interval'
		. ' len pre toto okno (napr. <code>...?refresh=10</code>). Parameter v adrese má'
		. ' prednosť — nastavenie pluginu sa preň úplne ignoruje.</p>'
		. '<ul>'
		. '<li><code>?refresh=10</code> → toto okno sa obnovuje každých 10 sekúnd.</li>'
		. '<li><code>?refresh=0</code> → toto okno sa neobnovuje vôbec, aj keď je v'
		. ' nastaveniach interval zadaný (napr. „zmrazenie“ okna, ktoré si chcete v pokoji'
		. ' prezrieť).</li>'
		. '<li>Platí len pre dané okno/adresu, nemení nastavenie pre ostatných.</li>'
		. '</ul>'
		. '<p>Typické použitie: projektor v cieli rýchlo (<code>?refresh=10</code>),'
		. ' interná obrazovka pomalšie (<code>?refresh=60</code>), kontrolné okno'
		. ' zmrazené (<code>?refresh=0</code>).</p>',
	'LAN_RACEREPORTS_NO_RACE'        => 'Nie je zadaný pretek.',
	'LAN_RACEREPORTS_NO_POINT'       => 'Nie je zadaná kontrola.',

	// Finish (post-race results list) report - heading suffix.
	'LAN_RACEREPORTS_FINISH_TITLE'   => 'Výsledky',

	// Start (start-point standings list) report - heading suffix.
	'LAN_RACEREPORTS_START_TITLE'    => 'Štartová listina',

	// Custom / segment (between-two-points split) report - heading names both points.
	'LAN_RACEREPORTS_OD'             => 'Od',
	'LAN_RACEREPORTS_DO'             => 'Do',

	// SUT (per-track finishers-only results) report.
	'LAN_RACEREPORTS_SUT_TITLE'      => 'Výsledky',
	'LAN_RACEREPORTS_SUT_NO_FINISH'  => 'Zatiaľ nikto v cieli.',
	'LAN_RACEREPORTS_COL_RANK'        => 'Poradie',
	'LAN_RACEREPORTS_COL_TIME'        => 'Čas',
	'LAN_RACEREPORTS_COL_SURNAME'     => 'Priezvisko',
	'LAN_RACEREPORTS_COL_FIRSTNAME'   => 'Meno',
	'LAN_RACEREPORTS_COL_GENDER'      => 'Pohlavie',
	'LAN_RACEREPORTS_COL_BIRTHDATE'   => 'Dátum narodenia',
	'LAN_RACEREPORTS_COL_NATIONALITY' => 'Národnosť',
	'LAN_RACEREPORTS_COL_BIB'         => 'Štartové číslo',
	// Dobeh (tabuľa dobehov) hlavička - stĺpec kategórie ("<kategória> — <N.>").
	'LAN_RACEREPORTS_COL_CATEGORY'    => 'Kategória',
	'LAN_RACEREPORTS_COL_NAME'        => 'Meno',
	'LAN_RACEREPORTS_COL_STATUS'      => 'Stav',

	// SUT export buttons (CSV / fake-XLS download of the displayed results).
	'LAN_RACEREPORTS_EXPORT_CSV'      => 'CSV',
	'LAN_RACEREPORTS_EXPORT_XLS'      => 'XLS',

	// AKTUALNE (full per-race results matrix) report.
	'LAN_RACEREPORTS_AKT_TITLE'         => 'Priebežné výsledky',
	'LAN_RACEREPORTS_AKT_UNKNOWN_RACE'  => 'Neznámy pretek.',
	'LAN_RACEREPORTS_AKT_EMPTY'         => 'Žiadni pretekári.',
	'LAN_RACEREPORTS_AKT_COL_POR'       => 'Por.',
	'LAN_RACEREPORTS_AKT_COL_NAME'      => 'Meno',
	'LAN_RACEREPORTS_AKT_COL_CAT'       => 'Kat.',
	'LAN_RACEREPORTS_AKT_COL_TIME'      => 'Čas',
	'LAN_RACEREPORTS_AKT_COL_CATRANK'   => 'Rank v kategórii',

	// NUMBER (priebeh jedného pretekára) - neznáme číslo, hlavičky stĺpcov a stav DNF/DSQ.
	'LAN_RACEREPORTS_NUM_UNKNOWN_BIB'   => 'Neznáme štartové číslo',
	'LAN_RACEREPORTS_NUM_COL_POINT'     => 'Kontrola',
	'LAN_RACEREPORTS_NUM_COL_TIMEOFDAY' => 'Čas dňa',
	'LAN_RACEREPORTS_NUM_COL_SPLIT'     => 'Medzičas',
	'LAN_RACEREPORTS_NUM_COL_SEGMENT'   => 'Úsek',
	'LAN_RACEREPORTS_NUM_DNF'           => 'DNF',
	'LAN_RACEREPORTS_NUM_DSQ'           => 'DSQ',
);
