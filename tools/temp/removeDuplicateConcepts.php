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
	fwrite(STDERR, $e->getMessage()."\n");
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(1);
}

include dirname(__FILE__) . '/../bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));


// Concepts
$deletedConceptsCounter = 0;
$notationsCounter = 0;

$apiModel = Api_Models_Concepts::factory();
$solr = OpenSKOS_Solr::getInstance()->cleanCopy();
$facetsCount = 0;
do {
    $facetsResponse = $solr
        ->limit(0,0)
        ->search(
            'deleted:false',
            [
                'facet' => 'true',
                'facet.field' => 'notation',
                'facet.mincount' => 2
            ]
        );
    
    $facetFields = $facetsResponse['facet_counts']['facet_fields'];
    
    if (!empty($facetFields['notation'])) {
        foreach ($facetFields['notation'] as $notation => $counts) {
            $notationsCounter ++;
            
            echo 'Process: ' . $notation . ' with "' . $counts . '" duplicates' . "\n";

            $apiModel->setQueryParam('sort', 'modified_timestamp asc');
            $response  = $apiModel->getConcepts('notation: ' . $notation);

            $lastConcept = array_pop($response['response']['docs']);
            echo 'We keep: ' . $lastConcept['uuid'] . ' modified timestamp: "' . $lastConcept['modified_timestamp'] . '"' . "\n";
            foreach ($response['response']['docs'] as $doc) {
                $deleteConcept = new Editor_Models_Concept(new Api_Models_Concept($doc));
                echo 'Mark as delete: ' . $deleteConcept['uuid'] . ' modified timestamp: "' . $deleteConcept['modified_timestamp'] . '"' . "\n";

                $deleteConcept->delete(true);
                $deletedConceptsCounter ++;
            }
        }
    }
    
} while (!empty($facetFields['notation']));

echo $notationsCounter . ' concepts with duplicates found.' . "\n";
echo $deletedConceptsCounter . ' concepts were deleted.' . "\n";