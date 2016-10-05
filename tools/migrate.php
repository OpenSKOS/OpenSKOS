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
 * Run for every tenant seperately. It is assumed that each tenant before migrating has only one set aka tenant collection (you are free add more sets to tenants after migration).
 *  */
require dirname(__FILE__) . '/autoload.inc.php';
require_once 'Logging.php';

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\Rdf;
use Rhumsaa\Uuid\Uuid;
use OpenSkos2\Validator\Resource as ResourceValidator;

/**
 * Script to migrate the data from SOLR to Jena run as following: 
 * Run the file as : php tools/migrate.php --endpoint http://<host>:<port>/path/core/select --tenant=<code> --enablestatusses=<bool>
 * Run for every tenant seperately. It is assumed that each tenant before migrating has only one set aka tenant collection (you are free add more sets to tenants after migration).
 *  */
// example :php migrate.php --endpoint=http://192.168.99.100:8984/solr/collection1/select --tenant=meertens --enablestatusses=true --language=en --license=http://creativecommons.org/licenses/by/4.0/ --dryrun=false --debug=false
$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'tenant=s' => 'Tenant to migrate',
    'start|s=s' => 'Start from that record',
    'enablestatusses=s' => 'true/false, enables/disables statusses',
    'language=s' => 'Default language',
    'license=s' => 'Default license url',
    'dryrun' => 'Only validate the data, do not migrate it.',
    'debug' => 'Show debug info.',
];

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
$resourceManager->setIsNoCommitMode(false);

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('debug')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
        \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, $logLevel
));

$tenant = $OPTS->tenant;
var_dump('tenant: ' . $tenant);
$isDryRun = $OPTS->dryrun;
var_dump('dry run : ' . $isDryRun);
$endPoint = $OPTS->endpoint . "?q=tenant%3A$tenant&rows=100&wt=json";
var_dump($endPoint);

$enableStatussesSystem = $OPTS->enablestatusses;
$language = $OPTS->language;
var_dump('language: ' . $language);
$license = $OPTS->license;
var_dump('license: ' . $license);

$init = json_decode(file_get_contents($endPoint), true);
$total = $init['response']['numFound'];

echo "Cleaning round, used when migrate script runs a few times with the same data, all removes concept schemata, collections and concepts, # documents to process: ";
function remove_resources($manager, $resources, $rdfType) {
    foreach ($resources as $resource) {
        $manager->delete($resource, $rdfType);
        $manager -> deleteReferencesToObject($resource);
    }
}

$concURIs=$resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::CONCEPT . '>', null);
$concs = $resourceManager->fetchByUris($concURIs, Skos::CONCEPT);
remove_resources($resourceManager, $concs, Skos::CONCEPT);

$collURIs=$resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::SKOSCOLLECTION . '>', null);
$colls = $resourceManager->fetchByUris($collURIs, Skos::SKOSCOLLECTION);
remove_resources($resourceManager, $colls, Skos::SKOSCOLLECTION);

$schemURIs=$resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::CONCEPTSCHEME . '>', null);
$schems = $resourceManager->fetchByUris($schemURIs, Skos::CONCEPTSCHEME);
remove_resources($resourceManager, $schems, Skos::CONCEPTSCHEME);

$setURIs=$resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Dcmi::DATASET . '>', null);
$sets = $resourceManager->fetchByUris($setURIs, Dcmi::DATASET);
remove_resources($resourceManager, $sets, Dcmi::DATASET);

$instURIs=$resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' .  Org::FORMALORG. '>', null);
$insts = $resourceManager->fetchByUris($instURIs, Org::FORMALORG);
remove_resources($resourceManager, $insts, Org::FORMALORG);

$old_time = time();


$getFieldsInClass = function ($class) {
    $retVal = '';
    foreach (\OpenSkos2\Rdf\Resource::$classes[$class] as $field) {
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

$labelMapping = array_merge($getFieldsInClass('LexicalLabels'), $getFieldsInClass('DocumentationProperties'), ['title'=>DcTerms::TITLE, 'dc_title'=>DcTerms::TITLE, 'dcterms_title'=>DcTerms::TITLE]);
$notFoundUsers = [];
$notFoundCollections = [];
$collections = [];
$userModel = new OpenSKOS_Db_Table_Users();
$collectionModel = new OpenSKOS_Db_Table_Collections();
$tenantModel = new OpenSKOS_Db_Table_Tenants();

$adapter = $userModel->getAdapter();
$cols = $userModel->info('cols');
if (!in_array('uri', $cols)) {
    $adapter->getConnection()->exec('ALTER TABLE user ADD uri VARCHAR(256)');
    $adapter->closeConnection();
}

$fetchRowWithRetries = function ($resourceManager, $model, $query) {
    return $resourceManager->fetchRowWithRetries($model, $query);
};

function set_property_with_check(&$resource, $property, $val, $isURI = false, $isBOOL = false, $lang = null) {
    if ($isURI) {
        if (isset($val)) {
            if (trim($val) !== '') {
                $resource->setProperty($property, new \OpenSkos2\Rdf\Uri($val));
            }
        }
        return;
    };

    if ($isBOOL) {
        if (isset($val)) {
            if (strtolower(strtolower($val)) === 'y' || strtolower($val) === "yes") {
                $val = 'true';
            }
            if (strtolower(strtolower($val)) === 'n' || strtolower($val) === "no") {
                $val = 'false';
            }
            $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val, null, \OpenSkos2\Rdf\Literal::TYPE_BOOL));
        } else {
            // default value is 'false'
            $resource->setProperty($property, new \OpenSkos2\Rdf\Literal('false', null, \OpenSkos2\Rdf\Literal::TYPE_BOOL));
        }
        return;
    }

    // the property must be a literal
    if ($lang == null) {
        $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val));
    } else {
        $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val, $lang));
    };
}

;

function insert_tenant($code, $tenantMySQL, $resourceManager, $enableStatussesSystem) {
    $tenantResource = new \OpenSkos2\Tenant();
    $uri = $tenantResource->selfGenerateUuidAndUriWhenAbsent($resourceManager, ['type' => Org::FORMALORG, 'tenantcode' => $code]);
    set_property_with_check($tenantResource, OpenSkos::CODE, $code);
    $organisation = new \OpenSkos2\Rdf\Resource(URI_PREFIX . '/node-org-' . Uuid::uuid4());
    set_property_with_check($organisation, vCard::ORGNAME, $tenantMySQL['name']);
    set_property_with_check($organisation, vCard::ORGUNIT, $tenantMySQL['organisationUnit']);
    $tenantResource->setProperty(vCard::ORG, $organisation);
    set_property_with_check($tenantResource, OpenSkos::WEBPAGE, $tenantMySQL['website'], true);
    set_property_with_check($tenantResource, vCard::EMAIL, $tenantMySQL['email']);
    $adress = new \OpenSkos2\Rdf\Resource(URI_PREFIX . 'node-adr-' . Uuid::uuid4());
    set_property_with_check($adress, vCard::STREET, $tenantMySQL['streetAddress']);
    set_property_with_check($adress, vCard::LOCALITY, $tenantMySQL['locality']);
    set_property_with_check($adress, vCard::PCODE, $tenantMySQL['postalCode']);
    set_property_with_check($adress, vCard::COUNTRY, $tenantMySQL['countryName']);
    $tenantResource->setProperty(vCard::ADR, $adress);
    set_property_with_check($tenantResource, OpenSkos::DISABLESEARCHINOTERTENANTS, $tenantMySQL['disableSearchInOtherTenants'], false, true);
    try {
        set_property_with_check($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $tenantMySQL['enableStatussesSystem'], false, true);
    } catch (Zend_Db_Table_Row_Exception $ex) {
        set_property_with_check($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $enableStatussesSystem, false, true);
    }
    $resourceManager->insert($tenantResource);
    return $uri;
}

;

function insert_set($code, $collectionMySQL, $resourceManager, $defaultLicense, $lang) {
    $setResource = new \OpenSkos2\Set();
    $uri = $setResource->selfGenerateUuidAndUriWhenAbsent($resourceManager, ['type' => Dcmi::DATASET, 'setcode' => $code]);
    set_property_with_check($setResource, OpenSkos::CODE, $code);

    $publisherURI = $resourceManager->fetchInstitutionUriByCode($collectionMySQL['tenant']);
    if ($publisherURI === null) {
        throw new Exception("Something went terribly worng: the tenant with the code " . $collectionMySQL['tenant'] . " has not been inserted in the triple store before now.");
    } else {
        var_dump("PublisherURI: " . $publisherURI . "\n");
    }
    set_property_with_check($setResource, DcTerms::PUBLISHER, $publisherURI, true);


    set_property_with_check($setResource, DcTerms::TITLE, $collectionMySQL['dc_title'], false, false, $lang);

    set_property_with_check($setResource, DcTerms::DESCRIPTION, $collectionMySQL['dc_description']);
    set_property_with_check($setResource, OpenSkos::WEBPAGE, $collectionMySQL['website'], true);

    $licenseURL = $defaultLicense;
    if (isset($collectionMySQL['license_url'])) {
        if (trim($collectionMySQL['license_url']) !== '') {
            $licenseURL = $collectionMySQL['license_url'];
        }
    }

    set_property_with_check($setResource, DcTerms::LICENSE, $licenseURL, true);

    set_property_with_check($setResource, OpenSkos::OAI_BASEURL, $collectionMySQL['OAI_baseURL'], true);
    set_property_with_check($setResource, OpenSkos::ALLOW_OAI, $collectionMySQL['allow_oai'], false, true);
    set_property_with_check($setResource, OpenSkos::CONCEPTBASEURI, $collectionMySQL['conceptsBaseUrl'], true);
    $resourceManager->insert($setResource);
    return $uri;
}

;

function fetch_tenant($code, $tenantModel, $fetchRowWithRetries, $resourceManager, $enableStatussesSystem) {
    if (!$code) {
        return null;
    }


    $tripleStoreTenant = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, "'" . $code . "'", Org::FORMALORG);
    if (count($tripleStoreTenant) < 1) { // this tenant is not yet in the triple store
        // look up MySQL
        /**
         * @var $tenant OpenSKOS_Db_Table_Row_Tenant
         */
        // name can be relaced with id
        $tenantMySQL = $fetchRowWithRetries($resourceManager, $tenantModel, 'code = ' . $tenantModel->getAdapter()->quote($code));

        if (!$tenantMySQL) {
            echo "Could not find tenant  with code: {$code}\n";
            return null;
        }

        $uri = insert_tenant($code, $tenantMySQL, $resourceManager, $enableStatussesSystem);
        var_dump("The institution's  (" . $code . ") handle/uri " . $uri . " is generated on the fly. ");
        return new \OpenSkos2\Rdf\Uri($uri);
    } else {
        return new \OpenSkos2\Rdf\Uri($tripleStoreTenant[0]);
    }
}

;

function fetch_set($id, $collectionModel, $fetchRowWithRetries, $resourceManager, $defaultLicense, $lang) {
    if (!$id) {
        return null;
    }

    /**
     * @var $collection OpenSKOS_Db_Table_Row_Collection
     */
    $collectionMySQL = $fetchRowWithRetries($resourceManager, $collectionModel, 'code = ' . $collectionModel->getAdapter()->quote($id)
    );

    if (!$collectionMySQL) {
        $collectionMySQL = $fetchRowWithRetries($resourceManager, $collectionModel, 'id = ' . $collectionModel->getAdapter()->quote($id)
        );
        if (!$collectionMySQL) {
            echo "Could not find a set (aka tenant collection) with id or code: {$id}\n";
            return null;
        } else {
            $code = $collectionMySQL['code'];
        }
    } else {
        $code = $id;
    }

    $tripleStoreSet = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, "'" . $code . "'", Dcmi::DATASET);
    if (count($tripleStoreSet) < 1) { // this set is not yet in the triple store
        $uri = insert_set($code, $collectionMySQL, $resourceManager, $defaultLicense, $lang);
        var_dump("The set's  (" . $code . ") handle/uri " . $uri . " is generated on the fly. ");
        return new \OpenSkos2\Rdf\Uri($uri);
    } else {
        return new \OpenSkos2\Rdf\Uri($tripleStoreSet[0]);
    }
}

;



$mappings = [
    'users' => [
        'callback' => function ($value) use ($userModel, &$users, &$notFoundUsers, $tenant, $fetchRowWithRetries, $resourceManager, $isDryRun) {
            if ($value === null || !$value || !isset($value)) {
                return new \OpenSkos2\Rdf\Literal("Unknown");
            }

            if (in_array($value, $notFoundUsers)) {
                return new \OpenSkos2\Rdf\Literal("Unknown");
            }

            if (!isset($users[$value])) {
                /**
                 * @var $user OpenSKOS_Db_Table_Row_User
                 */
                if (is_numeric($value)) {
                    $user = $fetchRowWithRetries($resourceManager, $userModel, 'id = ' . $userModel->getAdapter()->quote($value) . ' '
                            . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
                    );
                } else {
                    $user = $fetchRowWithRetries($resourceManager, $userModel, 'name = ' . $userModel->getAdapter()->quote($value) . ' '
                            . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
                    );
                }
                if (!$user) {
                    $logger->notice("Could not find user with id/name: {$value}");
                    $notFoundUsers[] = $value;
                    $users[$value] = new \OpenSkos2\Rdf\Literal("Unknown");
                } else {
                    $users[$value] = new \OpenSkos2\Rdf\Uri($user->getFoafPerson(!$isDryRun)->getUri());
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
        'callback' => function ($value) use ($collectionModel, $fetchRowWithRetries, $resourceManager, $license, $language) {
            $retVal = fetch_set($value, $collectionModel, $fetchRowWithRetries, $resourceManager, $license, $language);
            return $retVal;
        },
        'fields' => [
            'collection' => OpenSkos2\Namespaces\OpenSkos::SET,
        ],
    ],
    'tenant' => [
        'callback' => function ($value) use ($tenantModel, $fetchRowWithRetries, $resourceManager, $enableStatussesSystem) {
            $retVal = fetch_tenant($value, $tenantModel, $fetchRowWithRetries, $resourceManager, $enableStatussesSystem);
            return $retVal;
        },
        'fields' => [
            'tenant' => OpenSkos2\Namespaces\OpenSkos::TENANT
        ]
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

var_dump("Preprocessing round (MySql -- Triple Store) 1 : fetching institutions. # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
do {
    //$logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $counter++;
        try {
            if (array_key_exists('tenant', $doc)) {
                $value = $doc['tenant'];
                foreach ((array) $value as $v) {
                    $uri = fetch_tenant($v, $tenantModel, $fetchRowWithRetries, $resourceManager, $enableStatussesSystem);
                }
            }
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
        }
    }
} while ($counter < $total && isset($data['response']['docs']));


/// memorise tenant uri for the tenant from the command line
$tenantUris = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, '"' . $tenant . '"', Org::FORMALORG);
$tenantUri = $tenantUris[0];

var_dump('Tenant ' . $tenant . ' has been assigned an uri ' . $tenantUri);
var_dump("\n");

var_dump("Preprocessing round (MySql -- Triple Store) 2: turning collections into triple-store sets.  # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
do {
    //$logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $counter++;
        try {
            if (array_key_exists('collection', $doc)) {
                $value = $doc['collection'];
                foreach ((array) $value as $v) {
                    $uri = fetch_set($v, $collectionModel, $fetchRowWithRetries, $resourceManager, $license, $language);
                }
            }
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
        }
    }
} while ($counter < $total && isset($data['response']['docs']));




$synonym = ['approved_timestamp' => 'dcterms_dateAccepted', 'created_timestamp' => 'dcterms_dateSubmitted', 'modified_timestamp' => 'dcterms_modified'];

function run_round($doc, $resourceManager, $tenantUri, $class, $default_lang, $synonym, $labelMapping, $mappings, $logger) {
    if ($doc['class'] === $class) {
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

            // initialise synonym flags
            $isset_synonym = [];
            foreach ($synonym as $key => $value) {
                $isset_synonym[$key] = false;
            };
            $setLabels = [];
            foreach ($doc as $field => $value) {
                // do not insert tenant for all resources and set for concepts: they are derivable
                // just skip them 
                if (($field !== 'tenant') && !($class === 'Concept' && $field == 'collection')) {
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
            }

            // check if there are orfan (without language) labels and documentation properties
            foreach ($doc as $field => $value) {
                if (array_key_exists($field, $labelMapping)) {
                    foreach ((array) $value as $v) {
                        if (!array_key_exists($field, $setLabels)) {
                            $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v, $default_lang));
                            $setLabels[$field][] = $v;
                        } else {
                            if (!in_array($v, $setLabels[$field])) {
                                $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v, $default_lang));
                                $setLabels[$field][] = $v;
                            }
                        }
                    }
                }
            }

            // Set status deleted
            if (!empty($doc['deleted'])) {
                $resource->setProperty(OpenSkos::STATUS, new OpenSkos2\Rdf\Literal(\OpenSkos2\Resource::STATUS_DELETED));
                if ($doc['deleted'] === 'false') {
                    $resource->unsetProperty(OpenSkos::DELETEDBY); // otherwise it is set to unknown which is misleading
                }
            } else {
                $resource->unsetProperty(OpenSkos::DELETEDBY);
            }

            // this round of validation must skip reference controle (if the referred object exists or not)
            // for this the last parameter $referenceCheckOn in resource validator is set to false
            $validator = new ResourceValidator($resourceManager, false, $tenantUri, false);
            $valid = $validator->validate($resource);
            if ($valid) {
                $resourceManager->insert($resource);
            } else {
                foreach ($validator->getErrorMessages() as $errorMessage) {
                    var_dump($errorMessage);
                    \Tools\Logging::var_logger("The followig resource has not been added due to the validation error ". $errorMessage, $resource->getUri(), ERROR_LOG);
               
                }
                $resourceManager->delete($resource); //remove garbage - 1
                $resourceManager->deleteReferencesToObject($resource); //remove garbage - 2
                var_dump($resource->getUri() . " cannot not been inserted due to the validation error(s) above.");
            }


            return 1;
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
            //var_dump($ex->getTraceAsString());
            var_dump("And the following document has not been added: ");
            var_dump($doc['uri']);
        }
    } else {
        return 0;
    }
}


var_dump("run Set (aka tenant collection) round, # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
$added = 0;
do {
    //$logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $check = run_round($doc, $resourceManager, $tenantUri, 'Collection', $language, $synonym, $labelMapping, $mappings, $logger);
        $added = $added + $check;
        $counter++;
    }
} while ($counter < $total && isset($data['response']['docs']));
var_dump('Sets (aka tenant collections) added: ');
var_dump($added);


var_dump('\n');
var_dump("run ConceptScheme round, # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
$added = 0;
do {
    //$logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $check = run_round($doc, $resourceManager, $tenantUri, 'ConceptScheme', $language, $synonym, $labelMapping, $mappings, $logger);
        $added = $added + $check;
        $counter++;
    }
} while ($counter < $total && isset($data['response']['docs']));
var_dump('ConceptSchemes added: ');
var_dump($added);


var_dump("run SkosCollection round, # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
$added = 0;
do {
    //$logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $check = run_round($doc, $resourceManager, $tenantUri, 'SKOSCollection', $language, $synonym, $labelMapping, $mappings, $logger);
        $added = $added + $check;
        $counter++;
    }
} while ($counter < $total && isset($data['response']['docs']));
var_dump('SkosCollections added: ');
var_dump($added);

var_dump("run Concept round, # documents to process: ");
var_dump($total);
if (!empty($OPTS->start)) {
    $counter = $OPTS->start;
} else {
    $counter = 0;
}
$added = 0;
do {
    $logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $check = run_round($doc, $resourceManager, $tenantUri, 'Concept', $language, $synonym, $labelMapping, $mappings, $logger);
        $added = $added + $check;
        $counter++;
    }
} while ($counter < $total && isset($data['response']['docs']));
var_dump('Concepts added: ');
var_dump($added);
$elapsed = time()-$old_time;
echo "\n time elapsed since start of migration (sec): ". $elapsed . "\n";
$old_time = time();


$logger->info("Done!");
