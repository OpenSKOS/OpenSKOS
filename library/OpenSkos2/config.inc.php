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

// should be true for BeG  and false for meertens
define('TENANTS_AND_SETS_IN_MYSQL', false);
define('ENABLE_STATUSSES_SYSTEM', true);
define('OMIT_JSON_REFICES', true);
define('EPIC_IS_ON', false);
define('ALLOWED_CONCEPTS_FOR_OTHER_TENANT_SCHEMES', false);
define('URI_PREFIX', 'http://mertens/knaw/');
define('UNKNOWN', "Unknown");