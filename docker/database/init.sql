CREATE DATABASE IF NOT EXISTS `digin`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `digin`;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email_address` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `level` INT UNSIGNED NOT NULL DEFAULT 1,
  `xp` INT UNSIGNED NOT NULL DEFAULT 0,
  `spotify_token` TEXT NULL,

  PRIMARY KEY (`user_id`),
  UNIQUE (`email_address`)
);

CREATE TABLE IF NOT EXISTS `achievement` (
  `achievement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `achievement_name` VARCHAR(100) NOT NULL,

  PRIMARY KEY (`achievement_id`),
  UNIQUE (`achievement_name`)
);

CREATE TABLE IF NOT EXISTS `user_achievement` (
  `user_id` INT UNSIGNED NOT NULL,
  `achievement_id` INT UNSIGNED NOT NULL,

  PRIMARY KEY (`user_id`, `achievement_id`),
  FOREIGN KEY (`user_id`)
    REFERENCES `user` (`user_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (`achievement_id`)
    REFERENCES `achievement` (`achievement_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `recipe` (
  `recipe_id` INT UNSIGNED NOT NULL,
  `recipe_json` LONGTEXT NOT NULL,

  PRIMARY KEY (`recipe_id`)
);

CREATE TABLE IF NOT EXISTS `cached_search` (
  `search_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `search_parameter_string` LONGTEXT NOT NULL,
  `search_parameter_hash` CHAR(64)
    GENERATED ALWAYS AS (SHA2(`search_parameter_string`, 256)) STORED,

  PRIMARY KEY (`search_id`),
  UNIQUE (`search_parameter_hash`)
);

CREATE TABLE IF NOT EXISTS `cached_search_results` (
  `search_id` INT UNSIGNED NOT NULL,
  `recipe_id` INT UNSIGNED NOT NULL,

  PRIMARY KEY (`search_id`, `recipe_id`),
  FOREIGN KEY (`search_id`)
    REFERENCES `cached_search` (`search_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (`recipe_id`)
    REFERENCES `recipe` (`recipe_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `user_saved_recipe` (
  `user_id` INT UNSIGNED NOT NULL,
  `recipe_id` INT UNSIGNED NOT NULL,

  PRIMARY KEY (`user_id`, `recipe_id`),
  FOREIGN KEY (`user_id`)
    REFERENCES `user` (`user_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (`recipe_id`)
    REFERENCES `recipe` (`recipe_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `user_cooked_recipe` (
  `cooked_recipe_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `recipe_id` INT UNSIGNED NOT NULL,
  `cooked_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` TINYINT UNSIGNED NULL,
  `comment` TEXT NULL,

  PRIMARY KEY (`cooked_recipe_id`),
  FOREIGN KEY (`user_id`)
    REFERENCES `user` (`user_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (`recipe_id`)
    REFERENCES `recipe` (`recipe_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `spoonacular_api_key` (
  `api_key_value` VARCHAR(255) NOT NULL,

  PRIMARY KEY (`api_key_value`)
);

CREATE TABLE IF NOT EXISTS `ramsay_clip` (
  `clip_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path_to_audio` VARCHAR(512) NOT NULL,

  PRIMARY KEY (`clip_id`),
  UNIQUE (`path_to_audio`)
);
