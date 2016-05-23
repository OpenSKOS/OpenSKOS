<?php

define('COMMON_APPLICATION_INI', '/app/application/configs/application.ini');

// roles //
define('ROOT', 'root');
define('ADMINISRATOR', 'administrator');
define('EDITOR', 'editor');
define('USER', 'user');
define('GUEST', 'guest');

//
define('MAXIMAL_ROWS', 5000);

define('CHECK_MYSQL', false);

// inverses

function inverse_relations() {
    $retVal = ['http://menzo.org/xmlns#faster' => 'http://menzo.org/xmlns#slower', 'http://menzo.org/xmlns#slower' => 'http://menzo.org/xmlns#faster'];
    return $retVal;
}

define('UNKNOWN', "Unknown");