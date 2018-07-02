<?php
/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2017 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 *
 * Repair incorrectly filled search profiles on the BeeldenGeluid test environment #33979
 */

include dirname(__FILE__) . '/../autoload.inc.php';

require_once 'Zend/Console/Getopt.php';
$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/../bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));



$searchProfilesTable = new \OpenSKOS_Db_Table_SearchProfiles();
foreach ($searchProfilesTable->fetchAll() as $profile) {
    $dbData = unserialize($profile->searchOptions);
    if (isset($dbData['collections'])){
        foreach ($dbData['collections'] as $key => $coll){
            $newColl = preg_replace('#http://accept.openskos.beeldengeluid.nl.pictura-dp.nl/api/collections/beng:#', 'http://data.beeldengeluid.nl/', $coll);
            $dbData['collections'][$key] = $newColl;
        }
    }
    $profile->searchOptions = serialize($dbData);
    $profile->save();
    print ($profile->searchOptions);
    print "\n\n";
}
