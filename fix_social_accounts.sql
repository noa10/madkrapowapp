-- Drop the existing foreign key constraint
ALTER TABLE `social_accounts` DROP FOREIGN KEY `social_accounts_user_id_foreign`;

-- Add the new foreign key constraint that points to the correct table and column
ALTER TABLE `social_accounts` 
ADD CONSTRAINT `social_accounts_user_id_foreign` 
FOREIGN KEY (`user_id`) REFERENCES `madkrapow_users` (`user_id`) 
ON DELETE CASCADE; 