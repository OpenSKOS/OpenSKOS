<?php
require_once dirname(__FILE__) . '/../library/OpenSKOS/Rdf/Parser.php';
require_once 'Zend/Console/Getopt.php';

$opts = new Zend_Console_Getopt(OpenSKOS_Rdf_Parser::$get_opts);

$parser = OpenSKOS_Rdf_Parser::factory($opts);

try {
	$parser->process();
} catch (OpenSKOS_Rdf_Parser_Exception $e) {
	fwrite(STDERR, $e->getMessage()."\n");
	exit(5);
}
