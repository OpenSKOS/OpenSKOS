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

//commits the solr index
require_once 'Zend/Console/Getopt.php';
$opts = array(
	'verbose|v' => 'Print debug messages to STDOUT',
	'help|?' => 'Print this usage message',
	'env|e=s' => 'The environment to use (defaults to "production")',
	'query|q=s' => 'valid Lucene query for delete action'
);
$OPTS = new Zend_Console_Getopt($opts);

if ($OPTS->help) {
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(0);
}

$args = $OPTS->getRemainingArgs();
if (!$args || count($args)<1) {
	echo str_replace('[ options ]', '[ options ] action(s)', $OPTS->getUsageMessage());
	fwrite(STDERR, "Expected 1 or more actions (commit|update|delete)\n");
	exit(1);
}
$actions = $args;

$query = $OPTS->query;
if ($query && !in_array('delete', $actions)) {
	fwrite(STDERR, "query argument only used for delete action\n");
	exit(1);
} elseif (!$query && in_array('delete', $actions)) {
	fwrite(STDERR, "missing required query argument for delete action\n");
	exit(1);
}

//logical order of actions:
$orderedActions = array();
foreach (array('delete', 'commit', 'optimize') as $action) {
	if (in_array($action, $actions)) {
		$orderedActions[] = $action;
	}
}

include 'bootstrap.inc.php';
$solr = Zend_Registry::get('OpenSKOS_Solr');
foreach ($orderedActions as $action) {
	if ($OPTS->verbose) fwrite(STDOUT, "performing action `{$action}` on Solr index: ");
	switch ($action) {
		case 'commit':
		case 'optimize':
			$solr->$action();
			break;
		case 'delete':
			$solr->$action($query);
			break;
		default:
			fwrite(STDERR, 'Unkown Solr action: '.$action."\n");
			exit(3);
	}
	if ($OPTS->verbose) fwrite(STDOUT, "done\n");
}

exit(0);
