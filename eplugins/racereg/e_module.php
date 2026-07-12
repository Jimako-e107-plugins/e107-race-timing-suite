<?php

$contactPrefs =  e107::pref('racereg');

 
if (!function_exists('cl'))
{
    function cl($key)
    {
        static $prefs = null;
        if ($prefs === null)
        {
            $prefs = e107::pref('racereg');   // your plugin shortname
        }


        // === TRICK: support LANX_ / LANED_ / LANF_ prefix for easy searching ===
        // You write: cl('LANX_CONTACT_02')
        // cl() automatically converts LANX_ → LAN_ for prefs lookup
        $realKey = str_replace(['LAN*_', 'LANX_', 'LANF_'], 'LAN_', $key);

        // Current language → English → original constant
        $lan = e_LANGUAGE;
        $val = $prefs['racereg_lans'][$realKey][$lan] ??
            $prefs['racereg_lans'][$realKey]['English'] ??
            deftrue($realKey, $realKey);

        return (trim($val) !== '') ? $val : deftrue($realKey, $realKey);
    }
}
