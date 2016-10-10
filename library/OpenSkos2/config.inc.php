<?php

define('APPLICATION_BASE_PATH', '/app/backendname');
define('COMMON_APPLICATION_INI', APPLICATION_BASE_PATH. '/application/configs/application.ini');
define('ERROR_LOG', APPLICATION_BASE_PATH. '/data/ValidationErrors.txt');

// roles //
define('ROOT', 'root');
define('ADMINISTRATOR', 'administrator');
define('EDITOR', 'editor');
define('USER', 'user');
define('GUEST', 'guest');

//
define('MAXIMAL_ROWS', 5000);
define('MAXIMAL_TIME_LIMIT', 240); //sec
define('NORMAL_TIME_LIMIT', 30); // sec

define('ENABLE_STATUSSES_SYSTEM', true);
define('OMIT_JSON_REFICES', true);
define('EPIC_IS_ON', false);
define('ALLOWED_CONCEPTS_FOR_OTHER_TENANT_SCHEMES', false);
define('URI_PREFIX', 'http://mertens/knaw/');
define('UNKNOWN', "Unknown");