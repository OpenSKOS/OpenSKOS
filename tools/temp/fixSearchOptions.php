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
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

include dirname(__FILE__) . '/../autoload.inc.php';

/* 
 * Updates the status expired to status obsolete
 */

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



$collectionsMap = [];
$collectionsTable = new \OpenSKOS_Db_Table_Collections();
foreach ($collectionsTable->fetchAll() as $collection) {
    $collectionsMap[$collection->id] = $collection->uri;
}

$usersMap = [];
$usersTable = new \OpenSKOS_Db_Table_Users();
foreach ($usersTable->fetchAll() as $user) {
    $usersMap[$user->id] = $user->uri;
}

$fixedCounter = 0;

foreach ($usersTable->fetchAll() as $user) {
    $fixed = fixOptions(unserialize($user->searchOptions), $collectionsMap);
    $user->searchOptions = serialize($fixed);
    $user->save();
    $fixedCounter ++;
}

$searchProfilesTable = new \OpenSKOS_Db_Table_SearchProfiles();
foreach ($searchProfilesTable->fetchAll() as $profile) {
    $fixed = fixOptions(unserialize($profile->searchOptions), $collectionsMap);
    $profile->searchOptions = serialize($fixed);
    $profile->save();
    $fixedCounter ++;
}

function fixOptions($options)
{
    global $collectionsMap;
    global $usersMap;
    
    if (!empty($options['collections'])) {
        $options['collections'] = fixFromMap($options['collections'], $collectionsMap);
    }
    
    if (!empty($options['interactionByUsers'])) {
        $options['interactionByUsers'] = fixFromMap($options['interactionByUsers'], $usersMap);
    }
    
    return $options;
}


function fixFromMap($optionsValues, $map)
{
    $result = [];
    foreach ($optionsValues as $idOrUri) {
        if (isset($map[$idOrUri])) {
            $result[] = $map[$idOrUri];
        } elseif (in_array($idOrUri, $map)) {
            $result[] = $idOrUri;
        }
    }
    
    return $result;
}

echo $fixedCounter . ' users and profiles were updated.' . "\n";
