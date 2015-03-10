REPLACE INTO `namespace`
    VALUES ('openskos', 'http://openskos.org/xmlns/openskos.xsd');

ALTER TABLE `tenant`
    ADD COLUMN `enableStatusesSystem` BOOLEAN;