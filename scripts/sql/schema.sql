CREATE DATABASE IF NOT EXISTS gostay;
USE gostay;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `role` ENUM('client', 'manager', 'admin') NOT NULL DEFAULT 'client',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reset_code` VARCHAR(255) NULL,
  `reset_expires_at` DATETIME NULL,
  PRIMARY KEY (`id`)
);

-- ----------------------------
-- Table structure for cities
-- ----------------------------
DROP TABLE IF EXISTS `cities`;
CREATE TABLE `cities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `image_url` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)
);

-- ----------------------------
-- Table structure for rooms
-- ----------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `city_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `address` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `capacity` INT(11) NOT NULL,
  `bedrooms` INT(11) NOT NULL,
  `bathrooms` INT(11) NOT NULL,
  `check_in_time` TIME,
  `check_out_time` TIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE
);

-- ----------------------------
-- Table structure for messages
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` INT(11) NOT NULL,
  `receiver_id` INT(11) NOT NULL,
  `message_body` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ----------------------------
-- Table structure for reservations
-- ----------------------------
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `room_id` INT(11) NOT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);

-- ----------------------------
-- Table structure for reviews
-- ----------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `room_id` INT(11) NOT NULL,
  `rating` TINYINT(4) NOT NULL,
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);

-- ----------------------------
-- Table structure for room_photos
-- ----------------------------
DROP TABLE IF EXISTS `room_photos`;
CREATE TABLE `room_photos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_id` INT(11) NOT NULL,
  `photo_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);

-- ----------------------------
-- Table structure for facilities
-- ----------------------------
DROP TABLE IF EXISTS `facilities`;
CREATE TABLE `facilities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `room_id` INT(11) NOT NULL,
  `has_wifi` TINYINT(1) DEFAULT 0,
  `has_workspace` TINYINT(1) DEFAULT 0,
  `has_ac` TINYINT(1) DEFAULT 0,
  `has_heating` TINYINT(1) DEFAULT 0,
  `has_parking` TINYINT(1) DEFAULT 0,
  `has_self_checkin` TINYINT(1) DEFAULT 0,
  `has_elevator` TINYINT(1) DEFAULT 0,
  `has_kitchen` TINYINT(1) DEFAULT 0,
  `has_fridge` TINYINT(1) DEFAULT 0,
  `has_microwave` TINYINT(1) DEFAULT 0,
  `has_cooking_basics` TINYINT(1) DEFAULT 0,
  `has_dishes` TINYINT(1) DEFAULT 0,
  `has_stove` TINYINT(1) DEFAULT 0,
  `has_coffee_maker` TINYINT(1) DEFAULT 0,
  `has_washing_machine` TINYINT(1) DEFAULT 0,
  `has_dryer` TINYINT(1) DEFAULT 0,
  `has_iron` TINYINT(1) DEFAULT 0,
  `has_hairdryer` TINYINT(1) DEFAULT 0,
  `has_hot_water` TINYINT(1) DEFAULT 0,
  `has_essentials` TINYINT(1) DEFAULT 0,
  `has_tv` TINYINT(1) DEFAULT 0,
  `has_balcony` TINYINT(1) DEFAULT 0,
  `has_pool` TINYINT(1) DEFAULT 0,
  `has_jacuzzi` TINYINT(1) DEFAULT 0,
  `has_smoke_alarm` TINYINT(1) DEFAULT 0,
  `has_first_aid` TINYINT(1) DEFAULT 0,
  `is_pet_friendly` TINYINT(1) DEFAULT 0,
  `is_smoking_allowed` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;