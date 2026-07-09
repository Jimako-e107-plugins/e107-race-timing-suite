<?php
/*
 * e107 website system
 *
 * race plugin - Slovak admin language file (issue #37).
 *
 * Array-style LAN file (returns the terms) loaded via
 * e107::lan('racetrack', true, true). Overrides the English_admin.php base per key;
 * any term left out here falls back to the English value (see English_admin.php
 * for why the array form is required).
 */

if (!defined('e107_INIT')) { exit; }

return array(
	// Track edit form.
	'LAN_ADMIN_RACE_001'      => 'SEF URL pre trať',
	'LAN_ADMIN_RACE_001_HELP' => 'Časť URL adresy, na ktorej sa zobrazujú dáta preteku. Aktuálny web + functionalita + SEF URL ',
	'LAN_ADMIN_RACE_002'      => 'SEF URL pre archív',
	'LAN_ADMIN_RACE_002_HELP' => 'Časť URL adresy, na ktorej sa zobrazí archív preteku. Aktuálny web + SEF URL archívu. Môže sa líšiť od SEF URL aktívneho preteku a mal by obsahovať rok kvôli opakovaným pretekom v ďalších rokoch.',

	'LAN_ADMIN_RACE_003'      => 'Zoznam tratí',
	'LAN_ADMIN_RACE_004'      => 'Pridať trať',

	'LAN_ADMIN_POINTS'        => 'Kontrolné body',
	'LAN_ADMIN_POINTS_ADD'    => 'Pridať kontrolu',

	// --- Archív (race_archive) + tlačidlo "Archivovať" v zozname tratí --------
	'LAN_ADMIN_ARCHIVE'            => 'Archív',
	'LAN_ADMIN_ARCHIVE_ADD'       => 'Pridať archív',
	'LAN_ADMIN_ARCHIVE_TRACK'     => 'Trať',
	'LAN_ADMIN_ARCHIVE_UNLINKED'  => 'Neprepojený archív',
	'LAN_ADMIN_ARCHIVE_CREATED'   => 'Vytvorené',
	'LAN_ADMIN_ARCHIVE_UPDATED'   => 'Aktualizované',
	'LAN_ADMIN_ARCHIVE_ARCHIVOVAT' => 'Archivovať',
	'LAN_ADMIN_ARCHIVE_REGENERATE' => 'Pregenerovať',
	'LAN_ADMIN_ARCHIVE_VIEW'       => 'Zobraziť',
	'LAN_ADMIN_ARCHIVE_MSG_CREATED'  => 'Archív vytvorený',
	'LAN_ADMIN_ARCHIVE_MSG_UPDATED'  => 'Archív aktualizovaný',
	'LAN_ADMIN_ARCHIVE_MSG_FAIL'     => 'Generovanie archívu zlyhalo',
	'LAN_ADMIN_ARCHIVE_MSG_NORACE'   => 'Pretek sa nenašiel',
	'LAN_ADMIN_ARCHIVE_MSG_NO_RR'    => 'Plugin Racereports nie je nainštalovaný - archív sa nedá vygenerovať.',
	'LAN_ADMIN_ARCHIVE_MSG_BAD_TOKEN' => 'Neplatný bezpečnostný token - požiadavka bola ignorovaná.',
	'LAN_ADMIN_ARCHIVE_NOTE'         => 'Prepojený archív (nastavená Trať) sa dá pregenerovať tlačidlom Pregenerovať. Zobraziť vždy ukáže uložený záznam a nikdy negeneruje. Odpojením (Trať = Neprepojený archív) záznam zostane, ale Pregenerovať sa skryje.',

	// Track edit form tab label (issue #34).
	'LAN_ADMIN_RACE_TAB_TRACK' => 'Trate',
	// Opt-in Registration tab label (shown only when racereg is installed).
	'LAN_ADMIN_RACE_TAB_REG'   => 'Registrácia',

	// --- Registration-config + price-tier admin strings (issue #30) ----------
	// Registračné nastavenia trate + kapacita.
	'LAN_ADMIN_RACE_CAPACITY'       => 'Kapacita',
	'LAN_ADMIN_RACE_CAPACITY_HELP'  => 'Maximálny počet pretekárov na štartovej listine. Ignoruje sa, ak je zapnutá neobmedzená kapacita.',
	'LAN_ADMIN_RACE_UNLIMITED'      => 'Neobmedzená kapacita',
	'LAN_ADMIN_RACE_UNLIMITED_HELP' => 'Ak je zapnuté, kapacita sa nekontroluje a všetci sa dostanú na štartovú listinu.',
	'LAN_ADMIN_RACE_APPROVAL'       => 'Vyžaduje schválenie',
	'LAN_ADMIN_RACE_APPROVAL_HELP'  => 'Ak je zapnuté, prihlášky čakajú na schválenie a na štartovú listinu sa zaraďujú až pri schválení (nie automaticky podľa kapacity).',
	'LAN_ADMIN_RACE_CLOSED'         => 'Registrácia uzavretá',
	'LAN_ADMIN_RACE_CLOSED_HELP'    => 'Ak je zapnuté, na túto trať sa nedá prihlásiť.',

	// Cenové úrovne podľa dátumu (podradená tabuľka).
	'LAN_ADMIN_PRICES'              => 'Cenník (úrovne)',
	'LAN_ADMIN_PRICES_ADD'          => 'Pridať cenovú úroveň',
	'LAN_ADMIN_PRICE_TRACK'         => 'Trať',
	'LAN_ADMIN_PRICE_VALUE'         => 'Cena',
	'LAN_ADMIN_PRICE_VALUE_HELP'    => 'Suma v EUR (napr. 15.00).',
	'LAN_ADMIN_PRICE_FROM'          => 'Platná od',
	'LAN_ADMIN_PRICE_FROM_HELP'     => 'Dátum a čas, od ktorého platí táto cena. Pre prihlášku platí úroveň s najneskorším dátumom, ktorý je <= aktuálny čas.',

	// --- Upozornenia pri uložení otvorenej trate (issue #47) -----------------
	'LAN_ADMIN_RACE_CAP_WARN'  => 'Táto trať je otvorená na registráciu, ale jej kapacita je 0 a nie je nastavená ako neobmedzená - nikoho nemožno zaradiť na štartovú listinu. Nastavte kapacitu alebo zapnite neobmedzenú kapacitu.',
	'LAN_ADMIN_RACE_FREE_WARN' => 'Táto trať je otvorená na registráciu, ale nemá žiadnu cenovú úroveň - prihlášky sa budú považovať za bezplatné („bez poplatku“). Ak sa má účtovať poplatok, pridajte cenovú úroveň.',
 

	// --- Stránka pomocníka ku konfigurácii trate (issue #47) -----------------
	'LAN_ADMIN_RACE_TRACK_HELP' =>
		'<strong>Nastavenia registrácie pre trať</strong><br>'
		. '<strong>Kapacita</strong> - maximálny počet pretekárov na štartovej listine. '
		. '<strong>Neobmedzená kapacita</strong> - ak je zapnutá, kapacita sa ignoruje a zaradí sa každý. '
		. 'Otvorená trať (Registrácia uzavretá = vypnuté) s kapacitou 0 a bez neobmedzenej kapacity nemôže nikoho zaradiť - pri uložení sa zobrazí upozornenie.<br>'
		. '<strong>Vyžaduje schválenie</strong> - prihlášky čakajú na organizátora a zaradia sa až po schválení (nie automaticky podľa kapacity).<br>'
		. '<strong>Registrácia uzavretá</strong> - ak je zapnuté, na túto trať sa nedá prihlásiť.<br>'
		. '<strong>Cenové úrovne</strong> - poplatky podľa dátumu (menu Cenník); platí úroveň s najneskorším „Platná od“, ktoré je &lt;= teraz. '
		. 'Otvorená trať bez cenovej úrovne sa považuje za bezplatnú („bez poplatku“) - pri uložení sa zobrazí upozornenie.<br>'
		. '<em>Kontrolné body pre štart a cieľ musia mať kódy start a finish, inak sa zobrazia ako klasické kontroly.</em>',
);
