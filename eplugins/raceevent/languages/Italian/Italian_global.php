<?php
/*
 * e107 website system
 *
 * raceevent plugin - Italian global language file.
 *
 * Array-style LAN file loaded via e107::lan('raceevent', 'global', true).
 * Overrides the English_global.php base per key; any key omitted here falls
 * back to the English value by design.
 */

if (!defined('e107_INIT')) { exit; }

return array(
    'LAN_GLOBAL_RACEEVENT_001' => "Evento gara",
    'LAN_GLOBAL_RACEEVENT_002' => "Configurazione dell'evento",
    'LAN_GLOBAL_RACEEVENT_003' => "Plugin base della suite di cronometraggio. Conserva la configurazione dell'evento (modello a evento singolo: un sito web = un evento) come preferenze del plugin, oltre alla manutenzione della stagione e alla diagnostica trasversale della suite. Tutti gli altri plugin dipendono da esso; non dichiara tabelle proprie.",
);
