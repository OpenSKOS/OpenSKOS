ALTER TABLE `user`
MODIFY COLUMN uri varchar(255) AFTER id,
ADD CONSTRAINT unique_uri UNIQUE(uri);

UPDATE `user`
SET uri = NULL;