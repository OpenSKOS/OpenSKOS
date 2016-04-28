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

// inverses

function inverse_relations() {
    $retVal = ['http://menzo.org/xmlns#stronger' => 'http://menzo.org/xmlns#weaker', 'http://menzo.org/xmlns#weaker' => 'http://menzo.org/xmlns#stronger'];
    return $retVal;
}
