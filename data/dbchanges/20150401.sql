INSERT INTO `namespace`
    VALUES ('openskos', 'http://openskos.org/xmlns#')
    ON DUPLICATE KEY UPDATE uri = 'http://openskos.org/xmlns#';