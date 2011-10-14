<?php
if (!$OPTS) {
	fwrite(STDERR, "INCLUDE ONLY FROM OTHER SCRIPTS!\n");
	exit(9);
}

if ($OPTS->env) define('APPLICATION_ENV', $OPTS->env);
//bootstrap the application:
include dirname(__FILE__) . '/../public/index.php';
error_reporting(E_ALL);
ini_set('display_errors', true);
