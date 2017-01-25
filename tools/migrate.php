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
 * Run the file as : php tools/migrate.php --endpoint http://<host>:<port>/path/core/select --tenant=<code>
 */
require dirname(__FILE__) . '/autoload.inc.php';

use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;

$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'db-hostname=s' => 'Origin database host',
    'db-database=s' => 'Origin database name',
    'db-username=s' => 'Origin database username',
    'db-password=s' => 'Origin database password',
    'tenant=s' => 'Tenant to migrate',
    'start|s=s' => 'Start from that record',
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

validateOptions($OPTS);

$dbSource = \Zend_Db::factory('Pdo_Mysql', array(
    'host'      => $OPTS->getOption('db-hostname'),
    'dbname'    => $OPTS->getOption('db-database'),
    'username'  => $OPTS->getOption('db-username'),
    'password'  => $OPTS->getOption('db-password'),
));
$dbSource->setFetchMode(\PDO::FETCH_OBJ);
$collectionCache = new Collections($dbSource);
$collectionCache->validateCollections();

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$resourceManager->setIsNoCommitMode(true);

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('debug')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
    $logLevel
));

$tenant = $OPTS->tenant;
$isDryRun = $OPTS->getOption('dryrun');

$query = [
    'q' => 'tenant:"'.$tenant.'"',
    'rows' => 100,
    'wt' => 'json',
];

$endPoint = $OPTS->endpoint . "?" . http_build_query($query);

$init = getFileContents($endPoint);
$total = $init['response']['numFound'];
$validator = new \OpenSkos2\Validator\Resource($resourceManager, new \OpenSkos2\Tenant($tenant), $logger);

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
            $tenant,
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
                        $userModel,
                        'id = ' . $userModel->getAdapter()->quote($value) . ' '
                        . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
                    );
                } else {
                    $user = $fetchRowWithRetries(
                        $userModel,
                        'name = ' . $userModel->getAdapter()->quote($value) . ' '
                        . 'AND tenant = ' . $userModel->getAdapter()->quote($tenant)
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
            $tenant,
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
                    $collectionModel,
                    'id = ' . $collectionModel->getAdapter()->quote($value)
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
            $getFieldsInClass('SemanticRelations'),
            $getFieldsInClass('MappingProperties'),
            $getFieldsInClass('ConceptSchemes'),
            [
                'member' => Skos::MEMBER, // for collections ?!?
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
            'dcterms_modified'  => DcTerms::MODIFIED,
            'dcterms_dateAccepted'  => DcTerms::DATEACCEPTED,
            'deleted_timestamp'  => OpenSkos::DATE_DELETED,
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
        ]
    ]
];

$logger->info('Found ' . $total . ' records');
do {
    $logger->debug("fetching " . $endPoint . "&start=$counter");

    if ($counter % 5000 == 0) {
        $logger->info('Processed so far: ' . $counter);
    }

    $data = getFileContents($endPoint . "&start=$counter");
    foreach ($data['response']['docs'] as $doc) {
        $counter++;

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

                // Make sure we have a valid uri in all caes.
                $uri = getConceptUri($uri, $doc, $collectionCache);

                $resource = new \OpenSkos2\Concept($uri);
                break;
            case 'Collection':
                $resource = new \OpenSkos2\Collection($uri);
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
                    foreach ((array)$value as $v) {
                        $resource->addProperty($labelMapping[$field], new \OpenSkos2\Rdf\Literal($v, $lang));
                    }
                    continue;
                }

            }

            foreach ($mappings as $mapping) {
                if (isset($mapping['fields'][$field])) {
                    foreach ((array)$value as $v) {
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
        $resource->addProperty(OpenSkos2\Namespaces\OpenSkos::TENANT, new OpenSkos2\Rdf\Literal($tenant));

        // Set status deleted
        if (!empty($doc['deleted'])) {
            $resource->setProperty(OpenSkos::STATUS, new OpenSkos2\Rdf\Literal(\OpenSkos2\Concept::STATUS_DELETED));
        }

        // Validate (only if not deleted, all deleted resources are considered valid.
        if ($resource->isDeleted()) {
            $isValid = true;
        } else {
            $isValid = validateResource($validator, $resource);
        }

        // Insert
        if ($isValid && !$isDryRun) {
            insertResource($resourceManager, $resource);
        }
    }
} while ($counter < $total && isset($data['response']['docs']));

$logger->info("Done!");

function validateResource(\OpenSkos2\Validator\Resource $validator, OpenSkos2\Rdf\Resource $resource, $retry = 20) {

    $tried = 0;

    do {

        try {
            return $validator->validate($resource);
        } catch (\Exception $exc) {

            echo 'failed validating retry' . PHP_EOL;

            $tried++;
            sleep(5);
        }

    } while($tried < $retry);

    throw $exc;
}

function insertResource(\OpenSkos2\Rdf\ResourceManager $resourceManager, \OpenSkos2\Rdf\Resource $resource, $retry = 20) {

    $tried = 0;

    filterLastModifiedDate($resource);

    do {

        try {

            return $resourceManager->insert($resource);

        } catch (\Exception $exc) {

            echo 'failed inserting retry' . PHP_EOL;

            $tried++;
            sleep(5);
        }

    } while($tried < $retry);

//    throw $exc;
    echo PHP_EOL;
    echo 'failed inserting ' . $retry . ' times ' . PHP_EOL;
    echo 'last exception is ' . print_r($exc, true) . PHP_EOL;
    echo PHP_EOL;
}

/**
 * Filter multiple modified dates to the last modified date.
 *
 * @param \OpenSkos2\Rdf\Resource $resource
 */
function filterLastModifiedDate(\OpenSkos2\Rdf\Resource $resource) {

    $dates = $resource->getProperty(DcTerms::MODIFIED);

    if (count($dates) < 2) {
        return;
    }

    $lastDate = new \DateTime($dates[0]->getValue());
    foreach ($dates as $date) {
        $otherDate = new \DateTime($date->getValue());
        if ($lastDate->getTimestamp() < $otherDate->getTimestamp()) {
            $lastDate = $otherDate;
        }
    }

    $newDate = new \OpenSkos2\Rdf\Literal(
        $lastDate->format("Y-m-d\TH:i:s.z\Z"),
        null,
        \OpenSkos2\Rdf\Literal::TYPE_DATETIME
    );

    $resource->setProperty(DcTerms::MODIFIED, $newDate);
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
function getFileContents($url, $retry = 20, $count = 0) {

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
 * Make sure all cli options are given
 *
 * @param \Zend_Console_Getopt $opts
 */
function validateOptions(\Zend_Console_Getopt $opts) {

    $required = [
        'db-hostname',
        'db-database',
        'db-username',
        'db-password',
    ];

    foreach ($required as $req) {
        $reqOption = $opts->getOption($req);
        if (empty($reqOption)) {
            echo $opts->getUsageMessage();
            exit();
        }
    }
}

/**
 * Used to generate uri's for bad data from the source
 * where the uri only had a notation
 *
 * @param string $uri
 * @param array $solrDoc
 * @return string
 */
function getConceptUri($uri, array $solrDoc, \Collections $collections) {

    if (filter_var($uri, FILTER_VALIDATE_URL, $uri)) {
        return $uri;
    }

    $collection = $collections->fetchById($solrDoc['collection']);
    if (count($solrDoc['notation']) !== 1) {
        throw new \RuntimeException('More then one notation: ' . var_export($solrDoc[['notation']]));
    }

    return $collection->conceptsBaseUrl . '/' . current($solrDoc['notation']);
}

class Collections {

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * @var array
     */
    private $collections = [];

    /**
     * Use the source db as parameter not the target.
     *
     * @param \Zend_Db_Adapter_Abstract $db
     */
    public function __construct(\Zend_Db_Adapter_Abstract $db) {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function fetchById($id)
    {

        if (!isset($this->fetchAll()[$id])) {
            throw new \RunTimeException('Collection not found');
        }

        return $this->fetchAll()[$id];
    }

    /**
     * Fetch all collections
     *
     * @return array
     */
    public function fetchAll()
    {
        if (!empty($this->collections)) {
            return $this->collections;
        }

        $collections = $this->db->fetchAll('select * from collection');
        foreach ($collections as $collection) {
            $this->collections[$collection->id] = $collection;
        }

        return $this->collections;
    }

    /**
     * Check if
     *
     * @throw \RuntimeException
     */
    public function validateCollections()
    {
        foreach ($this->fetchAll() as $row) {
            if (!filter_var($row->conceptsBaseUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Could not validate url for collection: ' . var_export($row, true));
            }
        }
    }
}