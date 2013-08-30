CREATE TABLE IF NOT EXISTS `search_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `searchOptions` BLOB,
  `creatorUserId` INT,
  `tenant` CHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_search_profile_user`
    FOREIGN KEY (`creatorUserId`)
    REFERENCES `user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_search_profile_tenant`
    FOREIGN KEY (`tenant`)
    REFERENCES `tenant` (`code`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

ALTER TABLE `user` 
  ADD COLUMN `defaultSearchProfileId` INT,
  ADD CONSTRAINT `fk_user_search_profile`
    FOREIGN KEY (`defaultSearchProfileId`)
    REFERENCES `search_profiles` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;
