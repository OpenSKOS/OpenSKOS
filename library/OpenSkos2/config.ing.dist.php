<?php

define('BACKEND_NAME', 'ccr');
define('APPLICATION_BASE_PATH', '/app/' . BACKEND_NAME);
define('COMMON_APPLICATION_INI', '/app/' . BACKEND_NAME . '/application/configs/application.ini');
define('ERROR_LOG', '/app/' . BACKEND_NAME . '/data/ValidationErrors.txt');

// roles //
define('ROOT', 'your string');
define('ADMINISTRATOR', 'your string');
define('EDITOR', 'your string');
define('USER', 'your string');
define('GUEST', 'your string');

//
define('BACKWARD_COMPATIBLE', true);
define('DEFAULT_AUTHORISATION', true);
define('DEFAULT_DELETION', true);
define('DEFAULT_RELATIONTYPES', true);
define('MAXIMAL_ROWS', 'your number');
define('MAXIMAL_TIME_LIMIT', 'your number'); //sec
define('NORMAL_TIME_LIMIT', 'your number'); // sec

define('ENABLE_STATUSSES_SYSTEM', 'your flag');
define('OMIT_JSON_REFICES', 'your flag');
define('EPIC_IS_ON', 'your flag');
define('ALLOWED_CONCEPTS_FOR_OTHER_TENANT_SCHEMES', 'your flag');
define('URI_PREFIX', 'your prefix');
define('UNKNOWN', "Unknown");
