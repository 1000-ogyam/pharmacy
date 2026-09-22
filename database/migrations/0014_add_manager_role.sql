INSERT INTO `roles` (`name`, `slug`, `description`, `created_at`, `updated_at`)
SELECT 'Manager', 'manager', 'Branch manager', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `roles` WHERE `slug` = 'manager' AND `deleted_at` IS NULL
);
