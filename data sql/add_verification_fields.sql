-- Add verification fields to suppliers table
-- Run this script to add the missing verification fields

ALTER TABLE `suppliers` 
ADD COLUMN `verification_code` VARCHAR(10) DEFAULT NULL,
ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0;

-- Add index for better performance on verification_code lookups
CREATE INDEX `idx_verification_code` ON `suppliers` (`verification_code`);
