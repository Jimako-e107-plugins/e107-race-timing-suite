<?php
/*
 * e107 website system
 *
 * racereg plugin - Slovak admin strings.
 */

define('LAN_RACEREG_PLUGIN', 'Registrácia pretekárov');

/* ---- Admin menu ---------------------------------------------------------- */
define('LAN_RACEREG_CONFIG',     'Nastavenie');
define('LAN_RACEREG_REG_LIST',   'Registrácie');
define('LAN_RACEREG_REG_CREATE', 'Pridať registráciu');
define('LAN_RACEREG_REG_INFO',   'Ďalšie info');
define('LAN_RACEREG_PAY_LIST',   'Platby');
define('LAN_RACEREG_PAY_CREATE', 'Pridať platbu');

/* ---- More info page ------------------------------------------------------ */
define('LAN_RACEREG_REG_FORM_LINK', 'Registračný formulár');

/* ---- Registration fields ------------------------------------------------- */
define('LAN_RACEREG_TRACK',         'Trať');
define('LAN_RACEREG_FIRST_NAME',    'Meno');
define('LAN_RACEREG_LAST_NAME',     'Priezvisko');
define('LAN_RACEREG_BIRTH_DATE',    'Dátum narodenia');
define('LAN_RACEREG_STREET',        'Ulica');
define('LAN_RACEREG_CITY',          'Mesto');
define('LAN_RACEREG_POSTAL',        'PSČ');
define('LAN_RACEREG_COUNTRY',       'Krajina');
define('LAN_RACEREG_EMAIL',         'E-mail');
define('LAN_RACEREG_PHONE',         'Telefón');
define('LAN_RACEREG_CLUB',          'Klub');
define('LAN_RACEREG_REG_DATE',      'Dátum registrácie');
define('LAN_RACEREG_START_LIST_AT', 'Na štartovej listine (dátum)');
define('LAN_RACEREG_VS',            'Variabilný symbol');
define('LAN_RACEREG_VS_HELP',       'Automaticky generovaný jedinečný číselný symbol. Po vytvorení uzamknutý.');
define('LAN_RACEREG_AMOUNT_DUE',    'Suma na úhradu');
define('LAN_RACEREG_AMOUNT_DUE_HELP','V tejto verzii sa zadáva ručne. Automatické zafixovanie ceny príde neskôr.');
define('LAN_RACEREG_APPROVAL',      'Stav schválenia');
define('LAN_RACEREG_PRIVATE_NOTE',  'Súkromná poznámka');

/* ---- Approval status labels ---------------------------------------------- */
define('LAN_RACEREG_APPROVAL_0', 'Čaká');
define('LAN_RACEREG_APPROVAL_1', 'Schválené');
define('LAN_RACEREG_APPROVAL_2', 'Zamietnuté');

/* ---- Payment fields ------------------------------------------------------- */
define('LAN_RACEREG_PAY_REGISTRATION', 'Registrácia');
define('LAN_RACEREG_PAY_AMOUNT',       'Suma');
define('LAN_RACEREG_PAY_STATUS',       'Stav');
define('LAN_RACEREG_PAY_PAID_AT',      'Zaplatené dňa');
define('LAN_RACEREG_PAY_NOTE',         'Poznámka');
define('LAN_RACEREG_PAY_CREATED',      'Vytvorené');

/* ---- Payment status labels ----------------------------------------------- */
define('LAN_RACEREG_PAYST_0', 'Čaká');
define('LAN_RACEREG_PAYST_1', 'Platná');
define('LAN_RACEREG_PAYST_2', 'Chybná');
define('LAN_RACEREG_PAYST_3', 'Vrátená');

/* ---- Messages / help ----------------------------------------------------- */
define('LAN_RACEREG_SOFT_DELETED', 'Registrácia označená ako vymazaná.');
define('LAN_RACEREG_REG_HELP', 'Registrácie obsahujú osobné údaje (PII): len pre organizátora, bez zobrazenia na webe. Mazanie je „mäkké“ (ponechané pre audit / obnovu). Variabilný symbol sa generuje automaticky a je uzamknutý; suma na úhradu sa v tejto verzii zadáva ručne.');
define('LAN_RACEREG_PAY_HELP', 'Platby naviazané na registráciu. Jedna registrácia môže mať viac platieb. Zoznam filtrujte podľa registrácie cez filter.');
define('LAN_RACEREG_CONFIG_DOC_HELP',
	'<strong>Nastavenia, ktoré riadia prihlášky a platby</strong><br>'
	. 'Tieto polia sú v súvisiacich pluginoch; ich nesprávne nastavenie ticho rozbije registráciu alebo QR kód.'
	. '<br><br><strong>Príjemca (Nastavenie podujatia &rarr; raceevent)</strong><br>'
	. 'Platobný QR kód (PAY by square) potrebuje OBE polia - IBAN príjemcu aj Názov príjemcu. Názov príjemcu je povinný - bysquare bez neho nevie zakódovať QR - preto sa IBAN uložený bez názvu odmietne. SWIFT / BIC je voliteľný.'
	. '<br><br><strong>Okno registrácie (raceevent)</strong><br>'
	. 'Prihlášky sa prijímajú len medzi časom „Registrácia sa otvára“ a „Registrácia sa uzatvára“. Ktorúkoľvek stranu možno nechať prázdnu (0) bez hranice; ak sú nastavené obe, otvorenie musí byť striktne pred uzavretím.'
	. '<br><br><strong>Nastavenia pre trať (Trate &rarr; racetrack)</strong><br>'
	. 'Kapacita = maximum na štartovej listine; Neobmedzená kapacita ju ignoruje. Vyžaduje schválenie = prihlášky čakajú na organizátora. Registrácia uzavretá = na trať sa nedá prihlásiť. Cenové úrovne určujú poplatok podľa dátumu; otvorená trať bez cenovej úrovne sa považuje za bezplatnú („bez poplatku“).');

/* ---- Scaffold placeholder (kept for reference) --------------------------- */
define('LAN_RACEREG_SCAFFOLD_INFO', 'Toto je základná kostra pluginu racereg. Funkcie registrácie a platieb (prihlasovací formulár, QR PAY by square, administrácia / označenie zaplatené) pribudnú v ďalších úlohách.');
define('LAN_RACEREG_CONFIG_HELP', 'Registrácia a platby pre sadu na meranie času. Závisí od pluginov raceevent (podujatie) a race (trate). Tento plugin bude obsahovať najcitlivejšie osobné údaje v sade - prístup k administrácii obmedzte.');

/* ---- Akcie organizátora (issue #26) -------------------------------------- */
define('LAN_RACEREG_PAID_STATUS',      'Zaplatené');
define('LAN_RACEREG_PAID_STATUS_HELP', 'Odvodené z platných platieb voči sume na úhradu. Len zobrazenie - neukladá sa.');
define('LAN_RACEREG_PAID_NOFEE',       'Bez poplatku');
define('LAN_RACEREG_PAID_UNPAID',      'Nezaplatené');
define('LAN_RACEREG_PAID_PARTIAL',     'Čiastočne');
define('LAN_RACEREG_PAID_PAID',        'Zaplatené');

define('LAN_RACEREG_ACT_APPROVE',  'Schváliť');
define('LAN_RACEREG_ACT_REJECT',   'Zamietnuť');
define('LAN_RACEREG_ACT_PROMOTE',  'Posunúť');
define('LAN_RACEREG_ACT_MARKPAID', 'Označiť zaplatené');
define('LAN_RACEREG_ACT_PAYMENT',  'Zobraziť platobné údaje');
define('LAN_RACEREG_ACT_BACK',     'Späť na zoznam');

/* ---- Zdieľaný platobný pohľad + QR (issue #40) --------------------------- */
define('LAN_RACEREG_PAY_PAYEE',     'Príjemca');
define('LAN_RACEREG_PAY_IBAN',      'IBAN');
define('LAN_RACEREG_PAY_SWIFT',     'SWIFT / BIC');
define('LAN_RACEREG_PAY_NO_IBAN',   'Platobný účet ešte nebol nastavený.');
define('LAN_RACEREG_PAY_NOTE_TEXT', 'Pri platbe použite ako referenciu vyššie uvedený variabilný symbol.');
define('LAN_RACEREG_QR_TITLE',      'Zaplatiť cez QR kód');
define('LAN_RACEREG_QR_HINT',       'Naskenujte tento PAY by square kód v bankovej aplikácii a platba sa predvyplní (IBAN, suma a variabilný symbol).');
define('LAN_RACEREG_PAY_LINK_NOTE', 'Verejný platobný odkaz pre túto registráciu (pretekár ho môže použiť na neskoršiu platbu):');
define('LAN_RACEREG_CONFIRM_REJECT', 'Zamietnuť túto registráciu?');

define('LAN_RACEREG_MSG_PAID',            'Platba označená ako platná.');
define('LAN_RACEREG_MSG_RECORDED',        'Zaznamenaná platná platba vo výške [x] EUR.');
define('LAN_RACEREG_MSG_ALREADY_PAID',    'Táto registrácia je už úplne zaplatená.');
define('LAN_RACEREG_MSG_APPROVED_PLACED', 'Registrácia schválená a zaradená na štartovú listinu.');
define('LAN_RACEREG_MSG_APPROVED_SUB',    'Registrácia schválená; trať je plná, ponechaná ako náhradník.');
define('LAN_RACEREG_MSG_REJECTED',        'Registrácia zamietnutá.');
define('LAN_RACEREG_MSG_PROMOTED',        'Náhradník posunutý na štartovú listinu.');
define('LAN_RACEREG_MSG_FULL',            'Trať je plná - registráciu nie je možné zaradiť.');
define('LAN_RACEREG_MSG_NOOP',            'Nevykonala sa žiadna zmena.');
define('LAN_RACEREG_MSG_AUTOPROMOTED',    'Náhradník bol automaticky posunutý na uvoľnené miesto.');
define('LAN_RACEREG_MSG_CREATE_SUBSTITUTE', 'Trať je plná - registrácia bola pridaná ako náhradník.');

define('LAN_RACEREG_ERR_TOKEN',    'Neplatný bezpečnostný token. Skúste to znova.');
define('LAN_RACEREG_ERR_NOTFOUND', 'Registrácia alebo platba sa nenašla.');

/* ---- Rýchly filter podľa platby ------------------------------------------ */
define('LAN_RACEREG_PAIDFILTER',     'Stav platby');
define('LAN_RACEREG_PAIDFILTER_ALL', 'Všetky');

/* ---- Prehľad prihlášok podľa trate --------------------------------------- */
define('LAN_RACEREG_RT_TITLE',    'Prihlášky podľa trate');
define('LAN_RACEREG_RT_ID',       'ID trate');
define('LAN_RACEREG_RT_NAME',     'Názov trate');
define('LAN_RACEREG_RT_ALL',      'Prihlášky - všetky');
define('LAN_RACEREG_RT_APPROVED', 'Schválené');
define('LAN_RACEREG_RT_REJECTED', 'Zamietnuté');
define('LAN_RACEREG_RT_PENDING',  'Čaká');
define('LAN_RACEREG_RT_NOFEE',    'Bez poplatku');
define('LAN_RACEREG_RT_PAID',     'Zaplatené');
define('LAN_RACEREG_RT_STARTERS', 'Štartujúci');
define('LAN_RACEREG_RT_NOTRACKS', 'Nenašli sa žiadne trate.');

/* ---- Stránka „Ďalšie info“ ----------------------------------------------- */
define('LAN_RACEREG_REG_FORM_LINK', 'Registračný formulár');

/* ---- Náhľad potvrdzovacej stránky (len pre admina, inertný) -------------- */
define('LAN_RACEREG_PREVIEW_TITLE',      'Náhľad potvrdzovacej stránky');
define('LAN_RACEREG_PREVIEW_INFO',       'Náhľad len pre admina. Zobrazuje potvrdzovaciu stránku pretekára s ukážkovými dátami - nič sa neukladá a neposiela sa žiadny e-mail.');
define('LAN_RACEREG_PREVIEW_STARTLIST',  'V štartovke');
define('LAN_RACEREG_PREVIEW_SUBSTITUTE', 'Náhradník');
define('LAN_RACEREG_PREVIEW_PENDING',    'Čaká na platbu');
define('LAN_RACEREG_PREVIEW_NOQR',       "V nastavení podujatia nie sú zadané platobné údaje príjemcu, takže QR kód PAY by square sa v náhľade nezobrazí.");

/* ---- Notify (Admin -> Notify, e_notify.php) ------------------------------ */
define('LAN_RACEREG_NT_SIGNUP', 'Bola odoslaná prihláška');
define('LAN_RACEREG_NT_SIGNUP_MSG', 'Bola odoslaná nová prihláška.<br><br>Meno: [name]<br>Trať: [track]<br>Variabilný symbol: [vs]<br>Suma: [amount]<br><br>Detail: [link]');
