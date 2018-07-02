/* Replace editor with dashboard */
ALTER TABLE `user` MODIFY COLUMN `type` ENUM('editor','api','both') NOT NULL DEFAULT 'both';
UPDATE `user` SET `type` = 'editor' WHERE `type` = 'dashboard';
