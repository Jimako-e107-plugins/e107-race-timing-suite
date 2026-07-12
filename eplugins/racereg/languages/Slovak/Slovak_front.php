<?php
/*
 * e107 website system
 *
 * racereg plugin - Slovak front strings (issue #24).
 *
 * Loaded on the front-end via e107::lan('racereg', '', true) (languages/<Language>/<Language>_front.php).
 */

if (!defined('e107_INIT')) { exit; }

return array(
	// ---- Page ----
	'LAN_RACEREG_SIGNUP_TITLE'  => 'Prihláška na pretek',
	'LAN_RACEREG_CONFIRM_TITLE' => 'Prihláška prijatá',
	'LAN_RACEREG_INTRO'         => 'Vyplňte formulár nižšie a prihláste sa na trať. Polia označené * sú povinné.',
	'LAN_RACEREG_SUBMIT'        => 'Odoslať prihlášku',
	'LAN_RACEREG_SELECT_TRACK'  => '— vyberte trať —',
	'LAN_RACEREG_GDPR_LABEL'    => 'Súhlasím so spracovaním mojich osobných údajov na účel tejto registrácie (GDPR). Pozri informácie o ochrane a uchovávaní údajov.',

	// ---- Field labels (front) ----
	'LAN_RACEREG_TRACK'         => 'Trať',
	'LAN_RACEREG_CATEGORY'      => 'Kategória',
	'LAN_RACEREG_CATEGORY_NONE' => 'Neurčené',
	'LAN_RACEREG_NATIONALITY'   => 'Národnosť',
	'LAN_RACEREG_LOCAL'         => 'Miestny pretekár',
	'LAN_RACEREG_FIRST_NAME'    => 'Meno',
	'LAN_RACEREG_LAST_NAME'     => 'Priezvisko',
	'LAN_RACEREG_BIRTH_DATE'    => 'Dátum narodenia',
	'LAN_RACEREG_STREET'        => 'Ulica',
	'LAN_RACEREG_CITY'          => 'Mesto',
	'LAN_RACEREG_POSTAL'        => 'PSČ',
	'LAN_RACEREG_COUNTRY'       => 'Krajina',
	'LAN_RACEREG_EMAIL'         => 'E-mail',
	'LAN_RACEREG_PHONE'         => 'Telefón',
	'LAN_RACEREG_CLUB'          => 'Klub',
	'LAN_RACEREG_VS'            => 'Variabilný symbol',
	'LAN_RACEREG_AMOUNT_DUE'    => 'Suma na úhradu',

	// ---- Confirmation ----
	'LAN_RACEREG_CONFIRM_SUMMARY'  => 'Zhrnutie',
	'LAN_RACEREG_CONFIRM_PAYMENT'  => 'Platobné údaje',
	'LAN_RACEREG_STATE_STARTLIST'  => 'Ste potvrdený na štartovej listine.',
	'LAN_RACEREG_STATE_SUBSTITUTE' => 'Trať je plná — boli ste zaradený medzi náhradníkov. V prípade uvoľnenia miesta budete posunutý vyššie.',
	'LAN_RACEREG_STATE_PENDING'    => 'Vaša registrácia bola prijatá a čaká na schválenie organizátorom.',
	'LAN_RACEREG_PAY_PAYEE'        => 'Príjemca',
	'LAN_RACEREG_PAY_IBAN'         => 'IBAN',
	'LAN_RACEREG_PAY_SWIFT'        => 'SWIFT / BIC',
	'LAN_RACEREG_QR_TITLE'         => 'Zaplatiť cez QR kód',
	'LAN_RACEREG_QR_HINT'          => 'Naskenujte tento PAY by square kód v bankovej aplikácii a platba sa predvyplní (IBAN, suma a variabilný symbol).',
	'LAN_RACEREG_PAY_NO_IBAN'      => 'Platobný účet ešte nebol nastavený. Organizátor poskytne platobné údaje.',
	'LAN_RACEREG_PAY_NOTE_TEXT'    => 'Pri platbe použite ako referenciu vyššie uvedený variabilný symbol.',

	// ---- Platobný odkaz + tokenizovaná platobná stránka (issue #40) ----
	'LAN_RACEREG_PAY_LINK_NOTE'     => 'Uložte si tento odkaz, aby ste sa neskôr mohli vrátiť k platobným údajom a QR kódu:',
	'LAN_RACEREG_PAY_DETAILS_TITLE' => 'Platobné údaje',
	'LAN_RACEREG_PAY_NOT_FOUND'     => 'Tento platobný odkaz je neplatný alebo už nie je dostupný.',
	'LAN_RACEREG_PAY_GREETING'      => 'Platobné údaje pre [x]',
	'LAN_RACEREG_PAID_STATUS'       => 'Stav platby',
	'LAN_RACEREG_PAID_NOFEE'        => 'Bez poplatku',
	'LAN_RACEREG_PAID_UNPAID'       => 'Nezaplatené',
	'LAN_RACEREG_PAID_PARTIAL'      => 'Čiastočne zaplatené',
	'LAN_RACEREG_PAID_PAID'         => 'Zaplatené',

	// ---- Errors / messages ----
	'LAN_RACEREG_ERR_FORM'         => 'Opravte zvýraznené polia a skúste to znova.',
	'LAN_RACEREG_ERR_CSRF'         => 'Kontrola bezpečnostného tokenu zlyhala. Znovu načítajte stránku a skúste to znova.',
	'LAN_RACEREG_ERR_SPAM'         => 'Vaše odoslanie sa nepodarilo spracovať.',
	'LAN_RACEREG_ERR_WINDOW'       => 'Registrácia na toto podujatie je momentálne uzavretá.',
	'LAN_RACEREG_ERR_NOTRACKS'     => 'Momentálne nie sú otvorené žiadne trate na registráciu.',
	'LAN_RACEREG_ERR_TRACK'        => 'Vyberte platnú trať.',
	'LAN_RACEREG_ERR_TRACK_CLOSED' => 'Registrácia na vybranú trať je uzavretá.',
	'LAN_RACEREG_ERR_REQUIRED'     => 'Toto pole je povinné.',
	'LAN_RACEREG_ERR_EMAIL'        => 'Zadajte platnú e-mailovú adresu.',
	'LAN_RACEREG_ERR_BIRTH'        => 'Zadajte platný dátum narodenia.',
	'LAN_RACEREG_ERR_GDPR'         => 'Na registráciu je potrebný súhlas.',
	'LAN_RACEREG_ERR_SAVE'         => 'Registráciu sa nepodarilo uložiť. Skúste to znova.',
);
