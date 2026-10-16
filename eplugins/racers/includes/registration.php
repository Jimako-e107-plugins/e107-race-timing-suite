<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - includes/registration.php
 *
*/

if (!defined('e107_INIT')) { exit; }

/**
 * On-site registration category matching, called by racer_category_handler.php
 * (the AJAX endpoint of the on-site form). Kept separate from includes/racers.php
 * so it can be reused (e.g. by racereg).
 *
 * SQL: the gender goes through $tp->toDB(), the track id is int-cast and the
 * age is computed (int), so no posted value reaches the query raw.
 */
class plugin_racers_registration {

    /**
     * Return the list of matching race_category rows for the given inputs.
     *
     *   - gender match (race_category_gender = '{$gender}') when a gender is given,
     *   - age range match (age_from <= age <= age_to) when a birthday is given,
     *   - FIND_IN_SET(raceId, race_category_race) only when a track is given.
     * $calculationMethod is undefined, so the age is always computed as of today
     * (the startforage pref is read into $plPref but not used).
     *
     * @param string $birthday on-site birthday string (e.g. 'd.m.yyyy')
     * @param string $gender   race_category_gender value
     * @param int    $raceId   track id (int-cast); 0 / empty => no track filter
     * @return array matching race_category rows (same set the handler builds <option>s from)
     */
    public static function getCategories($birthday, $gender, $raceId = 0): array
    {
        $sql = e107::getDb();
        $tp  = e107::getParser();

        $race = (int) $raceId;
        $gender = $tp->toDB((string) $gender);

        $plPref = e107::pref('racers', 'startforage');

        $where1 = "";
        $where2 = "";
        $where3 = "";

        if (!empty($birthday))
        {
            // Age
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            //$age = $today->diff($birthDate)->y;

            // Age as of 1 January or as of today ($calculationMethod: see docblock).
            if ($calculationMethod === 'startOfYear')
            {
                $startOfYear = new DateTime($today->format('Y') . '-01-01');
                $age = $startOfYear->diff($birthDate)->y;
            }
            else
            {
                $age = $today->diff($birthDate)->y;
            }

            $age = (int) $age;
            $where1 = "  AND {$age} >= race_category_age_from  AND {$age} <= race_category_age_to ";
        }

        if (!empty($gender))
        {
            $where2 = "  race_category_gender = '{$gender}'  ";
        }

        if (!empty($race))
        {
            $where3 = " AND FIND_IN_SET({$race}, race_category_race) > 0  ";
        }

        $categories = $sql->retrieve('race_category', '*',  $where2 . $where1 .  $where3, true);

        return is_array($categories) ? $categories : array();
    }

}
