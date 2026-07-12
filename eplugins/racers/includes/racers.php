<?php

 /*
  CREATE TABLE `racer` (
 `racer_id` int NOT NULL AUTO_INCREMENT,
 `racer_race_id` int NOT NULL,
 `race_category_id` int NOT NULL,
 `racer_number` varchar(11) NOT NULL,
 `racer_surname` varchar(20) NOT NULL,
 `racer_firstname` varchar(20) NOT NULL,
 `racer_gender` varchar(1) NOT NULL DEFAULT '',
 `racer_nacionality` varchar(10) NOT NULL DEFAULT '',
 `racer_birthday` varchar(12) NOT NULL DEFAULT '',
 `racer_active` int(1) NOT NULL,
 `racer_local` int(1) NOT NULL,
 `racer_tags` varchar(100) NOT NULL,
 `racer_team` varchar(100) NOT NULL,
 `racer_extid` INT(10) NOT NULL ,
 PRIMARY KEY (`racer_id`)
 ) ENGINE=InnoDB;

 CREATE TABLE `race_category` (
 `race_category_id` int NOT NULL AUTO_INCREMENT,
 `race_category_gender` varchar(1) NOT NULL DEFAULT '',
 `race_category_age_from` int(10) NOT NULL,
 `race_category_age_to` int(10) NOT NULL,
 `race_category_sef` varchar(100) NOT NULL,
 `race_category_name` varchar(50) NOT NULL,
 `race_category_color` varchar(10) NOT NULL,
 PRIMARY KEY (`race_category_id`)
 ) ENGINE=InnoDB;
  */

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

    // Count of racers entered on a track (all racers on the track, by
    // racer_race_id - not gated by racer_active). $raceId is int-cast; the
    // count goes through the db class, never raw string interpolation.
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


    // Category x track join, reproducing what timetracker_class::$racecats used to
    // provide (B3 de-coupling). One row per (track x category) pair: each row carries
    // the track fields (race_name, race_sef, ...) AND the category fields
    // (race_category_name, race_category_sef, race_category_color, ...) so the
    // race_categories menu can build START LIST + FINISH urls without timetracker.
    //
    // Categories link to tracks through the CSV race_category_race column, joined via
    // FIND_IN_SET(race_id, race_category_race) - the same join timetracker used. Order
    // matches legacy (race_id, race_category_sef DESC) so the tile order is unchanged.
    public static function getCategoriesWithTracks()
    {
        $query = "SELECT * FROM  #race AS r,  #race_category AS rc
                WHERE FIND_IN_SET(r.race_id, rc.race_category_race) ORDER BY race_id, race_category_sef DESC ";

        $rows = e107::getDb()->retrieve($query, true);

        return is_array($rows) ? $rows : array();
    }


    function getRacerName($racer = array(), $type = "team")
    {

        $racer_name =  $racer['racer_surname'] . ' '  .    $racer['racer_firstname'];
 
        if (self::$pluginPrefs['name_hash'])
        {
            $racer_name =  md5($racer_name);
        }

        if (self::$pluginPrefs['display_local'] && self::$pluginPrefs['text_local'] && $racer['racer_local'])
        {
            $racer_name .= e107::getParser()->toHTML(self::$pluginPrefs['text_local'], "TITLE", true);
        }

        if ($type == 'team')
        {
            if (self::$pluginPrefs['display_team'] && $racer['racer_team'])
            {

                $team =  e107::getParser()->toHTML($racer['racer_team'], "TITLE", true);
                $racer_name .= "&nbsp;<small><i>(" . $team . ")</i></small>";
            }
        }

        return $racer_name;
    }

    function getSingleRacerTimeOnPoint($point, $number) {

        $prp = e107::getSingleton('plugin_race_points', e_PLUGIN . "racetrack/includes/points.php");
        $prr = e107::getSingleton('plugin_racers_racers', e_PLUGIN . "racers/includes/racers.php"); 
 
 
    }

}
