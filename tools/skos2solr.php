<?php
/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

require_once dirname(__FILE__) . '/../library/OpenSKOS/Rdf/Parser.php';
require_once 'Zend/Console/Getopt.php';

$opts = new Zend_Console_Getopt(OpenSKOS_Rdf_Parser::$get_opts);

try {
	$parser = OpenSKOS_Rdf_Parser::factory($opts);
	$parser->process();
} catch (OpenSKOS_Rdf_Parser_Exception $e) {
	fwrite(STDERR, $e->getMessage());
	exit($e->getCode());
}
