<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2015 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * racetrack plugin - includes/points.php
 *
*/

class plugin_race_points  {

 

    static $pointRaceTimes = array();
    static $pointInfo = array();


    function __construct()
    {
        $points = e107::getDb()->retrieve("race_point", "*", true, " ORDER BY race_point_order ", 'race_point_code');
        $pointsdata = e107::getDb()->retrieve("race_time" , "*", true, true);
   
        self::$pointRaceTimes = $pointsdata;
        self::$pointInfo = $points;
 
    }

 
    static function getPointsInfo($point = "")
    {
        if($point && isset(self::$pointInfo[$point])) return self::$pointInfo[$point];
        return self::$pointInfo;
    }

    /* All times recorded at one checkpoint. */
    static function getSinglePointData($point = "")
    {

        $data = self::$pointRaceTimes;
        $result = array_filter($data, function ($item) use ($point)
        {
            return isset($item['race_time_point']) && $item['race_time_point'] === $point;
        });
        return $result;
    }

    public function getSingleStartData($point = "start")
    {
        $data = self::$pointRaceTimes;
        return $data;
        $result = array_filter($data, function ($item) use ($point)
        {
            return isset($item['race_time_point']) && $item['race_time_point'] === $point;
        });
 
        return $result;
    }
 
}
