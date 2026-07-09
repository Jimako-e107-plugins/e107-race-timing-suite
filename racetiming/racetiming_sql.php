CREATE TABLE IF NOT EXISTS `race_time` (
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
