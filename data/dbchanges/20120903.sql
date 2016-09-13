/* Adds OpenID identity to map an openskosk user */
ALTER TABLE `user` ADD COLUMN `openIdIdentity` VARCHAR(255) NULL;
ALTER TABLE `user` ADD UNIQUE KEY `unique_openIdIdentity` (`openIdIdentity`);

/* Allows creating of users without password. If the user uses OpenID the password is not needed */
ALTER TABLE `user` MODIFY COLUMN `password` CHAR(32) DEFAULT NULL;