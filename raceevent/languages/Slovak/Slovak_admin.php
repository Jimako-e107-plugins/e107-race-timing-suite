<?php
/*
 * e107 website system
 *
 * raceevent base plugin - Slovak admin strings.
 */

define('LAN_RACEEVENT_PLUGIN', 'Podujatie');

/* ---- Admin menu ---------------------------------------------------------- */
define('LAN_RACEEVENT_CONFIG', 'Nastavenie podujatia');

/* ---- Cross-plugin admin-menu shortcuts (raceevent_admin_links helper) ----- */
define('LAN_RACEEVENT_LINK_EVENT',      '» Podujatie');
define('LAN_RACEEVENT_LINK_TRACKS',     '» Trate');
define('LAN_RACEEVENT_LINK_ARCHIVE',    '» Archív');
define('LAN_RACEEVENT_LINK_RACERS',     '» Pretekári');
define('LAN_RACEEVENT_LINK_CATEGORIES', '» Kategórie');
define('LAN_RACEEVENT_LINK_TERMINOVKA', '» Terminovka.sk');
define('LAN_RACEEVENT_LINK_REGISTRATION', '» Registrácia');
define('LAN_RACEEVENT_LINK_RFID',         '» RFID import');
define('LAN_RACEEVENT_LINK_TIMING',       '» Časomiera');
define('LAN_RACEEVENT_LINK_REPORTS',      '» Zostavy');

define('LAN_RACEEVENT_TAB_EVENT',      	'Podujatie');
define('LAN_RACEEVENT_TAB_REGISTRATION', 'Registrácia');

/* ---- Event fields (plugin prefs) ----------------------------------------- */
define('LAN_RACEEVENT_NAME',             'Názov podujatia');
define('LAN_RACEEVENT_DATE',             'Dátum podujatia');
define('LAN_RACEEVENT_CITY',             'Mesto / dedina konania');
define('LAN_RACEEVENT_LOCATION',         'Konkrétne miesto / zázemie');
define('LAN_RACEEVENT_DESCRIPTION',      'Popis');
define('LAN_RACEEVENT_ORGANIZER',        'Organizátor');

/* ---- Event flags (plugin prefs) ------------------------------------------ */
define('LAN_RACEEVENT_IS_CHARITY',       'Charitatívne podujatie');
define('LAN_RACEEVENT_IS_CHILDREN_RUNS', 'Súčasťou sú detské behy');
define('LAN_RACEEVENT_IS_DOG_ALLOWED',   'Povolená účasť so psom');

/* ---- Registration window + payee (plugin prefs) -------------------------- */
define('LAN_RACEEVENT_REG_START',       'Registrácia sa otvára');
define('LAN_RACEEVENT_REG_START_HELP',  'Prihlášky sa prijímajú od tohto okamihu. Prázdne (0) = bez dolnej hranice. Ak sú nastavené obe hranice, otvorenie musí byť pred uzavretím.');
define('LAN_RACEEVENT_REG_END',         'Registrácia sa uzatvára');
define('LAN_RACEEVENT_REG_END_HELP',    'Prihlášky sa prijímajú do tohto okamihu. Prázdne (0) = bez hornej hranice. Ak sú nastavené obe hranice, uzavretie musí byť po otvorení.');
define('LAN_RACEEVENT_PAYEE_IBAN',      'IBAN príjemcu');
define('LAN_RACEEVENT_PAYEE_IBAN_HELP', 'Bankový účet (IBAN) zobrazený ako cieľ platby na potvrdzovacej stránke registrácie. Medzery sa automaticky odstránia. Povinný (spolu s názvom príjemcu) pre platobný QR kód.');
define('LAN_RACEEVENT_PAYEE_NAME',      'Názov príjemcu');
define('LAN_RACEEVENT_PAYEE_NAME_HELP', 'Meno majiteľa účtu / príjemcu zobrazené pri platobných údajoch. Povinné vždy, keď je zadaný IBAN: PAY by square bez neho nevie vygenerovať QR kód.');
define('LAN_RACEEVENT_PAYEE_SWIFT',     'SWIFT / BIC príjemcu');
define('LAN_RACEEVENT_PAYEE_SWIFT_HELP','Voliteľný identifikátor banky (BIC), 8 alebo 11 znakov. Medzery sa automaticky odstránia.');
define('LAN_RACEEVENT_IBAN_WARN',       'IBAN bol uložený, ale nevyzerá platne (formát / kontrolný súčet). Skontrolujte ho.');
define('LAN_RACEEVENT_SWIFT_WARN',      'SWIFT / BIC bol uložený, ale nevyzerá platne (očakáva sa 8 alebo 11 znakov). Skontrolujte ho.');
define('LAN_RACEEVENT_PAYEE_NAME_REQUIRED', 'Pri zadanom IBAN je potrebný názov príjemcu, inak nie je možné vygenerovať platobný QR kód. Zmena nebola uložená - doplňte názov príjemcu a uložte znova.');
define('LAN_RACEEVENT_REG_WINDOW_INVALID',  'Registrácia sa nemôže uzavrieť skôr (ani v rovnakom čase) ako sa otvára. Zmena nebola uložená - nastavte čas uzavretia po čase otvorenia.');
define('LAN_RACEEVENT_PAYEE_INCOMPLETE_WARN', 'Je nastavené okno registrácie, ale blok príjemcu je neúplný (chýba IBAN alebo názov príjemcu). Platobný QR kód a pokyny nebudú fungovať, kým nevyplníte oboje.');

/* ---- Help ---------------------------------------------------------------- */
define('LAN_RACEEVENT_CONFIG_HELP', 'Nastavenie podujatia. Toto je inštalácia pre jedno podujatie: podujatie je uložené v nastaveniach pluginu (nie ako riadky v databáze) a zdieľané so zvyškom sady na meranie času cez e107::getPlugConfig(\'raceevent\').'
	. '<hr><strong>Platba / QR (povinné polia)</strong><br>'
	. 'Platobný QR kód (PAY by square) potrebuje OBE polia - IBAN príjemcu aj Názov príjemcu. Názov príjemcu je povinný - bysquare bez neho nevie zakódovať QR, preto sa IBAN zadaný bez názvu pri uložení odmietne. SWIFT / BIC je voliteľný.'
	. '<hr><strong>Okno registrácie</strong><br>'
	. 'Prihlášky sa prijímajú len medzi časom Registrácia sa otvára a Registrácia sa uzatvára. Ktorúkoľvek stranu možno nechať prázdnu (0) bez hranice. Ak sú nastavené obe, otvorenie musí byť striktne pred uzavretím - inak sa uloženie odmietne. Ak je okno nastavené, ale blok príjemcu je neúplný, zobrazí sa upozornenie, že platby / QR nebudú fungovať.');

/* --- Maintenance page (udrzba) --- */
define('LAN_TR_UDRZBA_ARCHIVE_WARN_TITLE',  'Pred začatím novej sezóny skontrolujte archív!');
define('LAN_TR_UDRZBA_ARCHIVE_WARN_LINKED', '[x] archívnych záznamov je stále prepojených s traťou.');
define('LAN_TR_UDRZBA_ARCHIVE_WARN_ADVICE', 'Odporúčame všetky odpojiť, inak sa môžu aktualizovať alebo brániť mazaniu pretekov.');

define('LAN_TR_UDRZBA_EXECUTE',        'Vykonať');
define('LAN_TR_UDRZBA_EDIT_PREFS',     'Upraviť preferencie');

define('LAN_TR_UDRZBA_ACTIVE_HEADING', 'Údržba – aktívne tabuľky');
define('LAN_TR_UDRZBA_CLEAN',          'Vyčistiť');
define('LAN_TR_UDRZBA_ITEM',           'Položka');
define('LAN_TR_UDRZBA_COUNT',          'Počet záznamov');

define('LAN_TR_UDRZBA_LEGACY_HEADING', 'Legacy / staré tabuľky');
define('LAN_TR_UDRZBA_DROP',           'DROP');
define('LAN_TR_UDRZBA_TABLE_DESC',     'Tabuľka / popis');
define('LAN_TR_UDRZBA_STATUS',         'Stav');
define('LAN_TR_UDRZBA_NOTEXIST',       'neexistuje');
define('LAN_TR_UDRZBA_EMPTY',          'prázdna');
define('LAN_TR_UDRZBA_ROWS',           'riadkov');

define('LAN_TR_UDRZBA_PLUGINS_HEADING', 'Kontrola potrebných pluginov');
define('LAN_TR_UDRZBA_PLUGIN',         'Plugin');
define('LAN_TR_UDRZBA_DESC',           'Popis');
define('LAN_TR_UDRZBA_MANDATORY',      'Povinný');
define('LAN_TR_UDRZBA_PREFS',          'Preferencie');
define('LAN_TR_UDRZBA_PLUGIN_MISSING', 'Neexistuje v eplugins');
define('LAN_TR_UDRZBA_PLUGIN_DISABLED', 'Vypnutý');
define('LAN_TR_UDRZBA_PLUGIN_ACTIVE',  'Aktívny');
define('LAN_TR_UDRZBA_YES',            'ÁNO');
define('LAN_TR_UDRZBA_NO',             'Nie');
define('LAN_TR_UDRZBA_NONE',           'žiadne');

/* --- Maintenance action messages --- */
define('LAN_TR_UDRZBA_MSG_BAD_TOKEN',      'Neplatný bezpečnostný token. Akcia bola zrušená.');
define('LAN_TR_UDRZBA_MSG_CLEANED',        'Tabuľka [x] vyčistená ([y] riadkov).');
define('LAN_TR_UDRZBA_MSG_LEGACY_CLEANED', 'Legacy tabuľka [x] vyčistená.');
define('LAN_TR_UDRZBA_MSG_LEGACY_DROPPED', 'Legacy tabuľka [x] odstránená (DROP).');
define('LAN_TR_UDRZBA_MSG_INVALID_TABLE',  'Neplatná tabuľka pre mazanie.');
define('LAN_TR_UDRZBA_MSG_TRACKS_BLOCKED', 'Trate nemožno vymazať, kým ostatné tabuľky obsahujú dáta. Najprv vymažte ostatné tabuľky.');
define('LAN_TR_UDRZBA_TRACKS_HINT',        'najprv vymažte ostatné tabuľky');

/* --- Maintenance table labels --- */
define('LAN_TR_TBL_RACES',      'Trate');
define('LAN_TR_TBL_POINTS',     'Kontroly');
define('LAN_TR_TBL_CATEGORIES', 'Kategórie');
define('LAN_TR_TBL_RACERS',     'Pretekári / štartovné čísla');
define('LAN_TR_TBL_RESULTS',    'Výsledky');
define('LAN_TR_TBL_TIMES',      'Časy na kontrolách');
define('LAN_TR_TBL_READER',     'Čítačka');
define('LAN_TR_TBL_OLD_RACERS', 'Starý zoznam pretekárov');

/* --- Required plugins (titles / descriptions) --- */
define('LAN_TR_PLUG_RACEREPORTS',       'Zostavy (výsledkové listiny)');
define('LAN_TR_PLUG_RACEREPORTS_DESC',  'Hlavný plugin pre meranie času, kontroly a výsledky.');
define('LAN_TR_PLUG_RACE',              'Race (základné preteky)');
define('LAN_TR_PLUG_RACE_DESC',         'Základný plugin pre definovanie pretekov a tratí.');
define('LAN_TR_PLUG_RACERS',            'Racers (pretekári)');
define('LAN_TR_PLUG_RACERS_DESC',       'Správa pretekárov, kategórií, štartovných čísel.');
define('LAN_TR_PLUG_RACETRACKING',      'RFID import (čítačka)');
define('LAN_TR_PLUG_RACETRACKING_DESC', 'Import časov z čítačky.');
define('LAN_TR_PLUG_TERMINOVKA',        'Terminovka.sk export');
define('LAN_TR_PLUG_TERMINOVKA_DESC',   'Voliteľný export výsledkov na terminovka.sk.');
define('LAN_TR_PLUG_REGISTRACIA',       'Registrácia');
define('LAN_TR_PLUG_REGISTRACIA_DESC',  'Prihlasovanie na preteky (plánované).');

/* --- Navigation check (checklinks) --- */
define('LAN_RACEEVENT_CHECKLINKS',        'Kontrola navigácie');
define('LAN_RACEEVENT_CHECKLINKS_HELP',   'Vypíše navigačné odkazy, ktoré volajú funkciu pluginu (link_function = plugin::metóda). Označí odkazy, ktorých plugin alebo funkcia už neexistuje, a odkazy, kde sa vlastník nezhoduje s volaným pluginom. Rozbité odkazy možno skryť (nastaviť na triedu „nobody“); upraviť alebo zmazať ich v správcovi navigácie.');
define('LAN_RACEEVENT_CL_HEADING',        'Navigačné odkazy s funkciou');
define('LAN_RACEEVENT_CL_COL_LINK',       'Odkaz');
define('LAN_RACEEVENT_CL_COL_FUNCTION',   'Funkcia');
define('LAN_RACEEVENT_CL_COL_PLUGIN',     'Plugin');
define('LAN_RACEEVENT_CL_COL_METHOD',     'Metóda');
define('LAN_RACEEVENT_CL_COL_OWNER',      'Vlastník');
define('LAN_RACEEVENT_CL_COL_STATUS',     'Stav');
define('LAN_RACEEVENT_CL_COL_ACTION',     'Akcia');
define('LAN_RACEEVENT_CL_OK',             'OK');
define('LAN_RACEEVENT_CL_BROKEN_PLUGIN',  'Plugin chýba');
define('LAN_RACEEVENT_CL_BROKEN_METHOD',  'Funkcia chýba');
define('LAN_RACEEVENT_CL_MALFORMED',      'Neplatný formát funkcie');
define('LAN_RACEEVENT_CL_OWNER_MISMATCH', 'Nesúlad vlastníka a funkcie');
define('LAN_RACEEVENT_CL_HIDE',           'Skryť');
define('LAN_RACEEVENT_CL_EDIT',           'Upraviť');
define('LAN_RACEEVENT_CL_EXECUTE',        'Skryť označené');
define('LAN_RACEEVENT_CL_ALREADY_HIDDEN', 'už skryté');
define('LAN_RACEEVENT_CL_NONE',           'Nenašli sa žiadne navigačné odkazy s funkciou.');
define('LAN_RACEEVENT_CL_MSG_BAD_TOKEN',  'Neplatný bezpečnostný token. Akcia bola zrušená.');
define('LAN_RACEEVENT_CL_MSG_HIDDEN',     'Odkaz [x] skrytý (nastavený na nobody).');

/* --- Event overview (Prehľad preteku) admin screen --- */
define('LAN_RACEEVENT_OV_MENU', 'Prehľad preteku');
define('LAN_RACEEVENT_OV_HELP', 'Krížový rozcestník odkazov na všetky reporty preteku (rovnaký výstup ako verejná stránka „preteky“). Pri každom odkaze ukazuje, či cieľový súbor reportu existuje na disku: zelená = hotové, červená = chýba alebo route nie je registrovaná (napr. štartový report, kým nie je vytvorený). Iba na čítanie.');
