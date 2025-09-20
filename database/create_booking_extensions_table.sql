-- SQL script to create booking_extensions table for extended time functionality
-- This table tracks all time extensions made to bookings

-- Create booking_extensions table
CREATE TABLE `booking_extensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `extended_by` int(11) DEFAULT NULL COMMENT 'Admin ID who extended the booking',
  `additional_hours` int(11) NOT NULL,
  `additional_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 200.00 COMMENT 'Rate per hour at time of extension',
  `previous_checkout` datetime NOT NULL COMMENT 'Original checkout time before extension',
  `new_checkout` datetime NOT NULL COMMENT 'New checkout time after extension',
  `extension_reason` text DEFAULT NULL COMMENT 'Reason for extension (optional)',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_extended_by` (`extended_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_booking_extensions_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_extensions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_booking_extensions_admin` FOREIGN KEY (`extended_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create booking_logs table for audit trail (if it doesn't exist)
CREATE TABLE IF NOT EXISTS `booking_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Action performed (e.g., time_extended, status_changed)',
  `details` text DEFAULT NULL COMMENT 'Additional details about the action',
  `performed_by` int(11) DEFAULT NULL COMMENT 'User or admin ID who performed the action',
  `performed_by_type` enum('user','admin','system') DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_booking_logs_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add hourly_rate column to room_types table if it doesn't exist
-- This is referenced in the ExtendTimeController but might be missing
ALTER TABLE `room_types` 
ADD COLUMN IF NOT EXISTS `hourly_rate` decimal(10,2) NOT NULL DEFAULT 200.00 COMMENT 'Hourly rate for time extensions' 
AFTER `description`;

-- Update existing room types with hourly rates
UPDATE `room_types` SET `hourly_rate` = 200.00 WHERE `id` = 1; -- Normal Room
UPDATE `room_types` SET `hourly_rate` = 250.00 WHERE `id` = 2; -- Family Room

-- Insert sample data for testing (optional)
-- INSERT INTO `booking_extensions` (`booking_id`, `user_id`, `additional_hours`, `additional_cost`, `previous_checkout`, `new_checkout`, `extension_reason`) 
-- VALUES (1, 1, 2, 400.00, '2025-09-15 15:00:00', '2025-09-15 17:00:00', 'Customer requested additional time');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_booking_extensions_dates` ON `booking_extensions` (`previous_checkout`, `new_checkout`);
CREATE INDEX IF NOT EXISTS `idx_booking_logs_action_date` ON `booking_logs` (`action`, `created_at`);

-- Add comments to existing tables for better documentation
ALTER TABLE `bookings` COMMENT = 'Main bookings table storing all hotel reservations';
ALTER TABLE `booking_rates` COMMENT = 'Predefined rates for different room types and durations';
ALTER TABLE `rooms` COMMENT = 'Hotel rooms inventory and status';
ALTER TABLE `room_types` COMMENT = 'Room type definitions with rates and descriptions';

COMMIT;