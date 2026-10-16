<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racers plugin - includes/racers.php
 *
*/

/*
 * Racer model: racers + categories loaded once (constructor), the category x
 * track join and the display-name wrapper. Schema: racers_sql.php.
 */

require_once(e_PLUGIN . 'racers/includes/racers_name.php');

class plugin_racers_racers  {

 

    static $categories = array();
    static $racers = array();
    static $pluginPrefs;

    function __construct()
    {
        $racers = e107::getDb()->retrieve("racer", "*", " ORDER BY racer_number ",  true, 'racer_number');
        $categories = e107::getDb()->retrieve("race_category", "*", " ORDER BY race_category_id ", true, 'race_category_id');
        self::$pluginPrefs = e107::pref('racers');
 
        self::$racers= $racers;
        self::$categories = $categories;
    }
 

    static function getRacersInfo($number = "")
    {

        if($number && isset(self::$racers[$number])) return self::$racers[$number];
        return self::$racers;
    }

    // Racers entered on a track (by racer_race_id, not gated by racer_active).
    // Used by racereg. $raceId is int-cast.
    public static function countOnTrack($raceId)
    {
        return (int) e107::getDb()->count('racer', '(*)', "racer_race_id = " . (int) $raceId);
    }

    static function getActiveRacers($parm = NULL)
    {
        $data = self::$racers;

        $active_racers = array_filter($data, function ($point)
        {
            return  $point['racer_active'] > 0;
        });

        return $active_racers;
    }  

    static function getSingleRacer($number = "")
    {
 
        if ($number && isset(self::$racers[$number])) return self::$racers[$number];
        return self::$racers;

   
    }

    static function getCategories($parm = NULL)
    {

        return self::$categories;
    }


    // Category x track join: one row per (track x category) pair carrying both the
    // track fields (race_name, race_sef, ...) and the category fields, so callers
    // can build start-list / finish URLs. Categories link to tracks through the CSV
    // race_category_race column (FIND_IN_SET). Order: race_id, race_category_sef DESC.
    public static function getCategoriesWithTracks()
    {
        $query = "SELECT * FROM  #race AS r,  #race_category AS rc
                WHERE FIND_IN_SET(r.race_id, rc.race_category_race) ORDER BY race_id, race_category_sef DESC ";

        $rows = e107::getDb()->retrieve($query, true);

        return is_array($rows) ? $rows : array();
    }


    /**
     * Racer display name. Delegates to racers_name::build() (includes/racers_name.php),
     * the single place the name is composed.
     *
     * @param array  $racer racer row
     * @param string $type  "team" includes the team suffix (default)
     * @return string HTML-safe display name
     */
    function getRacerName($racer = array(), $type = "team")
    {
        return racers_name::build((array) $racer, array('team' => ($type == 'team')));
    }

    function getSingleRacerTimeOnPoint($point, $number) {

        $prp = e107::getSingleton('plugin_race_points', e_PLUGIN . "racetrack/includes/points.php");
        $prr = e107::getSingleton('plugin_racers_racers', e_PLUGIN . "racers/includes/racers.php"); 
 
 
    }

}
