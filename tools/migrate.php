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
/**
 * Script to migrate the data from SOLR to Jena run as following: 
 * Run the file as : php tools/migrate.php --endpoint http://<host>:<port>/path/core/select --tenant=<code>
 */
require dirname(__FILE__) . '/autoload.inc.php';

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Resource;
use Rhumsaa\Uuid\Uuid;

$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'tenant=s' => 'Tenant to migrate',
    'start|s=s' => 'Start from that record'
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$resourceManager->setIsNoCommitMode(true);

$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$tenant = $OPTS->tenant;

$endPoint = $OPTS->endpoint . "?q=tenant%3A$tenant&rows=100&wt=json";
var_dump($endPoint);
$init = json_decode(file_get_contents($endPoint), true);
$total = $init['response']['numFound'];

if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}


$getFieldsInClass = function ($class) {
    $retVal = '';
    foreach (\OpenSkos2\Concept::$classes[$class] as $field) {
        //$return [str_replace('http://www.w3.org/2004/02/skos/core#', '', $field)] = $field;
        // olha
        $index = strrpos($field, "#");
        $retVal [substr($field, $index + 1)] = $field;
    }
    return $retVal;
};

$labelMapping = array_merge($getFieldsInClass('LexicalLabels'), $getFieldsInClass('DocumentationProperties'));

$users = [];
$notFoundUsers = [];
$collections = [];
$userModel = new OpenSKOS_Db_Table_Users();
$collectionModel = new OpenSKOS_Db_Table_Collections();

$fetchRowWithRetries = function ($model, $query) {
    $tries = 0;
    $maxTries = 3;
    do {
        try {
            return $model->fetchRow($query);
        } catch (\Exception $exception) {
            echo 'retry mysql connect' . PHP_EOL;
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
        'callback' => function ($value) use ($userModel, &$users, &$notFoundUsers, $tenant, $fetchRowWithRetries) {
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
                            // olha: discrepance tussen tenant-solr (meertens) en tenant-code in Mysql database mi
                            //. 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
                    );
                } else {
                    $user = $fetchRowWithRetries(
                            $userModel, 'name = ' . $userModel->getAdapter()->quote($value) . ' '
                            . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
                    );
                }
                if (!$user) {
                    echo "Could not find user with id/name: {$value}\n";
                    $notFoundUsers[] = $value;
                    $users[$value] = null;
                } else {
                    $users[$value] = $user->getFoafPerson();
                }
            }
            return $users[$value];
        },
        'fields' => [
            'modified_by' => DcTerms::CONTRIBUTOR,
            'created_by' => DcTerms::CREATOR,
            'dcterms_creator' => DcTerms::CREATOR,
            'approved_by' => OpenSkos::ACCEPTEDBY,
            'deleted_by' => OpenSkos::DELETEDBY,
        ],
    ],
    'collection' => [
        'callback' => function ($value) use ($collectionModel, &$collections, $tenant, $fetchRowWithRetries) {
            if (!$value) {
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
                    echo "Could not find tenant collection (migrating into set) with id: {$value}\n";
                    $collections [$value] = null;
                } else {
                    try {
                        $collections [$value] = $collection->getUri();
                    } catch (Zend_Db_Table_Row_Exception $ex) {
                        $uuid = Uuid::uuid4();
                        //$uri = Resource::generatePidEPIC($uuid, 'Dataset');
                        // temporary!!!!
                        $collections [$value] = new \OpenSkos2\Rdf\Uri("http:/tmp-bypass-epic/" . $uuid);
                        var_dump($ex->getMessage());
                        var_dump("So, the set (former tenant collection) handle/uri is generated on the fly. ");
                    }
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
                $logger->info('found uri which is not valid "' . $value . '"');
                // We will keep it and urlencode it to be able to insert it in Jena
                $value = urlencode($value);
            }
            return new \OpenSkos2\Rdf\Uri($value);
        },
        'fields' => array_merge(
                $getFieldsInClass('SemanticRelations'), $getFieldsInClass('MappingProperties'), $getFieldsInClass('ConceptSchemes'), $getFieldsInClass('SkosCollections'), [
            'member' => Skos::MEMBER,
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
            // Olha: the field below is added because no timestamp in jena
            'timestamp' => DcTerms::DATESUBMITTED,
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
            'SkosCollections' => 'SkosCollections',
        ]
    ]
];

var_dump($total);
do {
    $logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $counter++;
        try {
            $uri = $doc['uri'];
            // Prevent deleted resources from having same uri.
            if (!empty($doc['deleted'])) {
                $uri = rtrim($uri, '/') . '/deleted';
            }

            switch ($doc['class']) {
                case 'ConceptScheme':
                    $resource = new \OpenSkos2\ConceptScheme($uri);
                    break;
                case 'Concept':
                    $resource = new \OpenSkos2\Concept($uri);
                    break;
                /// 
                case 'Collection':
                    $resource = new \OpenSkos2\Set($uri);
                    break;
                case 'SKOSCollection': // 
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
                            }
                        }
                        continue 2;
                    }
                }


                if (preg_match('#dcterms_(.+)#', $field, $match)) {
                    if ($resource->hasProperty('http://purl.org/dc/terms/' . $match[1])) {
                        $logger->info("found dc field " . $field . " that is already filled (could be double data)");
                    }

                    foreach ($value as $v) {
                        $resource->addProperty('http://purl.org/dc/terms/' . $match[1], new \OpenSkos2\Rdf\Literal($v));
                    }
                    continue;
                }

                throw new Exception("What to do with field {$field}");
            }

            // Set status deleted
            if (!empty($doc['deleted'])) {
                $resource->setProperty(OpenSkos::STATUS, new OpenSkos2\Rdf\Literal(\OpenSkos2\Concept::STATUS_DELETED));
            }

            // Add tenant in graph
            $resource->addProperty(OpenSkos2\Namespaces\OpenSkos::TENANT, new OpenSkos2\Rdf\Literal($tenant));

            $resourceManager->insert($resource);
        } catch (Exception $ex) {
            var_dump("The document below has not been added because: " . $ex->getMessage());
            var_dump($doc);
            continue;
        }
    }
} while ($counter < $total && isset($data['response']['docs']));


echo "done!";

// List of issues:

//1) tenant is "meertens" for solr and "mi" for mysql; mysql's mi is used to fetch a user, commented out now;
// way out: make two params: tenant for solr and tenant for mysql

// 2) tenant resource is not added properly as a resource, only as a literal (change the code); 

// 3) Sets are not added despite it should have been: there is a bug in the code; at least they are not displayed in the browser

// 4) Set's uri is generated on the fly, because there must be a column uri in the table "collection", which is not on our MySql

// 5) altering test database: had to chnage collection code from 5 to 1, otherwise error reported

// 6) handling corrupted data: instead of exception the corrupted resource is not added with logging and the migration continues

// 7) had to add column "uri" for users, otherwise cannot test my stuff, but it should not influence migration.