<?php

use DI\Container;
use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Rdf;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';
require_once 'utils_functions.php';


fwrite(STDOUT, "\n\n\n Starting script concept.php ... \n ");

$opts = [
  'key=s' => 'user key',
  'tenant=s' => 'tenant code',
];

$OPTS = new Zend_Console_Getopt($opts);

require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');

$model = new OpenSKOS_Db_Table_Users();

$must_params = ['tenant', 'key'];
foreach ($must_params as $name) {
  if (null === $OPTS->$name) {
    fwrite(STDERR, "missing required `" . $name . "` argument\n");
    exit(1);
  }
}

check_if_admin($OPTS->tenant, $OPTS->key, $resourceManager, $model);


$args = $OPTS->getRemainingArgs();
$action = $args[count($args) - 1];

function delete_all_concepts($resourceManager) {
  $conceptURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, new \OpenSkos2\Rdf\Uri(Concept::TYPE), null);
  $concepts = $resourceManager->fetchByUris($conceptURIs, Concept::TYPE);
  foreach ($concepts as $concept) {
    $resourceManager->delete($concept, Concept::TYPE); // should also clean solr according to the code, but it does not. Possible reason: dependency injection does not work well.
    $resourceManager->deleteReferencesToObject($concept);
  }
}


function delete_all_concepts_solr($solrResourceManager) {
  $conceptURIs = $solrResourceManager->search("*:*",50000000);
  foreach ($conceptURIs as $conceptURI) {
    $solrResourceManager->delete(new \OpenSkos2\Rdf\Uri($conceptURI));
  }
}



switch ($action) {
  case 'delete':
    if ($OPTS->uri == null) {
      delete_all_concepts($resourceManager);
      fwrite(STDOUT, "all concepts are deleted from the triple store, now: clean up solr.\n");
      delete_all_concepts_solr($solrResourceManager);
      fwrite(STDOUT, "all concepts are deleted from solr.\n");
      
    } else {
      fwrite(STDERR, "deleting a concept by its uri is not implemented yet\n");
      exit(1);
    };
    break;
  default:
    fwrite(STDERR, "unkown (not yet implemented) action `{$action}`\n");
    exit(1);
}

exit(0);

// php concept.php --tenant=example --key=xxx  delete  
   
