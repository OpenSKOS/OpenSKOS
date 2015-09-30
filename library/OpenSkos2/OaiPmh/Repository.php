<?php

/*
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
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\OaiPmh;

use Asparagus\QueryBuilder;
use DateTime;
use DOMDocument;
use OpenSkos2\Concept;
use OpenSkos2\ConceptManager;
use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Xsd;
use OpenSkos2\OaiPmh\Concept as OaiConcept;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSKOS_Db_Table_Row_Collection;
use Picturae\OaiPmh\Exception\IdDoesNotExistException;
use Picturae\OaiPmh\Implementation\MetadataFormatType as ImplementationMetadataFormatType;
use Picturae\OaiPmh\Implementation\RecordList as OaiRecordList;
use Picturae\OaiPmh\Implementation\Repository\Identity as ImplementationIdentity;
use Picturae\OaiPmh\Implementation\Set;
use Picturae\OaiPmh\Implementation\SetList;
use Picturae\OaiPmh\Interfaces\MetadataFormatType;
use Picturae\OaiPmh\Interfaces\Record;
use Picturae\OaiPmh\Interfaces\RecordList;
use Picturae\OaiPmh\Interfaces\Repository as InterfaceRepository;
use Picturae\OaiPmh\Interfaces\Repository\Identity;
use Picturae\OaiPmh\Interfaces\SetList as InterfaceSetList;
use Zend_Db_Adapter_Abstract;

class Repository implements InterfaceRepository
{

    /**
     * Amount of records to be displayed
     *
     * @var int
     */
    private $limit = 100;

    /**
     * OAI-PMH Repository name
     * @var string
     */
    private $repositoryName;

    /**
     * Base url for OAI-PMH
     * @var string
     */
    private $baseUrl;

    /**
     * Admin emails
     *
     * @var string[]
     */
    private $adminEmails = [];

    /**
     * Optional description for the repository
     *
     * @var string|null
     */
    private $description;

    /**
     * a datetime that is the guaranteed lower limit of all datestamps recording changes,modifications, or deletions
     * in the repository. A repository must not use datestamps lower than the one specified
     * by the content of the earliestDatestamp element. earliestDatestamp must be expressed at the finest granularity
     * supported by the repository.
     *
     * @return DateTime
     */
    private $earliestDateStamp;

    /**
     * RDF Resource manager
     *
     * @var ConceptManager
     */
    private $conceptManager;

    /**
     * Current offset retrieved from token
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Database adapter
     *
     * @var Zend_Db_Adapter_Abstract $db,
     */
    private $db;

    /**
     *
     * @var ConceptSchemeManager
     */
    private $schemeManager;

    public function __construct(
        ConceptManager $conceptManager,
        ConceptSchemeManager $schemeManager,
        $repositoryName,
        $baseUrl,
        array $adminEmails,
        Zend_Db_Adapter_Abstract $db,
        $description = null
    ) {
        $this->conceptManager = $conceptManager;
        $this->schemeManager = $schemeManager;
        $this->repositoryName = $repositoryName;
        $this->baseUrl = $baseUrl;
        $this->adminEmails = $adminEmails;
        $this->description = $description;
        $this->db = $db;
    }

    /**
     * @return Identity
     */
    public function identify()
    {
        return new ImplementationIdentity(
            $this->repositoryName,
            $this->baseUrl,
            $this->getEarliestDateStamp(),
            Identity::DELETED_RECORD_NO,
            $this->adminEmails,
            ImplementationIdentity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ,
            null,
            null
        );
    }

    /**
     * @return InterfaceSetList
     */
    public function listSets()
    {
        $collections = $this->getCollections();

        $items = [];

        $tenantAdded = [];

        foreach ($collections as $row) {
            // Tenant spec
            $tenantCode = $row['tenant_code'];
            if (!isset($tenantAdded[$tenantCode])) {
                $items[] = new Set($tenantCode, $row['tenant_title']);
                $tenantAdded[$tenantCode] = $tenantCode;
            }

            // Collection spec
            $spec = $row['tenant_code'] . ':' . $row['collection_code'];
            $items[] = new Set($spec, $row['collection_title']);

            // Concept scheme spec
            $schemes = $this->schemeManager->getSchemeByCollectionUri($row['collection_uri']);
            foreach ($schemes as $scheme) {
                $uuidProp = $scheme->getProperty(OpenSkos::UUID);
                $uuid = $uuidProp[0]->getValue();
                $schemeSpec = $spec . ':' . $uuid;

                $title = $scheme->getProperty(DcTerms::TITLE);
                $name = $title[0]->getValue();

                $items[] = new Set($schemeSpec, $name);
            }
        }

        return new SetList($items);
    }

    /**
     * @param string $token
     * @return InterfaceSetList
     */
    public function listSetsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);
        return $this->listSets();
    }

    /**
     * @param string $metadataFormat
     * @param string $identifier
     * @return Record
     */
    public function getRecord($metadataFormat, $identifier)
    {
        try {
            $concept = $this->conceptManager->fetchByUri($identifier);
        } catch (ResourceNotFoundException $exc) {
            throw new IdDoesNotExistException('No matching identifier ' . $identifier, $exc->getCode(), $exc);
        }
        
        return new OaiConcept($concept);
    }

    /**
     * @param string $metadataFormat metadata format of the records to be fetch or null if only headers are fetched
     * (listIdentifiers)
     * @param DateTime $from
     * @param DateTime $until
     * @param string $set name of the set containing this record
     * @return RecordList
     */
    public function listRecords($metadataFormat = null, DateTime $from = null, DateTime $until = null, $set = null)
    {
        
        $pSet = $this->parseSet($set);
        
        $concepts = $this->getConcepts(
            $this->limit + 1,
            0,
            $from,
            $until,
            $pSet['tenant'],
            $pSet['collection'],
            $pSet['conceptScheme']
        );
        
        $items = [];
        
        $showToken = false;
        foreach ($concepts as $i => $concept) {
            if ($i === $this->limit) {
                $showToken = true;
                continue;
            }
            $items[] = new OaiConcept($concept);
        }
        
        $token = null;
        if ($showToken) {
            $token = $this->encodeResumptionToken($this->limit, $from, $until, $metadataFormat, $set);
        }
        
        return new OaiRecordList($items, $token);
    }

    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);

        $concepts = $this->getConcepts(
            $this->limit + 1,
            $params['offset'],
            $params['from'],
            $params['until']
        );
        
        $items = [];
        
        $showToken = false;
        foreach ($concepts as $i => $concept) {
            if ($i === $this->limit) {
                $showToken = true;
                continue;
            }
            
            $items[] = new OaiConcept($concept);
        }

        $params['offset'] = (int)$params['offset'] + $this->limit;
        
        $token = null;
        
        if ($showToken) {
            $token = $this->encodeResumptionToken(
                $params['offset'],
                $params['from'],
                $params['until'],
                $params['metadataPrefix'],
                $params['set']
            );
        }

        return new OaiRecordList($items, $token);
    }

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats($identifier = null)
    {
        $formats = [];
        $formats[] = new ImplementationMetadataFormatType(
            'oai_dc',
            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'http://www.openarchives.org/OAI/2.0/oai_dc/'
        );

        $formats[] = new ImplementationMetadataFormatType(
            'oai_rdf',
            'http://www.openarchives.org/OAI/2.0/rdf.xsd',
            'http://www.w3.org/2004/02/skos/core#'
        );

        return $formats;
    }
    
    /**
     * Parse set string
     *
     *           (optional)       (optional)
     * <tenant>:<collection>:<concept-scheme-uuid>
     *
     * Returns an array with the following keys
     *
     * [
     *   'tenant',
     *   'collection',
     *   'conceptScheme',
     * ]
     *
     * @param string $set
     * @return array
     */
    private function parseSet($set)
    {
        $arrSet = explode(':', $set);
        
        $return = [];
        
        $tenant = null;
        if (!empty($arrSet[0])) {
            $tenant = new Literal($arrSet[0]);
        }
        $return['tenant'] = $tenant;
        
        $collection = null;
        if (!empty($arrSet[2])) {
            $collection = new Uri(OpenSkos::COLLECTION_BASE. $arrSet[0] . ':'. $arrSet[1]);
        }
        $return['collection'] = $collection;
        
        $conceptScheme = null;
        if (!empty($arrSet[2])) {
            $conceptScheme = new Literal($arrSet[2]);
        }
        $return['conceptScheme'] = $conceptScheme;
        return $return;
    }

    /**
     * Get all collections
     *
     * @return OpenSKOS_Db_Table_Row_Collection[]
     */
    private function getCollections()
    {
        $sql = $this->db->select()
                ->from(['col' => 'collection'], [
                    'collection_code' => 'col.code',
                    'collection_title' => 'col.dc_title',
                    'collection_description' => 'col.dc_description',
                    'collection_uri' => 'col.uri',
                ])
                ->join(
                    ['ten' => 'tenant'],
                    'col.tenant = ten.code',
                    [
                    'tenant_title' => 'ten.name',
                    'tenant_code' => 'ten.code',
                        ]
                )
                ->order('col.tenant ASC');

        return $this->db->fetchAll($sql);
    }

    /**
     * Decode resumption token
     * possible properties are:
     *
     * ->offset
     * ->metadataPrefix
     * ->set
     * ->from (timestamp)
     * ->until (timestamp)
     *
     * @param string $token
     * @return array
     */
    private function decodeResumptionToken($token)
    {
        $params = (array) json_decode(base64_decode($token));

        if (!empty($params['from'])) {
            $params['from'] = new \DateTime('@' . $params['from']);
        }

        if (!empty($params['until'])) {
            $params['until'] = new \DateTime('@' . $params['until']);
        }

        return $params;
    }

    /**
     * Get resumption token
     *
     * @param int $offset
     * @param DateTime $from
     * @param DateTime $util
     * @param string $metadataPrefix
     * @param string $set
     * @return string
     */
    private function encodeResumptionToken(
        $offset = 0,
        DateTime $from = null,
        DateTime $util = null,
        $metadataPrefix = null,
        $set = null
    ) {
        $params = [];
        $params['offset'] = $offset;
        $params['metadataPrefix'] = $metadataPrefix;
        $params['set'] = $set;
        $params['from'] = null;
        $params['until'] = null;

        if ($from) {
            $params['from'] = $from->getTimestamp();
        }

        if ($util) {
            $params['until'] = $util->getTimestamp();
        }

        return base64_encode(json_encode($params));
    }

    /**
     * Get earliest modified timestamp
     *
     * @return DateTime
     */
    private function getEarliestDateStamp()
    {
        if ($this->earliestDateStamp) {
            return $this->earliestDateStamp;
        }

        $query = 'PREFIX dcterms: <http://purl.org/dc/terms/>
            SELECT ?date
                WHERE {
                    ?subject dcterms:modified ?date
                }
                ORDER BY ASC(?date)
                LIMIT 1
            ';

        $graph = $this->conceptManager->query($query);
        return $graph[0]->date->getValue();
    }
    
    /**
     * Fetch all concepts based on parameters in the token
     *
     * @param int $limit
     * @param int $offset
     * @param \DateTime $from
     * @param \DateTime $till
     * @return Concept[]
     */
    private function getConcepts(
        $limit = 10,
        $offset = 0,
        \DateTime $from = null,
        \DateTime $till = null,
        $tenant = null,
        $collection = null,
        $scheme = null
    ) {
        $prefixes = [
            'rdf' => Rdf::NAME_SPACE,
            'skos' => Skos::NAME_SPACE,
            'dc' => Dc::NAME_SPACE,
            'dct' => DcTerms::NAME_SPACE,
            'openskos' => OpenSkos::NAME_SPACE,
            'xsd' => Xsd::NAME_SPACE
        ];

        $qb = new QueryBuilder($prefixes);
        $select = $qb->describe('?subject')
                ->where('?subject', 'rdf:type', 'skos:Concept')
                ->also('dct:modified', '?modified')
                ->limit($limit)
                ->offset($offset);
        
        if (!empty($tenant)) {
            $tenantN = NTriple::getInstance()->serialize($tenant);
            $select->also('openskos:tenant', $tenantN);
        }

        if (!empty($collection)) {
            $collectionN = NTriple::getInstance()->serialize($collection);
            $select->also('skos:Collection', $collectionN);
        }

        if (!empty($scheme)) {
            $schemeN = NTriple::getInstance()->serialize($scheme);
            $select->also('skos:inScheme', '?scheme')
                    ->also('?scheme', 'openskos:uuid', $schemeN);
        }

        if (!empty($from)) {
            $tFrom = $from->format(DATE_W3C);
            $select->filter('?modified >= "' . $tFrom . '"^^xsd:dateTime');
        }

        if (!empty($till)) {
            $tTill = $till->format(DATE_W3C);
            $select->filter('?modified >= "' . $tTill . '"^^xsd:dateTime');
        }
        
        return $this->conceptManager->fetchQuery($select);
    }
}
