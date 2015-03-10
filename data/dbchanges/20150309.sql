REPLACE INTO `namespace`
    VALUES ('openskos', 'http://openskos.org/xmlns/openskos.xsd');

ALTER TABLE `tenant`
    ADD COLUMN `enableStatusesSystem` BOOLEAN;

INSERT INTO collection_has_namespace
    SELECT DISTINCT id, 'openskos'
    FROM collection;