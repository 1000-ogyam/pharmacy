ALTER TABLE `users`
  ADD COLUMN `customer_id` BIGINT UNSIGNED NULL AFTER `licence_expires_at`,
  ADD COLUMN `supplier_id` BIGINT UNSIGNED NULL AFTER `customer_id`,
  ADD CONSTRAINT `fk_users_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;
