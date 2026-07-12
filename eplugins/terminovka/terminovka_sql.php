CREATE TABLE `terminovka_track` (
`terminovka_track_id` int(10) NOT NULL AUTO_INCREMENT,
`race_id` int(10) NOT NULL,
`ext_id` int(10) NOT NULL DEFAULT 0,
PRIMARY KEY (`terminovka_track_id`),
UNIQUE KEY `race_id` (`race_id`)
) ENGINE=InnoDB;


CREATE TABLE `race_result` (
`race_result_id` int(10) NOT NULL AUTO_INCREMENT,
`race_result_number` VARCHAR(11) NOT NULL,
`race_result_time` varchar(255) NOT NULL,
`race_result_sent` INT(10) NOT NULL,
`race_result_log` LONGTEXT NOT NULL,
`race_result_created` INT(10) NOT NULL,
`race_result_updated` INT(10) NOT NULL,
`race_result_timesent` INT(10) NOT NULL,
PRIMARY KEY (`race_result_id`),
UNIQUE `racer` (`race_result_number`)
) ENGINE=InnoDB;
