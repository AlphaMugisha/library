-- Migration 001: Add Academic System Integration Fields
-- This migration adds fields necessary for integrating with the main academic system
-- Run this script to update the database schema for SSO integration

-- Add academic integration fields to users table
ALTER TABLE `users` 
ADD COLUMN `academic_id` VARCHAR(50) UNIQUE AFTER `id`,
ADD COLUMN `sync_status` ENUM('synced', 'pending', 'error', 'manual') DEFAULT 'pending' AFTER `two_factor_expires`,
ADD COLUMN `last_sync` TIMESTAMP NULL DEFAULT NULL AFTER `sync_status`,
ADD COLUMN `academic_role` VARCHAR(50) NULL AFTER `last_sync`,
ADD COLUMN `sync_error_message` TEXT NULL AFTER `academic_role`,
ADD COLUMN `academic_department` VARCHAR(100) NULL AFTER `sync_error_message`,
ADD COLUMN `academic_level` VARCHAR(50) NULL AFTER `academic_department`;

-- Add indexes for better performance
CREATE INDEX `idx_academic_id` ON `users` (`academic_id`);
CREATE INDEX `idx_sync_status` ON `users` (`sync_status`);
CREATE INDEX `idx_last_sync` ON `users` (`last_sync`);

-- Create integration logs table for tracking sync operations
CREATE TABLE `integration_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('sync', 'create', 'update', 'login', 'error') NOT NULL,
  `academic_id` varchar(50) DEFAULT NULL,
  `status` enum('success', 'error', 'pending') NOT NULL,
  `message` text DEFAULT NULL,
  `data_sent` json DEFAULT NULL,
  `response_received` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_academic_id` (`academic_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_integration_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create academic_sessions table for SSO token management
CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `academic_token` varchar(500) NOT NULL,
  `token_expires` datetime NOT NULL,
  `session_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_used` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_academic_token` (`academic_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_token_expires` (`token_expires`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_academic_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing users to have manual sync status (for existing accounts)
UPDATE `users` SET `sync_status` = 'manual' WHERE `academic_id` IS NULL;

-- Add comments for documentation
ALTER TABLE `users` COMMENT = 'User accounts with academic system integration support';
ALTER TABLE `integration_logs` COMMENT = 'Logs for academic system integration operations';
ALTER TABLE `academic_sessions` COMMENT = 'SSO session tokens from academic system';
