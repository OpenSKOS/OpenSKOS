ALTER TABLE `user` ADD COLUMN role varchar(25) NOT NULL DEFAULT "guest";
ALTER TABLE `user` ADD COLUMN `searchOptions` VARCHAR(2000) AFTER `role`;
