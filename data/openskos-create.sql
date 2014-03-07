SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `openskos` DEFAULT CHARACTER SET utf8 ;
USE `openskos` ;

-- -----------------------------------------------------
-- Table `openskos`.`tenant`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`tenant` (
  `code` CHAR(10) NOT NULL ,
  `name` VARCHAR(150) NULL DEFAULT NULL ,
  `organisationUnit` VARCHAR(100) NULL DEFAULT NULL ,
  `website` VARCHAR(100) NULL DEFAULT NULL ,
  `email` VARCHAR(100) NULL DEFAULT NULL ,
  `streetAddress` VARCHAR(255) NULL DEFAULT NULL ,
  `locality` VARCHAR(150) NULL DEFAULT NULL ,
  `postalCode` VARCHAR(50) NULL DEFAULT NULL ,
  `countryName` VARCHAR(100) NULL DEFAULT NULL ,
  `disableSearchInOtherTenants` BOOLEAN,
  PRIMARY KEY (`code`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `openskos`.`collection`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`collection` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `code` CHAR(10) NULL DEFAULT NULL ,
  `tenant` CHAR(10) NOT NULL ,
  `dc_title` VARCHAR(150) NOT NULL ,
  `dc_description` TEXT NULL DEFAULT NULL ,
  `website` VARCHAR(100) NULL DEFAULT NULL ,
  `license_name` VARCHAR(150) NULL DEFAULT NULL ,
  `license_url` VARCHAR(255) NULL DEFAULT NULL ,
  `OAI_baseURL` TEXT NULL DEFAULT NULL ,
  `allow_oai` ENUM('Y','N') NOT NULL DEFAULT 'Y' ,
  `conceptsBaseUrl` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unique_collection` (`code` ASC, `tenant` ASC) ,
  INDEX `fk_collection_tenant` (`tenant` ASC) ,
  INDEX `ix_allow_oai` (`allow_oai` ASC) ,
  CONSTRAINT `fk_collection_tenant`
    FOREIGN KEY (`tenant` )
    REFERENCES `openskos`.`tenant` (`code` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `openskos`.`namespace`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`namespace` (
  `prefix` VARCHAR(25) NOT NULL COMMENT '			' ,
  `uri` VARCHAR(150) NULL DEFAULT NULL ,
  PRIMARY KEY (`prefix`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `openskos`.`collection_has_namespace`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`collection_has_namespace` (
  `collection` INT(11) NOT NULL ,
  `namespace` VARCHAR(25) NOT NULL ,
  PRIMARY KEY (`collection`, `namespace`) ,
  INDEX `fk_collection_has_namespace_namespace1` (`namespace` ASC) ,
  CONSTRAINT `fk_collection_has_namespace_namespace1`
    FOREIGN KEY (`namespace` )
    REFERENCES `openskos`.`namespace` (`prefix` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_collection_has_namespace_collection1`
    FOREIGN KEY (`collection` )
    REFERENCES `openskos`.`collection` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `openskos`.`user`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`user` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `email` VARCHAR(100) NOT NULL ,
  `name` VARCHAR(150) NOT NULL ,
  `password` CHAR(32) NOT NULL ,
  `tenant` CHAR(10) NOT NULL ,
  `apikey` VARCHAR(100) NULL DEFAULT NULL ,
  `active` CHAR(1) NOT NULL DEFAULT 'Y' ,
  `type` ENUM('editor','api','both') NOT NULL DEFAULT 'both' ,
  `eppn` VARCHAR(100) NOT NULL ,
  `role` varchar(25) NOT NULL DEFAULT "guest",
  `searchOptions` BLOB,
  `conceptsSelection` BLOB,
  `defaultSearchProfileIds` INT,
  `disableSearchProfileChanging` BOOLEAN,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unique_user` (`email` ASC, `tenant` ASC) ,
  INDEX `fk_user_tenant` (`tenant` ASC) ,
  UNIQUE INDEX `eduPersonPrincipalName` (`eppn` ASC, `tenant` ASC) ,
  CONSTRAINT `fk_user_tenant`
    FOREIGN KEY (`tenant` )
    REFERENCES `openskos`.`tenant` (`code` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `openskos`.`job`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `openskos`.`job` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `collection` INT(11) NOT NULL ,
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
  INDEX `fk_job_collection` (`collection` ASC) ,
  CONSTRAINT `fk_job_collection`
    FOREIGN KEY (`collection` )
    REFERENCES `openskos`.`collection` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_job_user`
    FOREIGN KEY (`user` )
    REFERENCES `openskos`.`user` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `openskos`.`notations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `openskos`.`notations` (
  `notation` INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`notation`) 
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `openskos`.`search_profiles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `openskos`.`search_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `searchOptions` BLOB,
  `creatorUserId` INT,
  `tenant` CHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_search_profile_user`
    FOREIGN KEY (`creatorUserId`)
    REFERENCES `openskos`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_search_profile_tenant`
    FOREIGN KEY (`tenant`)
    REFERENCES `openskos`.`tenant` (`code`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


INSERT INTO `namespace` (`prefix`, `uri`) VALUES
('cc', 'http://creativecommons.org/ns#'),
('dc', 'http://purl.org/dc/elements/1.1/'),
('dcr', 'http://www.isocat.org/ns/dcr.rdf#'),
('dcterms', 'http://purl.org/dc/terms/'),
('fb', 'http://rdf.freebase.com/ns/'),
('foaf', 'http://xmlns.com/foaf/0.1/'),
('geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#'),
('geonames', 'http://www.geonames.org/ontology#'),
('nyt', 'http://data.nytimes.com/elements/'),
('owl', 'http://www.w3.org/2002/07/owl#'),
('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'),
('rdfs', 'http://www.w3.org/2000/01/rdf-schema#'),
('skos', 'http://www.w3.org/2004/02/skos/core#'),
('time', 'http://www.w3.org/2006/time#');
