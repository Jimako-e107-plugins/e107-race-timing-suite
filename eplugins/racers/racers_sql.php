 CREATE TABLE `racer` (
 `racer_id` int NOT NULL AUTO_INCREMENT,
 `racer_race_id` int NOT NULL,
 `racer_category_id` int NOT NULL,
 `racer_number` varchar(11) NOT NULL,
 `racer_surname` varchar(100) NOT NULL,
 `racer_firstname` varchar(20) NOT NULL,
 `racer_gender` varchar(1) NOT NULL DEFAULT '',
 `racer_nacionality` varchar(10) NOT NULL DEFAULT '',
 `racer_birthday` varchar(12) NOT NULL DEFAULT '',
 `racer_active` int(1) NOT NULL,
 `racer_local` int(1) NOT NULL,
 `racer_tags` varchar(100) NOT NULL,
 `racer_team` varchar(100) NOT NULL,
 `racer_extid` INT(10) NOT NULL ,
 `racer_city` varchar(30) NOT NULL,
 PRIMARY KEY (`racer_id`)
 ) ENGINE=InnoDB;


 CREATE TABLE `race_category` (
 `race_category_id` int NOT NULL AUTO_INCREMENT,
 `race_category_race` varchar(10) NOT NULL,
 `race_category_sef` varchar(100) NOT NULL,
 `race_category_name` varchar(50) NOT NULL,
 `race_category_color` varchar(10) NOT NULL,
 `race_category_gender` varchar(1) NOT NULL DEFAULT '',
 `race_category_age_from` int(10) NOT NULL,
 `race_category_age_to` int(10) NOT NULL,
 PRIMARY KEY (`race_category_id`)
 ) ENGINE=InnoDB;
