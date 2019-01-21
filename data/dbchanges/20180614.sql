ALTER TABLE `job`
DROP FOREIGN KEY `fk_job_collection`;
ALTER TABLE `job`
DROP INDEX `fk_job_collection` ;

ALTER TABLE `job`
DROP COLUMN `collection`,
ADD COLUMN `set_uri` VARCHAR(255) NULL AFTER `user`;
