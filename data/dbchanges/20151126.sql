DROP TABLE collection_has_namespace;
DROP TABLE namespace;
DROP TABLE notations;

ALTER TABLE collection
MODIFY COLUMN uri varchar(255) AFTER id,
ADD CONSTRAINT unique_uri UNIQUE(uri);

UPDATE collection
SET uri = NULL;