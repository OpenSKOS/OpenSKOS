<?php

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\RelationManager;


include_once dirname(__FILE__) . '/autoload.inc.php';


$opts = [];

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}
include_once dirname(__FILE__) . '/bootstrap.inc.php';

$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();


$resManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$resManager->setIsNoCommitMode(false);



function remove_dangling_references($manager, $resources, $property, $rdfType) {
    $removed=0;
    foreach ($resources as $resource) {
        $references = $resource->getProperty($property);
        $oldcount= count($references)
;        $newreferences = array();
        foreach ($references as $reference) {
            $count = $manager->countTriples('<' . trim($reference->getUri()) . '>', '<' . Rdf::TYPE . '>', '<' . $rdfType . '>');
            if ($count > 0) {
                $newreferences[]=$reference;
            }
        }
        $resource -> unsetProperty($property);
        if (count($newreferences) > 0) {
            $resource->addProperties($property, $newreferences);
        }
        $manager->replace($resource);
        $removed += count($newreferences)-$oldcount;
    }
    echo "Removed ". $removed . " references \n";
}

function cleaner($resourceManager) {
    echo "Removing dangling has-top-concept references in concept schemata ... \n";
    $schemataURIs = $resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::CONCEPTSCHEME . '>', null);
    $schemata = $resourceManager->fetchByUris($schemataURIs, Skos::CONCEPTSCHEME);
    remove_dangling_references($resourceManager, $schemata, Skos::HASTOPCONCEPT, Skos::CONCEPT);

    echo "Removing dangling member references in skos collections ... \n";
    $skoscollectionsURIs = $resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::SKOSCOLLECTION . '>', null);
    $skoscollections = $resourceManager->fetchByUris($skoscollectionsURIs, Skos::SKOSCOLLECTION);
    remove_dangling_references($resourceManager, $skoscollections, Skos::MEMBER, Skos::CONCEPT);

    echo "Removing dangling in-scheme references in concepts (first retrieve all concepts)... \n";
    $conceptURIs = $resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::CONCEPT . '>', null);
    $concepts = $resourceManager->fetchByUris($conceptURIs, Skos::CONCEPT);
    remove_dangling_references($resourceManager, $concepts, Skos::INSCHEME, Skos::CONCEPTSCHEME);

    echo "Removing dangling top-concept-of references in concepts ... .\n";
    remove_dangling_references($resourceManager, $concepts, Skos::TOPCONCEPTOF, Skos::CONCEPTSCHEME);

    echo "Removing dangling inSKOSCOLLECTION references in concepts ...\n";
    remove_dangling_references($resourceManager, $concepts, OpenSkos::INSKOSCOLLECTION, Skos::SKOSCOLLECTION);

    $relations = RelationManager::fetchConceptConceptRelationsNameUri();
    foreach ($relations as $relName => $relUri) {
        echo "Removing dangling concept-concept-relation " . $relName . " references in concepts ...\n";
        remove_dangling_references($resourceManager, $concepts, $relUri, Skos::CONCEPT);
    }
}

cleaner($resManager);

echo "Removing dangling references is done";