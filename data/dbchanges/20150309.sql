ALTER TABLE `collection`
    ADD COLUMN `enableStatusesSystem` BOOLEAN;

REPLACE INTO `namespace`
    VALUES ('openskos', 'http://openskos.org/xmlns/openskos.xsd');