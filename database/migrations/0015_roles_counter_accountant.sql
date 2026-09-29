INSERT INTO `roles` (`name`, `slug`, `description`, `created_at`, `updated_at`)
SELECT 'Medicine Counter Assistant', 'counter', 'Read-only access to branch data', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `roles` WHERE `slug` = 'counter' AND `deleted_at` IS NULL
);

UPDATE `roles`
SET `name` = 'Accountant', `description` = 'Accounting and credit', `updated_at` = NOW()
WHERE `slug` = 'finance' AND `deleted_at` IS NULL;
