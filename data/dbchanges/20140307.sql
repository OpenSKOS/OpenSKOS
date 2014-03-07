ALTER TABLE `user`   
  DROP FOREIGN KEY `fk_user_search_profile`,
  CHANGE COLUMN `defaultSearchProfileId` `defaultSearchProfileIds` VARCHAR(255);