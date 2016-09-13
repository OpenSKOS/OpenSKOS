/* Fix columns types for serialized data */
ALTER TABLE `user` MODIFY COLUMN `searchOptions` BLOB;
ALTER TABLE `user` MODIFY COLUMN `conceptsSelection` BLOB;
