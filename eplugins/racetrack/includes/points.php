<?php
 
/**
 * Class trumbowyg_admin_config.
 */

 /*
 CREATE TABLE `race_point` (
`race_point_id` int NOT NULL AUTO_INCREMENT,
`race_point_race` varchar(10) NOT NULL,
`race_point_code` varchar(10) NOT NULL,
`race_point_name` varchar(255) NOT NULL,
`race_point_password` varchar(255) NOT NULL,
`race_point_order` int NOT NULL,
PRIMARY KEY (`race_point_id`) USING BTREE,
KEY `race_point_order` (`race_point_order`)
) ENGINE=InnoDB;
*/

/*
CREATE TABLE `race_time` (
`race_time_id` int NOT NULL AUTO_INCREMENT,
`race_time_racer_number` VARCHAR(11) NOT NULL,
`race_time_point` VARCHAR(11) NOT NULL ,
`race_time_time` VARCHAR(100) NOT NULL ,
`race_time_ended` VARCHAR(3) NOT NULL DEFAULT '',
`race_time_created` INT(10) NOT NULL,
`race_time_updated` INT(10) NOT NULL,
PRIMARY KEY (`race_time_id`),
UNIQUE `racer` (`race_time_racer_number`, `race_time_point`, `race_time_ended`)
) ENGINE=InnoDB;
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

    /* všetky časy pre konkretny point */
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
