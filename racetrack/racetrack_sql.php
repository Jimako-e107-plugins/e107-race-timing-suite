CREATE TABLE `race` (
`race_id` int NOT NULL AUTO_INCREMENT,
`race_code` varchar(100) NOT NULL,
`race_sef` varchar(100) NOT NULL,
`race_name` varchar(255) NOT NULL,
`race_capacity` int(10) NOT NULL DEFAULT 0,
`race_unlimited_capacity` int(10) NOT NULL DEFAULT 0,
`race_requires_approval` int(10) NOT NULL DEFAULT 0,
`race_registration_closed` int(10) NOT NULL DEFAULT 0,
PRIMARY KEY (`race_id`)
) ENGINE=InnoDB;


CREATE TABLE `race_price` (
`race_price_id` int NOT NULL AUTO_INCREMENT,
`race_price_race` int NOT NULL DEFAULT 0,
`race_price_value` decimal(10,2) NOT NULL DEFAULT 0.00,
`race_price_from` int(10) NOT NULL DEFAULT 0,
PRIMARY KEY (`race_price_id`),
KEY `race_price_race` (`race_price_race`)
) ENGINE=InnoDB;


CREATE TABLE `race_point` (
`race_point_id` int NOT NULL AUTO_INCREMENT,
`race_point_race` varchar(10) NOT NULL,
`race_point_code` varchar(10) NOT NULL,
`race_point_dbfield` varchar(10) NOT NULL,
`race_point_name` varchar(255) NOT NULL,
`race_point_password` varchar(255) NOT NULL,
`race_point_order` int NOT NULL,
PRIMARY KEY (`race_point_id`) USING BTREE,
KEY `race_point_order` (`race_point_order`)
) ENGINE=InnoDB;


CREATE TABLE `race_archive` (
`race_archive_id` int NOT NULL AUTO_INCREMENT,
`race_archive_race` varchar(100) NOT NULL,
`race_archive_sef` varchar(100) NOT NULL,
`race_archive_name` varchar(255) NOT NULL,
`race_archive_desc` TEXT NOT NULL,
`race_archive_data` LONGTEXT NOT NULL,
`race_archive_html` LONGTEXT NOT NULL,
`race_archive_created` INT(10) NOT NULL,
`race_archive_updated` INT(10) NOT NULL,
PRIMARY KEY (`race_archive_id`)
) ENGINE=InnoDB;
