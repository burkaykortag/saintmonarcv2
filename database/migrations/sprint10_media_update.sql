SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add fields to media_library table if they do not exist
ALTER TABLE `media_library` 
ADD COLUMN `caption` VARCHAR(255) NULL AFTER `title`,
ADD COLUMN `duration` INT UNSIGNED NULL AFTER `height`;

-- 2. Add promo_video_id to products table
ALTER TABLE `products`
ADD COLUMN `promo_video_id` BIGINT UNSIGNED NULL AFTER `cover_image_id`;

-- 3. Add image_id to product_images table
ALTER TABLE `product_images`
ADD COLUMN `image_id` BIGINT UNSIGNED NULL AFTER `product_id`;

SET FOREIGN_KEY_CHECKS = 1;
