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
include dirname(__FILE__) . '/autoload.inc.php';

$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/bootstrap.inc.php';

// Test....

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');

$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$tenant = 'rce';

$endPoint = $OPTS->endpoint . "?q=tenant%3A$tenant&rows=100&wt=json";
$init = json_decode(file_get_contents($endPoint), true);
$total = $init['response']['numFound'];

$counter = 0;


$getFieldsInClass = function ($class) {
    $return = '';
    foreach (\OpenSkos2\Concept::$classes[$class] as $field) {
        $return [str_replace('http://www.w3.org/2004/02/skos/core#', '', $field)] = $field;
    }
    return $return;
};

$labelMapping = array_merge($getFieldsInClass('LexicalLabels'), $getFieldsInClass('DocumentationProperties'));

$users = [];
$userModel = new OpenSKOS_Db_Table_Users();
$mappings = [
    'users' => [
        'callback' => function ($value) use ($userModel, &$users, $tenant) {
            if (!$value) {
                return null;
            }

            if (!isset($users [$value])) {
                /**
                 * @var $user OpenSKOS_Db_Table_Row_User
                 */
                if (is_numeric($value)) {
                    $user = $userModel->fetchRow('id = ' . $userModel->getAdapter()->quote($value) . ' AND tenant = ' . $userModel->getAdapter()->quote($tenant));
                } else {
                    $user = $userModel->fetchRow('name = ' . $userModel->getAdapter()->quote($value) . ' AND tenant = ' . $userModel->getAdapter()->quote($tenant));
                }
                if (!$user) {
                    echo "Could not find user with id: {$value}\n";
                    $users [$value] = null;
                } else {
                    $users [$value] = $user->getFoafPerson();
                }
            }
            return $users [$value];
        },
        'fields' => [
            'modified_by' => \OpenSkos2\Concept::PROPERTY_DCTERMS_CONTRIBUTOR,
            'created_by' => \OpenSkos2\Concept::PROPERTY_DCTERMS_CREATOR,
            'dcterms_creator' => \OpenSkos2\Concept::PROPERTY_DCTERMS_CREATOR,
            'approved_by' => \OpenSkos2\Concept::PROPERTY_OPENSKOS_ACCEPTEDBY,
            'deleted_by' => \OpenSkos2\Concept::PROPERTY_OPENSKOS_DELETEDBY,
        ],
    ],
    'uris' => [
        'callback' => function ($value) {
            return new \OpenSkos2\Rdf\Uri($value);
        },
        'fields' => array_merge(
            $getFieldsInClass('SemanticRelations'),
            $getFieldsInClass('MappingProperties'),
            $getFieldsInClass('ConceptSchemes')
        ),
    ],
    'literals' => [
        'callback' => function ($value) {
            return new \OpenSkos2\Rdf\Literal($value);
        },
        'fields' => [
            'status' => \OpenSkos2\Concept::PROPERTY_OPENSKOS_STATUS,
            'notation' => \OpenSkos2\Concept::PROPERTY_NOTATION,
        ]
    ],
    'dates' => [
        'callback' => function ($value) {
            return new \OpenSkos2\Rdf\Literal($value, null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        },
        'fields' => [
            'approved_timestamp' => \OpenSkos2\Concept::PROPERTY_DCTERMS_DATE_ACCEPTED,
            'created_timestamp' => \OpenSkos2\Concept::PROPERTY_DCTERMS_DATESUBMITTED,
            'modified_timestamp' => \OpenSkos2\Concept::PROPERTY_DCTERMS_MODIFIED,
            'dcterms_dateSubmitted' => \OpenSkos2\Concept::PROPERTY_DCTERMS_DATESUBMITTED,
            'dcterms_modified'  => \OpenSkos2\Concept::PROPERTY_DCTERMS_MODIFIED,
            'dcterms_dateAccepted'  => \OpenSkos2\Concept::PROPERTY_DCTERMS_DATE_ACCEPTED,
            'deleted_timestamp'  => \OpenSkos2\Concept::PROPERTY_OPENSKOS_DATE_DELETED,
        ]
    ],
    'bool' => [
        'callback' => function ($value) {
            if ($value) {
                return new \OpenSkos2\Rdf\Literal(true, null, \OpenSkos2\Rdf\Literal::TYPE_BOOL);
            }
        },
        'fields' => [
            'toBeChecked' => \OpenSkos2\Concept::PROPERTY_OPENSKOS_TOBECHECKED
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
        ]
    ]
];

var_dump($total);
do {
    $logger->info("fetching " . $endPoint . "&start=$counter");
    $data = json_decode(file_get_contents($endPoint . "&start=$counter"), true);
    foreach ($data['response']['docs'] as $doc) {
        $counter++;

        switch ($doc['class']) {
            case 'ConceptScheme':
                $resource = new \OpenSkos2\Schema($doc['uri']);
                break;
            case 'Concept':
                $resource = new \OpenSkos2\Concept($doc['uri']);
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

            switch ($field) {


                case 'tenant':
                case 'collection':
                case 'uuid':

                case 'deleted':
                case 'statusOtherConcept':

                    //todo
                    break;

                default:
                    var_dump($doc);
                    throw new Exception("What to do with field {$field}");
            }
        }

        $resourceManager->insert($resource);

    }
} while ($counter < $total && isset($data['response']['docs']));


echo "done!";


