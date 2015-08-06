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
$fixedConceptsCounter = 0;
$notationsCounter = 0;

$docsToRestore = [];

$apiModel = Api_Models_Concepts::factory();
$solr = OpenSKOS_Solr::getInstance()->cleanCopy();
$limit = 100;
$offset = 0;
do {
    $facetsResponse = $solr
        ->limit(0,0)
        ->search('timestamp:[2015-04-24T00:00:00Z TO 2015-04-25T06:00:00Z] AND deleted:true',
            [
                'facet' => 'true',
                'facet.field' => 'notation',
                'facet.mincount' => 1,
                'facet.limit' => $limit,
                'facet.offset' => $offset,
            ]
        );
    
    $facetFields = $facetsResponse['facet_counts']['facet_fields'];
    
    if (!empty($facetFields['notation'])) {
        foreach ($facetFields['notation'] as $notation => $counts) {
            $notationsCounter ++;
            
            echo 'Check notation "' . $notation . '"' . "\n";
            
            $apiModel->setQueryParam('sort', 'modified_timestamp asc');
            $response = $apiModel->getConcepts('notation:"' . $notation . '"', true);

            $perTenant = [];
            foreach ($response['response']['docs'] as $doc) {
                if (!isset($perTenant[$doc['tenant']])) {
                    $perTenant[$doc['tenant']] = [];
                }
                $perTenant[$doc['tenant']][] = $doc;
            }
            
            foreach ($perTenant as $tenant => $tenantDocs) {
                
                // Nothing to fix if there is at least one not deleted doc.                
                $allDocsAreDeleted = true;
                foreach ($tenantDocs as $singleDoc) {
                    if (!$singleDoc['deleted']) {
                        $allDocsAreDeleted = false;
                        break;
                    }
                }
                
                
                if ($allDocsAreDeleted) {
                    // The last one is with highest modified_timestamp. We restore it.
                    $docsToRestore[] = array_pop($tenantDocs)['uuid'];
                }
            }
        }
    }
    
    $offset += $limit;
    
} while (!empty($facetFields['notation']));

// Restores all found buggy docs.

foreach ($docsToRestore as $docToRestoreUuid) {
    $conceptToRestore = new Editor_Models_Concept($apiModel->getConcept($docToRestoreUuid, true));
    
    echo 'Restoring: ' . $conceptToRestore['uuid'] . ', notation "' . $conceptToRestore['notation'][0] . '" for tenant "' . $conceptToRestore['tenant'] . '"' . "\n";
    
    $conceptToRestore->update(
        [],
        [
            'deleted' => false,
            'status' => OpenSKOS_Concept_Status::APPROVED,
            'deleted_by' => null,
            'deleted_timestamp' => null,
        ],
        true,
        true
    );

    $fixedConceptsCounter ++;
}

echo $notationsCounter . ' notations were checked.' . "\n";
echo $fixedConceptsCounter . ' concepts were restored.' . "\n";