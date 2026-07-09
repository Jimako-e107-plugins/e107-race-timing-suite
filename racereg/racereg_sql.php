# racereg plugin - schema (issue #22).
#
# e107 Lite reads this <plugin>_sql.php during install/uninstall and prefixes
# each table name with the configured site table prefix automatically. Mirrors
# the reference plugins (race_sql.php / timetracker_sql.php): plain CREATE TABLE,
# KEY/UNIQUE index declarations, ENGINE=InnoDB. utf8mb4 so international names /
# addresses (PII) store the full Unicode range.
#
# Relationship: racereg_payment.registration_id -> racereg_registration.
# registration_id is 1:N. No engine-level FOREIGN KEY (none of the reference
# Lite plugins declare one); the relationship is kept logical with an index on
# racereg_payment.registration_id.
#
# Dates are Unix timestamps in INT(10) columns (never SQL DATE). Money is
# DECIMAL(10,2). variable_symbol and postal_code are strings (numeric strings),
# never INT.
#
# pay_token (issue #40) is an unguessable 128-bit random hex string (NOT derived
# from registration_id), UNIQUE and indexed, used to reach the public pay page.
# It is DEFAULT NULL (not '') on purpose: MySQL allows multiple NULLs in a UNIQUE
# index, so legacy rows can sit token-less until backfilled lazily without
# colliding. New rows always get a token on create.

CREATE TABLE `racereg_registration` (
  `registration_id` INT NOT NULL AUTO_INCREMENT,
  `track_id` INT NOT NULL DEFAULT 0,
  `first_name` VARCHAR(100) NOT NULL DEFAULT '',
  `last_name` VARCHAR(100) NOT NULL DEFAULT '',
  `birth_date` INT(10) NOT NULL DEFAULT 0,
  `street` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) DEFAULT NULL,
  `club` VARCHAR(255) DEFAULT NULL,
  `category_id` INT NOT NULL DEFAULT 0,
  `nationality` VARCHAR(10) NOT NULL DEFAULT '',
  `local` TINYINT(1) NOT NULL DEFAULT 0,
  `registration_date` INT(10) NOT NULL DEFAULT 0,
  `start_list_at` INT(10) DEFAULT NULL,
  `variable_symbol` VARCHAR(10) NOT NULL DEFAULT '',
  `amount_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `approval_status` TINYINT NOT NULL DEFAULT 0,
  `pay_token` VARCHAR(32) DEFAULT NULL,
  `private_note` TEXT,
  `deleted_at` INT(10) DEFAULT NULL,
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `variable_symbol` (`variable_symbol`),
  UNIQUE KEY `pay_token` (`pay_token`),
  KEY `track_id` (`track_id`),
  KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `racereg_payment` (
  `payment_id` INT NOT NULL AUTO_INCREMENT,
  `registration_id` INT NOT NULL DEFAULT 0,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` TINYINT NOT NULL DEFAULT 0,
  `paid_at` INT(10) DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  KEY `registration_id` (`registration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
