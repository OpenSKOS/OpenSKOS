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

Zend_Session::start(true);

// Concepts
$deletedConceptsCounter = 0;
$notationsCounter = 0;

$apiModel = Api_Models_Concepts::factory();

$solrTenant = OpenSKOS_Solr::getInstance()->cleanCopy();
$facetsResponseTenant = $solrTenant
    ->limit(0,0)
    ->search(
        '*:*',
        [
            'facet' => 'true',
            'facet.field' => 'tenant',
        ]
    );


$facetFieldsTenant = $facetsResponseTenant['facet_counts']['facet_fields'];

foreach ($facetFieldsTenant['tenant'] as $tenant => $countsTenant) {

    echo 'Processing tenant ' . $tenant . '.' . "\n";
    
    $solrNotation = OpenSKOS_Solr::getInstance()->cleanCopy();
    do {
        $facetsResponseNotation = $solrNotation
            ->limit(0,0)
            ->search(
                'deleted:false AND tenant:' . $tenant,
                [
                    'facet' => 'true',
                    'facet.field' => 'notation',
                    'facet.mincount' => 2
                ]
            );

        $facetFieldsNotation = $facetsResponseNotation['facet_counts']['facet_fields'];

        if (!empty($facetFieldsNotation['notation'])) {
            foreach ($facetFieldsNotation['notation'] as $notation => $countsNotation) {
                $notationsCounter ++;

                echo 'Process: ' . $notation . ' with "' . $countsNotation . '" duplicates' . "\n";

                $apiModel->setQueryParam('sort', 'modified_timestamp asc, status desc');
                $response = $apiModel->getConcepts('notation:"' . $notation . '" AND tenant:"' . $tenant . '"');

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

    } while (!empty($facetFieldsNotation['notation']));
}

echo $notationsCounter . ' concepts with duplicates found.' . "\n";
echo $deletedConceptsCounter . ' concepts were deleted.' . "\n";