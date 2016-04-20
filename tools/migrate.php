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
 * Run the file as : php tools/migrate.php --endpoint http://<host>:<port>/path/core/select --tenant=<code> --enablestatusses=<bool>
 */
require dirname(__FILE__) . '/autoload.inc.php';

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Rdf\Resource;
use Rhumsaa\Uuid\Uuid;

$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'tenant=s' => 'Tenant to migrate',
    'start|s=s' => 'Start from that record',
    'enablestatusses=s' => 'Enable status system (cnadidate, approved, etc) for the given tenant',
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

$enableStatussesSystem = $OPTS->enablestatusses;

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
        if (!$index) {
            $index = strrpos($field, "/");
        };
        if ($index > 0) {
            $retVal [substr($field, $index + 1)] = $field;
        }
    }
    return $retVal;
};

$labelMapping = array_merge($getFieldsInClass('LexicalLabels'), $getFieldsInClass('DocumentationProperties'));
$notFoundUsers = [];
$collections = [];
$userModel = new OpenSKOS_Db_Table_Users();
$collectionModel = new OpenSKOS_Db_Table_Collections();
$tenantModel = new OpenSKOS_Db_Table_Tenants();

$setsToInsert = []; // parallel to collections: setsToInsert[$value] contains the full row row from MySql, whereas $collections[$value] on ly the uri
$tenantsToInsert = []; // $tenantsToInsert[$tenant] contains the row for tenant with name $tenant. 

$adapter = $userModel->getAdapter();
$cols = $userModel->info('cols');
if (!in_array('uri', $cols)) {
    $adapter->getConnection()->exec('ALTER TABLE user ADD uri VARCHAR(256)');
    $adapter->closeConnection();
}

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
                            . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
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
                    $users[$value] = new \OpenSkos2\Rdf\Uri($user->getFoafPerson()->getUri());
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
        'callback' => function ($value) use ($collectionModel, &$collections, &$setsToInsert, $tenant, $fetchRowWithRetries, $resourceManager) {
            if (!$value) {
                return null;
            }

            if (!isset($collections[$value])) { // collection-id value ccurs for the first time
                // look up MySQL
                /**
                 * @var $collection OpenSKOS_Db_Table_Row_Collection
                 */
                $collection = $fetchRowWithRetries(
                        $collectionModel, 'id = ' . $collectionModel->getAdapter()->quote($value)
                );

                if (!$collection) {
                    echo "Could not find a set (aka tenant collection) with id: {$value}\n";
                    $collections [$value] = null;
                } else {
                    try {
                        $collections [$value] = $collection->getUri();
                    } catch (Zend_Db_Table_Row_Exception $ex) {
                        $collectionTripleStore = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, "'" . $collection['code'] . "'", Dcmi::DATASET);
                        if (count($collectionTripleStore) < 1) { // the set is not yet in the triple store
                            $uuid = Uuid::uuid4();
                            $uri = Resource::generatePidEPIC($uuid, 'Dataset');
                            $setsToInsert[$value] = ['row' => $collection, 'uri' => $uri, 'uuid' => $uuid];
                            var_dump("The set's (aka tenant-collection's) handle/uri " . $uri . " is generated on the fly. ");
                            $collections [$value] = new \OpenSkos2\Rdf\Uri($uri);
                        } else {
                            $collections [$value] = new \OpenSkos2\Rdf\Uri($collectionTripleStore[0]);
                        }
                    }
                }
                return $collections[$value];
            }
        },
                'fields' => [
                    'collection' => OpenSkos2\Namespaces\OpenSkos::SET,
                ],
            ],
            'tenant' => [
                'callback' => function ($value) use ($tenantModel, &$tenantsToInsert, $tenant, $fetchRowWithRetries, $resourceManager) {
                    if (!$value) {
                        return null;
                    }

                    if (!isset($tenantsToInsert[$value])) { // collection-id value ccurs for the first time
                        // look up MySQL
                        /**
                         * @var $collection OpenSKOS_Db_Table_Row_Tenant
                         */
                        // name can be relaced with id
                        $tenantComplete = $fetchRowWithRetries(
                                $tenantModel, 'code = ' . $tenantModel->getAdapter()->quote($value)
                        );

                        if (!$tenantComplete) {
                            echo "Could not find tenant  with code: {$value}\n";
                            $tenantsToInsert [$value] = null;
                            return null;
                        } else {
                            $tenants = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, "'" . $value . "'", Org::FORMALORG);
                            if (count($tenants) < 1) { // this tenant is not yet in the triple store
                                $uuid = Uuid::uuid4();
                                $uri = Resource::generatePidEPIC($uuid, 'FormalOrganization');
                                $tenantsToInsert[$value] = ['row' => $tenantComplete, 'uri' => $uri, 'uuid' => $uuid];
                                var_dump("The institution's  (" . $value . ") handle/uri " . $uri . " is generated on the fly. ");
                                return new \OpenSkos2\Rdf\Uri($uri);
                            } else {
                                return new \OpenSkos2\Rdf\Uri($tenants[0]);
                            }
                        }
                    }
                },
                        'fields' => [
                            'tenant' => OpenSkos2\Namespaces\OpenSkos::TENANT
                        ]
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
                            'uuid' => OpenSkos::UUID,
                            'notation' => Skos::NOTATION,
                            'dcterms_relation' => DcTerms::RELATION,
                            'dcterms_source' => DcTerms::SOURCE
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
                            'deleted_timestamp' => OpenSkos::DATE_DELETED,
                            'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
                            'dcterms_modified' => DcTerms::MODIFIED,
                            'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
                            'timestamp' => DcTerms::DATE,
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
                            'statusOtherConcept' => 'statusOtherConcept',
                            'statusOtherConceptLabelToFill' => 'statusOtherConceptLabelToFill',
                        ]
                    ]
                ];

                $synonym = ['approved_timestamp' => 'dcterms_dateAccepted', 'created_timestamp' => 'dcterms_dateSubmitted', 'modified_timestamp' => 'dcterms_modified'];

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
                                    unset($doc['hasTopConcept']);
                                    $resource = new \OpenSkos2\SkosCollection($uri);
                                    break;
                                default:
                                    throw new Exception("Didn't expect class: " . $doc['class']);
                            }

                            // initialise set-synonym flags
                            $isset_synonym = [];
                            foreach ($synonym as $key => $value) {
                                $isset_synonym[$key] = false;
                            };
                            $setLabels = [];
                            foreach ($doc as $field => $value) {

                                // it is a copy field (label or docproperty) if the language attribute is present.
                                //  to avoid missing labels and docproperties without languages run
                                // another loop after exiting this one, to fix orfans  
                                if (isset($labelMapping[$field])) {
                                    continue;
                                }


                                if (array_key_exists($field, $synonym)) {
                                    if ($isset_synonym[$field]) {
                                        continue;
                                    }
                                }


                                $key_synonym = array_search($field, $synonym);
                                if ($key_synonym) {
                                    if ($isset_synonym[$key_synonym]) {
                                        continue;
                                    }
                                }

                                $lang = null;
                                if (preg_match('#^(?<field>.+)@(?<lang>\w+)$#', $field, $m2)) {
                                    $lang = $m2['lang'];
                                    $field = $m2['field'];
                                    if (isset($labelMapping[$field])) {
                                        foreach ((array) $value as $v) {
                                            $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v, $lang));
                                            $setLabels[$field][] = $v;
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

                                                if (array_key_exists($field, $synonym)) {
                                                    $isset_synonym[$field] = true;
                                                } else {
                                                    if ($key_synonym) {
                                                        $isset_synonym[$key_synonym] = true;
                                                    }
                                                }
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

                            // check if there are orfan (without language) labels and documentation properties
                            foreach ($doc as $field => $value) {
                                if (array_key_exists($field, $labelMapping)) {
                                    foreach ((array) $value as $v) {
                                        if (!array_key_exists($field, $setLabels)) {
                                            $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v));
                                            $setLabels[$field][] = $v;
                                        } else {
                                            if (!in_array($v, $setLabels[$field])) {
                                                $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v));
                                                $setLabels[$field][] = $v;
                                            }
                                        }
                                    }
                                }
                            }

                            // Set status deleted
                            if (!empty($doc['deleted'])) {
                                $resource->setProperty(OpenSkos::STATUS, new OpenSkos2\Rdf\Literal(\OpenSkos2\Concept::STATUS_DELETED));
                            }

                            // Tenant added as a reference in the "mappings"-loop
                            //$resource->addProperty(OpenSkos2\Namespaces\OpenSkos::TENANT, new OpenSkos2\Rdf\Literal($tenant));
                            $resourceManager->deleteSolrIntact(new OpenSkos2\Rdf\Uri($doc['uri'])); // just in case if you run migrate for a couple of times, remove the old intance form the triple store  
                            $resourceManager->insert($resource);
                        } catch (Exception $ex) {
                            var_dump($ex->getMessage());
                            //var_dump($ex->getTraceAsString());
                            var_dump("And the following document has not been added: ");
                            var_dump($doc['uri']);
                        }
                    }
                } while ($counter < $total && isset($data['response']['docs']));
         

                $setPropertyWithCheck = function (&$resource, $property, $val, $isURI, $isBOOL = false) {
                    if (isset($val)) {
                        if (!empty($val)) {
                            if ($isURI) {
                                $resource->setProperty($property, new \OpenSkos2\Rdf\Uri($val));
                            } else {
                                if (!$isBOOL) {
                                    $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val));
                                } else {
                                    $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val, null, \OpenSkos2\Rdf\Literal::TYPE_BOOL));
                                }
                            }
                        }
                    } else {
                        var_dump('WARNING NON-COMPLETE DATA: the property ' . $property . ' is not set in Mysql Database for the resource with uri ' . $resource->getUri());
                    }
                };

                foreach ($tenantsToInsert as $tenantComplete) {
                    $tenantResource = new \OpenSkos2\Tenant($tenantComplete['uri']);
                    $tenantResource->setProperty(OpenSkos::UUID, new \OpenSkos2\Rdf\Literal($tenantComplete['uuid']));
                    $setPropertyWithCheck($tenantResource, OpenSkos::CODE, $tenantComplete['row']['code'], false);
                    $organisation = new \OpenSkos2\Rdf\Resource("nodeID_" . Uuid::uuid4());
                    $setPropertyWithCheck($organisation, vCard::ORGNAME, $tenantComplete['row']['name'], false);
                    $setPropertyWithCheck($organisation, vCard::ORGUNIT, $tenantComplete['row']['organisationUnit'], false);
                    $tenantResource->setProperty(vCard::ORG, $organisation);
                    $setPropertyWithCheck($tenantResource, OpenSkos::WEBPAGE, $tenantComplete['row']['website'], true);
                    $setPropertyWithCheck($tenantResource, vCard::EMAIL, $tenantComplete['row']['email'], false);
                    $adress = new \OpenSkos2\Rdf\Resource("nodeID_" . Uuid::uuid4());
                    $setPropertyWithCheck($adress, vCard::STREET, $tenantComplete['row']['streetAddress'], false);
                    $setPropertyWithCheck($adress, vCard::LOCALITY, $tenantComplete['row']['locality'], false);
                    $setPropertyWithCheck($adress, vCard::PCODE, $tenantComplete['row']['postalCode'], false);
                    $setPropertyWithCheck($adress, vCard::COUNTRY, $tenantComplete['row']['countryName'], false);
                    $tenantResource->setProperty(vCard::ADR, $adress);
                    $setPropertyWithCheck($tenantResource, OpenSkos::DISABLESEARCHINOTERTENANTS, $tenantComplete['row']['disableSearchInOtherTenants'], false, true);
                    try {
                        $setPropertyWithCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $tenantComplete['row']['enableStatussesSystem'], false, true);
                    } catch (Zend_Db_Table_Row_Exception $ex) {
                        $setPropertyWithCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $enableStatussesSystem, false, true);
                    }
                    $resourceManager->insert($tenantResource);
                };


                foreach ($setsToInsert as $set) {
                    $setResource = new \OpenSkos2\Set($set['uri']);
                    $setResource->setProperty(OpenSkos::UUID, new \OpenSkos2\Rdf\Literal($set['uuid']));
                    $setPropertyWithCheck($setResource, OpenSkos::CODE, $set['row']['code'], false);
                    $tenants = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, "'" . $set['row']['tenant'] . "'", Org::FORMALORG);
                    if (count($tenants) < 1) {
                        throw new Exception("Something went terribly worng: the tenat with the code " . $set['row']['tenant'] . " has not been inserted in the triple store before now.");
                    };
                    $publisherURI = $tenants[0];
                    $setPropertyWithCheck($setResource, DcTerms::PUBLISHER, $publisherURI, true);
                    $setPropertyWithCheck($setResource, DcTerms::TITLE, $set['row']['dc_title'], false);
                    $setPropertyWithCheck($setResource, DcTerms::DESCRIPTION, $set['row']['dc_description'], false);
                    $setPropertyWithCheck($setResource, OpenSkos::WEBPAGE, $set['row']['website'], true);
                    $setPropertyWithCheck($setResource, DcTerms::LICENSE, $set['row']['license_url'], true);
                    $setPropertyWithCheck($setResource, OpenSkos::OAI_BASEURL, $set['row']['OAI_baseURL'], true);
                    $setPropertyWithCheck($setResource, OpenSkos::ALLOW_OAI, $set['row']['allow_oai'], false, true);
                    $setPropertyWithCheck($setResource, OpenSkos::CONCEPTBASEURI, $set['row']['conceptsBaseUrl'], true);
                    $resourceManager->insert($setResource);
                }


                echo "done!";
                