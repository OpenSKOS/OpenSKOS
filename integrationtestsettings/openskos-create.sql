SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `openskos` DEFAULT CHARACTER SET utf8 ;
USE `openskos` ;


-- -----------------------------------------------------
-- Table `openskos`.`user`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uri` varchar(256),
  `email` VARCHAR(100) NOT NULL ,
  `name` VARCHAR(150) NOT NULL ,
  `password` CHAR(32) NOT NULL ,
  `tenant` CHAR(10) NOT NULL ,
  `apikey` VARCHAR(100) NULL DEFAULT NULL ,
  `active` CHAR(1) NOT NULL DEFAULT 'Y' ,
  `type` ENUM('editor','api','both') NOT NULL DEFAULT 'both' ,
  `role` varchar(25) NOT NULL DEFAULT "guest",
  `searchOptions` BLOB,
  `conceptsSelection` BLOB,
  `defaultSearchProfileIds` VARCHAR(255),
  `disableSearchProfileChanging` BOOLEAN,  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unique_user` (`email` ASC))
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `openskos`.`job`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`job` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `set` INT(11) NOT NULL ,
  `user` INT(11) NOT NULL ,
  `task` VARCHAR(100) NULL DEFAULT NULL ,
  `parameters` TEXT NULL DEFAULT NULL ,
  `created` DATETIME NULL DEFAULT NULL ,
  `started` DATETIME NULL DEFAULT NULL ,
  `finished` DATETIME NULL DEFAULT NULL ,
  `status` ENUM('SUCCESS', 'ERROR') NULL DEFAULT NULL ,
  `info` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `task` (`task` ASC) ,
  INDEX `finished` (`finished` ASC) ,
  INDEX `fk_job_user` (`user` ASC) ,
  CONSTRAINT `fk_job_user`
    FOREIGN KEY (`user` )
    REFERENCES `openskos`.`user` (`id` )
    ON DELETE CASCADE
ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = utf8;





