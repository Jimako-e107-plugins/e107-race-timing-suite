 CREATE TABLE `race_tracking` (
 `id` int NOT NULL AUTO_INCREMENT,
 `TagID` varchar(255) NOT NULL,
 `Meno` varchar(255) DEFAULT NULL,
 `Start` varchar(255) DEFAULT NULL,
 `Ciel` varchar(255) DEFAULT NULL,
 `Proces` varchar(10) DEFAULT NULL,
 PRIMARY KEY (`id`)
 ) ENGINE=InnoDB;
