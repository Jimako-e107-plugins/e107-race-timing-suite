<?php
/*
 * e107 website system
 *
 * racetiming plugin - Slovak admin language file.
 *
 * Array-style LAN file loaded via e107::lan('racetiming', true, true). Overrides
 * the English_admin.php base per key; missing terms fall back to English.
 */

if (!defined('e107_INIT')) { exit; }

return array(
	'LAN_ADMIN_RACETIMING_001' => 'Meranie časov',
	'LAN_ADMIN_RACETIMING_002' => 'Kostra merania časov - tento plugin vlastní tabuľku kontrolných časov race_time. Výpočtový engine merania ešte nie je implementovaný.',
	'LAN_ADMIN_RACETIMING_003' => 'Správa merania časov',

	// --- race_time CRUD admin (admin/admin_config.php) ---
	'LAN_ADMIN_RACETIMING_010' => 'Záznamy časov',
	'LAN_ADMIN_RACETIMING_011' => 'Pridať záznam času',
	'LAN_ADMIN_RACETIMING_020' => 'Kontrolný bod',
	'LAN_ADMIN_RACETIMING_021' => 'Vyberte kontrolný bod / merací bod',
	'LAN_ADMIN_RACETIMING_022' => 'Štartovné číslo',
	'LAN_ADMIN_RACETIMING_023' => 'Zadajte štartovné číslo (vrátane vedúcich núl)',
	'LAN_ADMIN_RACETIMING_024' => 'Namerený čas',
	'LAN_ADMIN_RACETIMING_025' => 'Namerený čas na kontrolnom bode',
	'LAN_ADMIN_RACETIMING_026' => 'Stav / Cieľový status',
	'LAN_ADMIN_RACETIMING_027' => 'DNF = Nedokončil, DSQ = Diskvalifikovaný, DNS = Nenastúpil',
	'LAN_ADMIN_RACETIMING_028' => 'Vytvorené',
	'LAN_ADMIN_RACETIMING_029' => 'Aktualizované',

	// --- bulk start-generation (admin/admin_generujstart.php) ---
	// Relocated from timetracker (LAN_TR_* there). Slovak overrides; any missing
	// key falls back to the English_admin.php base.
	'LAN_ADMIN_RACETIMING_040' => 'Generovanie štartu',
	'LAN_ADMIN_RACETIMING_041' => 'Predvolený čas štartu',
	'LAN_ADMIN_RACETIMING_042' => 'Použije sa ako predvolená hodnota pri generovaní štartu.',
	'LAN_ADMIN_RACETIMING_043' => 'Generovať štart',
	'LAN_ADMIN_RACETIMING_044' => 'Zvoľte trate a čas',
	'LAN_ADMIN_RACETIMING_045' => 'Namerený čas',
	'LAN_ADMIN_RACETIMING_046' => 'Nastav aktuálny čas',
	'LAN_ADMIN_RACETIMING_047' => 'Generuj časy štartov',
	'LAN_ADMIN_RACETIMING_048' => 'Generujem preteky ID {ID}',
	'LAN_ADMIN_RACETIMING_049' => 'Tento pretekár už má vygenerovaný štart',
	'LAN_ADMIN_RACETIMING_050' => 'Štart vygenerovaný',
	'LAN_ADMIN_RACETIMING_051' => 'Pretekár nenastúpil',
	'LAN_ADMIN_RACETIMING_052' => 'Nastavenie času štartu',
	'LAN_ADMIN_RACETIMING_053' => 'Nevybrali ste trať',
);
