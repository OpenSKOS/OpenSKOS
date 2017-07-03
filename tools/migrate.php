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
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
/**
 * Script to migrate the data from SOLR to Jena run as following:
 * Run the file as : php migrate.php --endpoint=http://<host>:<port>/solr/<core>/select --tenant=<tenant_code> --db-hostname=<db-host> --db-database=<> --db-username=<> --purge=1 --defaultSet="isocat"
 */
require_once dirname(__FILE__) . '/autoload.inc.php';

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\ConceptScheme;
use OpenSkos2\SkosCollection;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Uri;

$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'db-hostname=s' => 'Origin database host',
    'db-database=s' => 'Origin database name',
    'db-username=s' => 'Origin database username',
    'db-password=s' => 'Origin database password',
    'tenant=s' => 'Tenant (code)',
    'start|s=s' => 'Start from that record',
    'dryrun' => 'Only validate the data, do not migrate it.',
    'debug' => 'Show debug info.',
    'modified|m=s' => 'Fetch only those modified after that date.',
    'defaultSet=s' => 'Set code to be used when tenant collection in the slor does not have any corresponding set in the tripl store',
    'purge' => 'if set to 1 then purges the triples tore and slor before migrating'
];

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}
require_once dirname(__FILE__) . '/bootstrap.inc.php';
require_once 'utils_functions.php';

validateOptions($OPTS);
$dbSource = \Zend_Db::factory('Pdo_Mysql', array(
        'host' => $OPTS->getOption('db-hostname'),
        'dbname' => $OPTS->getOption('db-database'),
        'username' => $OPTS->getOption('db-username'),
        'password' => $OPTS->getOption('db-password'),
    ));
$dbSource->setFetchMode(\PDO::FETCH_OBJ);

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$tenantManager = $diContainer->make('\OpenSkos2\TenantManager');
$labelManager = $diContainer->make('\OpenSkos2\SkosXl\LabelManager'); // Discuss

/**
 * @var $conceptManager \OpenSkos2\ConceptManager
 */
$conceptManager = $diContainer->make('\OpenSkos2\ConceptManager');
$conceptManager->setLabelManager($labelManager);

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('debug')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, $logLevel
));

if ($OPTS->purge) {
    
    $logger->info("Purging triple store: scheme");
    $schemeURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, 
        new Uri(ConceptScheme::TYPE));
    foreach ($schemeURIs as $schemeURI) {
        $resourceManager->delete(new Uri($schemeURI));
    }
    $logger->info("Purging triple store: skos collections");
    $collectionURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, 
        new Uri(SkosCollection::TYPE));
    foreach ($collectionURIs as $collectionURI) {
        $resourceManager->delete(new Uri($collectionURI));
    }
    $logger->info("Purging triple store and solr: concepts");
    $conceptURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, 
        new Uri(Concept::TYPE));
    foreach ($conceptURIs as $conceptURI) {
        $conceptManager->delete(new Uri($conceptURI));
    }
    $logger->info("Purging solr from possible garabage left from bad experiments");
    $solrManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');
    $garbage = $solrManager->search("*:*", 10000000);
    foreach ($garbage as $uri) {
        $solrManager->delete(new Uri($uri));
    }
}


$conceptManager->setIsNoCommitMode(true);
$tenantCode = $OPTS->tenant;
$tenantResource = $resourceManager -> fetchByUuid($tenantCode, \OpenSkos2\Tenant::TYPE, 'openskos:code');
$defaultSet = $OPTS->defaultSet;

$isDryRun = $OPTS->getOption('dryrun');

$modifiedSince = $OPTS->getOption('modified');
$queryQuery = 'tenant:"' . $tenantCode . '"';
if (!empty($modifiedSince)) {
    $logger->info('Index only concepts modified (timestamp field) after ' . $modifiedSince);

    $checkDate = DateTime::createFromFormat(DATE_ATOM, $modifiedSince);
    if ($checkDate === false) {
        throw new \Exception('Input date for modified option is not valid iso8601 (for solr)');
    }

    $queryQuery .= ' AND timestamp:[' . $modifiedSince . ' TO *]';
}
$query = [
    'q' => $queryQuery,
    'rows' => 100,
    'wt' => 'json',
];
$endPoint = $OPTS->endpoint . "?" . http_build_query($query);
$init = getFileContents($endPoint);
$total = $init['response']['numFound'];

if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
$getFieldsInClass = function ($class) {
    $return = '';
    foreach (\OpenSkos2\Concept::$classes[$class] as $field) {
        $return [str_replace('http://www.w3.org/2004/02/skos/core#', '', $field)] = $field;
    }
    return $return;
};
$labelMapping = array_merge($getFieldsInClass('LexicalLabels'), $getFieldsInClass('DocumentationProperties'));
$users = [];
$notFoundUsers = [];
$notFoundCollections = [];
$collections = [];
$userModel = new OpenSKOS_Db_Table_Users();
$collectionModel = new OpenSKOS_Db_Table_Collections();
$fetchRowWithRetries = function ($model, $query) use ($logger) {
    $tries = 0;
    $maxTries = 3;
    do {
        try {
            return $model->fetchRow($query);
        } catch (\Exception $exception) {
            $logger->debug('retry mysql connect');
            // Reconnect
            $model->getAdapter()->closeConnection();
            $modelClass = get_class($model);
            $model = new $modelClass();
            $tries ++;
        }
    } while ($tries < $maxTries);
    if ($exception) {
        throw $exception;
    }
};
$mappings = [
    'users' => [
        'callback' => function ($value) use (
        $userModel,
        &$users,
        &$notFoundUsers,
        $tenantCode,
        $fetchRowWithRetries,
        $logger,
        $isDryRun
        ) {
            if (!$value) {
                return null;
            }
            if (in_array($value, $notFoundUsers)) {
                return null;
            }
            if (!isset($users[$value])) {
                /**
                 * @var $user OpenSKOS_Db_Table_Row_User
                 */
                if (is_numeric($value)) {
                    $user = $fetchRowWithRetries(
                        $userModel, 'id = ' . $userModel->getAdapter()->quote($value) . ' '
                        . 'AND tenant = ' . $userModel->getAdapter()->quote($tenantCode)
                    );
                } else {
                    $user = $fetchRowWithRetries(
                        $userModel, 'name = ' . $userModel->getAdapter()->quote($value) . ' '
                        . 'AND tenant = ' . $userModel->getAdapter()->quote($tenantCode)
                    );
                }
                if (!$user) {
                    $logger->notice("Could not find user with id/name: {$value}");
                    $notFoundUsers[] = $value;
                    $users[$value] = null;
                } else {
                    $users[$value] = $user->getFoafPerson(!$isDryRun);
                }
            }
            return $users[$value];
        },
        'fields' => [
            'modified_by' => OpenSkos::MODIFIEDBY,
            'created_by' => DcTerms::CREATOR,
            'dcterms_creator' => DcTerms::CREATOR,
            'approved_by' => OpenSkos::ACCEPTEDBY,
            'deleted_by' => OpenSkos::DELETEDBY,
        ],
    ],
    'collection' => [
        'callback' => function ($value) use (
        $collectionModel,
        &$collections,
        &$notFoundCollections,
        $tenantCode,
        $fetchRowWithRetries,
        $logger,
        $isDryRun
        ) {
            if (!$value) {
                return null;
            }
            if (in_array($value, $notFoundCollections)) {
                return null;
            }
            if (!isset($collections[$value])) {
                /**
                 * @var $collection OpenSKOS_Db_Table_Row_Collection
                 */
                $collection = $fetchRowWithRetries(
                    $collectionModel, 'id = ' . $collectionModel->getAdapter()->quote($value)
                );
                if (!$collection) {
                    $logger->notice("Could not find collection with id: {$value}");
                    $notFoundCollections[] = $value;
                    $collections[$value] = null;
                } else {
                    $collections[$value] = $collection->getUri(!$isDryRun);
                }
            }
            return $collections[$value];
        },
        'fields' => [
            'collection' => OpenSkos2\Namespaces\OpenSkos::SET,
        ],
    ],
    'uris' => [
        'callback' => function ($value) use ($logger) {
            $value = trim($value);
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                $logger->notice('found uri which is not valid "' . $value . '"');
                // We will keep it and urlencode it to be able to insert it in Jena
                $value = urlencode($value);
            }
            return new \OpenSkos2\Rdf\Uri($value);
        },
        'fields' => array_merge(
            $getFieldsInClass('SemanticRelations'), $getFieldsInClass('MappingProperties'), $getFieldsInClass('ConceptSchemes'), [
            'member' => Skos::MEMBER, // for skos collections 
            //'inSkosCollection' => OpenSkos::INSKOSCOLLECTION, // DISCUSS
            'inScheme' => Skos::INSCHEME,
            // has top concept vs memebr DISCUSS
            ]
        ),
    ],
    'literals' => [
        'callback' => function ($value) {
            return new \OpenSkos2\Rdf\Literal($value);
        },
        'fields' => [
            'status' => OpenSkos::STATUS,
            'notation' => Skos::NOTATION,
            'uuid' => OpenSkos::UUID,
        ]
    ],
    'dates' => [
        'callback' => function ($value) {
            return new \OpenSkos2\Rdf\Literal($value, null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        },
        'fields' => [
            'approved_timestamp' => DcTerms::DATEACCEPTED,
            'created_timestamp' => DcTerms::DATESUBMITTED,
            'modified_timestamp' => DcTerms::MODIFIED,
            'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
            'dcterms_modified' => DcTerms::MODIFIED,
            'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
            'deleted_timestamp' => OpenSkos::DATE_DELETED,
        ]
    ],
    'bool' => [
        'callback' => function ($value) {
            if ($value) {
                return new \OpenSkos2\Rdf\Literal(true, null, \OpenSkos2\Rdf\Literal::TYPE_BOOL);
            }
        },
        'fields' => [
            'toBeChecked' => OpenSkos::TOBECHECKED
        ]
    ],
    'ignore' => [
        'callback' => function ($value) {
            return null;
        },
        'fields' => [
            'xml' => 'xml',
            'timestamp' => 'timestamp',
            'xmlns' => 'xmlns',
            'score' => 'score',
            'class' => 'class',
            'uri' => 'uri',
            'prefLabelAutocomplete' => 'prefLabelAutocomplete',
            'prefLabelSort' => 'prefLabelSort',
            'LexicalLabels' => 'LexicalLabels',
            'DocumentationProperties' => 'DocumentationProperties',
            'SemanticRelations' => 'SemanticRelations',
            'deleted' => 'deleted',
            'tenant' => 'tenant',
            'statusOtherConcept' => 'statusOtherConcept',
            'statusOtherConceptLabelToFill' => 'statusOtherConceptLabelToFill',
            'ConceptCollections' => 'ConceptCollections',
            'inSkosCollection' => 'inSkosCollection',
        ]
    ]
];
$logger->info('Found ' . $total . ' records');
var_dump('Skos Collection round');
insert_round('SKOSCollection', $logger, $endPoint, $counter, $resourceManager, $tenantResource, $total, $mappings, $defaultSet, $isDryRun);
var_dump('ConceptScheme round');
insert_round('ConceptScheme', $logger, $endPoint, $counter, $resourceManager, $tenantResource, $total, $mappings, $defaultSet, $isDryRun);


var_dump('Concept round');
insert_round('Concept', $logger, $endPoint, $counter, $resourceManager, $tenantResource, $total, $mappings, $defaultSet, $isDryRun, $labelMapping, $conceptManager);

function insert_round($docClass, $logger, $endPoint, $counter, $resourceManager, $tenantResource, $total, $mappings, $defaultSet, $isDryRun, $labelMapping = null, $conceptManager = null)
{
    do {
        $logger->debug("fetching " . $endPoint . "&start=$counter");
        if ($counter % 5000 == 0) {
            $logger->info('Processed so far: ' . $counter);
        }
        $data = getFileContents($endPoint . "&start=$counter");
        foreach ($data['response']['docs'] as $doc) {
            $counter++;
            if ($docClass !== $doc['class']) {
                continue;
            }
            $uri = trim($doc['uri']); // seems there are uri's with a space prefix ? :|
            // Prevent deleted resources from having same uri.
            if (!empty($doc['deleted'])) {
                $uri = rtrim($uri, '/') . '/deleted';
            }
            switch ($doc['class']) {
                case 'ConceptScheme':
                    $resource = new \OpenSkos2\ConceptScheme($uri);
                    break;
                case 'Concept':
                    // Fix for notation
                    if (count($doc['notation']) !== 1) {
                        $logger->notice(
                            'found double notations in same concept ' . print_r($doc['notation'])
                            . ' will leave only the first one and import the concept.'
                        );
                        $doc['notation'] = [current($doc['notation'])];
                    }
                    // Make sure we have a valid uri in all cases.
                    $uri = getConceptUri($uri, $doc, $resourceManager);
                    $resource = new \OpenSkos2\Concept($uri);
                    break;
                case 'SKOSCollection':
                    $resource = new \OpenSkos2\SkosCollection($uri);
                    break;
                default:
                    throw new Exception("Didn't expect class: " . $doc['class']);
            }

            foreach ($doc as $field => $value) {
                //this is just a copy field
                if (isset($labelMapping[$field])) {
                    continue;
                }
                $lang = null;
                if (preg_match('#^(?<field>.+)@(?<lang>\w+)$#', $field, $m2)) {
                    $lang = $m2['lang'];
                    $field = $m2['field'];
                    if (isset($labelMapping[$field])) {
                        foreach ((array) $value as $v) {
                            $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v, $lang));
                        }
                        continue;
                    }
                }
                foreach ($mappings as $mapping) {
                    if (isset($mapping['fields'][$field])) {
                        foreach ((array) $value as $v) {
                            $insertValue = $mapping['callback']($v);
                            if ($insertValue !== null) {
                                $resource->addProperty($mapping['fields'][$field], $insertValue);
                            } elseif (in_array($field, ['created_by', 'dcterms_creator']) && !empty($v)) {
                                // Handle dcterms_creator and dc_creator
                                if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                                    $resource->addProperty(Dc::CREATOR, new \OpenSkos2\Rdf\Literal($v));
                                } else {
                                    $resource->addProperty(DcTerms::CREATOR, new \OpenSkos2\Rdf\Uri($v));
                                }
                            }
                        }
                        continue 2;
                    }
                }
                if (preg_match('#dcterms_(.+)#', $field, $match)) {
                    if ($resource->hasProperty('http://purl.org/dc/terms/' . $match[1])) {
                        $logger->notice("found dc field " . $field . " that is already filled (could be double data)");
                    }
                    foreach ($value as $v) {
                        if ($field != 'dcterms_contributor') {
                            $resource->addProperty('http://purl.org/dc/terms/' . $match[1], new \OpenSkos2\Rdf\Literal($v));
                        } else {
                            // Handle dcterms_contributor and dc_contributor
                            if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                                $resource->addProperty(Dc::CONTRIBUTOR, new \OpenSkos2\Rdf\Literal($v));
                            } else {
                                $resource->addProperty(DcTerms::CONTRIBUTOR, new \OpenSkos2\Rdf\Uri($v));
                            }
                        }
                    }
                    continue;
                }
                throw new Exception("What to do with field {$field}");
            }
            // Add tenant in graph
            $resource->setProperty(OpenSkos2\Namespaces\OpenSkos::TENANT, $tenantResource->getCode());
            $resource->setProperty(OpenSkos2\Namespaces\DcTerms::PUBLISHER, new Uri($tenantResource->getUri()));

            // Add set to graph
            if (!empty($doc['collection'])) {
                try {
                    $set = $resourceManager->fetchByUuid($doc['collection'], \OpenSkos2\Set::TYPE, 'openskos:code');
                } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
                     $set = $resourceManager->fetchByUuid($defaultSet, \OpenSkos2\Set::TYPE, 'openskos:code');
                }
                $resource->setProperty(\OpenSkos2\Namespaces\OpenSkos::SET, new \OpenSKos2\Rdf\Uri($set->getUri()));
            }

            // Set status deleted
            if (!empty($doc['deleted'])) {
                $resource->setProperty(OpenSkos::STATUS, new OpenSkos2\Rdf\Literal(\OpenSkos2\Concept::STATUS_DELETED));
            }
            // Validate (only if not deleted, all deleted resources are considered valid.
            if (($resource->getType()->getUri() === \OpenSKos2\Concept::TYPE) && ($resource->isDeleted())) {
                $isValid = true;
            } else {
                if ($resource->getType()->getUri() === \OpenSkos2\Set::TYPE) {
                    $setResource = $resource;
                } else {
                    if ($resource->getSet() != null) {
                        $setUri = current($resource->getSet())->getUri();
                        try {
                            $setResource = $resourceManager->fetchByUri($setUri, \OpenSkos2\Set::TYPE);
                        } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
                            throw new \Exception("First create a set in the triple store for the collection "
                            . "with the code {$setUri} . Use the script 'set.php' and the same data as in the table.");
                            exit(1);
                        }
                    } else {
                        $setResource = null;
                    }
                }
                if ($resource instanceof OpenSkos2\Concept) {
                    $validator = new \OpenSkos2\Validator\Resource($conceptManager, $tenantResource, $setResource, false, false, false, $logger);
                } else {
                    $validator = new \OpenSkos2\Validator\Resource($resourceManager, $tenantResource, $setResource, false, false, false, $logger);
                }
                $isValid = validateResource($validator, $resource);
            }
            // Insert
            if ($isValid && !$isDryRun) {
                if ($resource instanceof OpenSkos2\Concept) {
                    insertResource($conceptManager, $resource);
                } else {
                    $logger->info("Inserting {$resource->getUri()}");
                    insertResource($resourceManager, $resource);
                }
            } else {
                if (!$isValid) {
                    $logger->error(implode(' ,', $validator->getErrorMessages()));
                }
            }
        }
    } while ($counter < $total && isset($data['response']['docs']));


    $logger->info("Processed in total: $counter");
    $logger->info("Round {$docClass} Done!");
}

function validateResource(\OpenSkos2\Validator\Resource $validator, OpenSkos2\Rdf\Resource $resource, $retry = 20)
{
    $tried = 0;
    do {
        try {
            return $validator->validate($resource);
        } catch (\Exception $exc) {
            echo 'failed validating retry' . PHP_EOL;
            echo $exc->getMessage() . PHP_EOL;
            ;
            $tried++;
            sleep(5);
        }
    } while ($tried < $retry);
    throw $exc;
}

/**
 * Get file contents with retry, and json decode results
 *
 * @param string $url
 * @param int $retry
 * @param int $count
 * @return \stdClass
 * @throws \Exception
 */
function getFileContents($url, $retry = 20, $count = 0)
{
    $tried = 0;
    do {
        $body = file_get_contents($url);
        if ($body !== false) {
            return json_decode($body, true);
        }
        echo 'failed get contents retry' . PHP_EOL;
        sleep(5);
        $tried++;
    } while ($tried < $retry);
    throw new \Exception('Failed file_get_contents on :' . $url . ' tried: ' . $tried);
}

/**
 * Used to generate uri's for bad data from the source
 * where the uri only had a notation
 *
 * @param string $uri
 * @param array $solrDoc
 * @return string
 */
function getConceptUri($uri, array $solrDoc, $resourceManager)
{
    if (filter_var($uri, FILTER_VALIDATE_URL, $uri)) {
        return $uri;
    }
    try {
                    $set = $resourceManager->fetchByUuid($doc['collection'], \OpenSkos2\Set::TYPE, 'openskos:code');
                } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
                     $set = $resourceManager->fetchByUuid($defaultSet, \OpenSkos2\Set::TYPE, 'openskos:code');
                }
                
    if (count($solrDoc['notation']) !== 1) {
        throw new \RuntimeException('More then one notation: ' . var_export($solrDoc[['notation']]));
    }
    $conceptBaseUris = $set->getProperty(OpenSkos::CONCEPTBASEURI);
    $conceptBaseUri = $conceptBaseUris[0]->getUri();
    return $conceptBaseUri . '/' . current($solrDoc['notation']);
}
