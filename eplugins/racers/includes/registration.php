<?php

if (!defined('e107_INIT')) { exit; }

/**
 * Standalone on-site registration category-match logic.
 *
 * This is a FAITHFUL 1:1 copy of the matching logic in the current working
 * racer_category_handler.php (post-revert form). It is intentionally separate
 * from includes/racers.php (the tested race model) so it can be reused later
 * (e.g. by racereg) without mixing into that model.
 *
 * STEP 1 of the age+gender -> category redo: this file is dormant / not wired up.
 * Nothing calls it yet; the on-site handler keeps running on its own code.
 * No improvements are made here on purpose (no bound params, no gender
 * whitelist, no CSRF, no validation) - those are separate later steps.
 */
class plugin_racers_registration {

    /**
     * Return the list of matching race_category rows for the given inputs.
     *
     * Reproduces the working handler exactly:
     *   - gender match (race_category_gender = '{$gender}') when a gender is given,
     *   - age range match (age_from <= age <= age_to) when a birthday is given,
     *   - FIND_IN_SET(raceId, race_category_race) ONLY when a real track is given.
     * Age is computed the SAME way the working handler does (copied verbatim,
     * including the undefined $calculationMethod branch that always falls through
     * to the today-diff calculation).
     *
     * Birthday is the on-site 'd.m.yyyy' string the working handler handles.
     *
     * @param string $birthday on-site birthday string (e.g. 'd.m.yyyy')
     * @param string $gender   race_category_gender value
     * @param int    $raceId   track id; 0 / empty => no track filter
     * @return array matching race_category rows (same set the handler builds <option>s from)
     */
    public static function getCategories($birthday, $gender, $raceId = 0): array
    {
        $sql = e107::getDb();

        $race = $raceId;

        $plPref = e107::pref('racers', 'startforage');

        $where1 = "";
        $where2 = "";
        $where3 = "";

        if (!empty($birthday))
        {
            // Calculate age
            $birthDate = new DateTime($birthday);
            $today = new DateTime();
            //$age = $today->diff($birthDate)->y;

            // Skontrolujte spôsob výpočtu veku (či sa má počítať k 1. januáru alebo k dnešnému dňu)
            if ($calculationMethod === 'startOfYear')
            {
                // Nastavíme dátum na 1. januára aktuálneho roka
                $startOfYear = new DateTime($today->format('Y') . '-01-01');
                // Vypočítať vek k 1. januáru
                $age = $startOfYear->diff($birthDate)->y;
            }
            else
            {
                // Vypočítať vek k dnešnému dňu
                $age = $today->diff($birthDate)->y;
            }

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
