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

use DateTime;
use OpenSkos2\Concept;
use OpenSkos2\ConceptManager;
use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\Search\Autocomplete;
use OpenSkos2\Search\ParserText;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\OaiPmh\Concept as OaiConcept;
use OpenSkos2\Rdf\Literal;
use OpenSKOS_Db_Table_Row_Collection;
use Picturae\OaiPmh\Exception\BadArgumentException;
use Picturae\OaiPmh\Exception\BadResumptionTokenException;
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

class Repository implements InterfaceRepository
{
    const PREFIX_OAI_RDF = 'oai_rdf';
    const PREFIX_OAI_RDF_XL = 'oai_rdf_xl';
    
    const SCHEMA_OAI_RDF = 'http://www.openarchives.org/OAI/2.0/rdf.xsd';

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
     * Used to get tenant:set:schema sets.
     * @var SetsMap
     */
    protected $setsMap;

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
     *
     * @var \OpenSKOS_Db_Table_Collections
     */
    private $setsModel;

    /**
     *
     * @var ConceptSchemeManager
     */
    private $schemeManager;

    /**
     *
     * @var Autocomplete
     */
    private $searchAutocomplete;
    
    /**
     * @param ConceptManager $conceptManager
     * @param ConceptSchemeManager $schemeManager
     * @param Autocomplete $searchAutocomplete
     * @param string $repositoryName
     * @param string $baseUrl
     * @param array $adminEmails
     * @param \OpenSKOS_Db_Table_Collections $setsModel
     * @param string $description
     */
    public function __construct(
        ConceptManager $conceptManager,
        ConceptSchemeManager $schemeManager,
        Autocomplete $searchAutocomplete,
        $repositoryName,
        $baseUrl,
        array $adminEmails,
        \OpenSKOS_Db_Table_Collections $setsModel,
        $description = null
    ) {
        $this->conceptManager = $conceptManager;
        $this->schemeManager = $schemeManager;
        $this->searchAutocomplete = $searchAutocomplete;
        $this->repositoryName = $repositoryName;
        $this->baseUrl = $baseUrl;
        $this->adminEmails = $adminEmails;
        $this->setsModel = $setsModel;
        $this->description = $description;
    }

    /**
     * @return string the base URL of the repository
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     * the finest harvesting granularity supported by the repository. The legitimate values are
     * YYYY-MM-DD and YYYY-MM-DDThh:mm:ssZ with meanings as defined in ISO8601.
     */
    public function getGranularity()
    {
        return ImplementationIdentity::GRANULARITY_YYYY_MM_DDTHH_MM_SSZ;
    }

    /**
     * @return Identity
     */
    public function identify()
    {
        return new ImplementationIdentity(
            $this->repositoryName,
            $this->getEarliestDateStamp(),
            Identity::DELETED_RECORD_PERSISTENT,
            $this->adminEmails,
            $this->getGranularity(),
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
            $spec = $row['tenant_code'] . ':' . $row['code'];
            $items[] = new Set($spec, $row['dc_title']);

            // Concept scheme spec
            $schemes = $this->schemeManager->getSchemesByCollectionUri($row['uri']);
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
            if (\Rhumsaa\Uuid\Uuid::isValid($identifier)) {
                $concept = $this->conceptManager->fetchByUuid($identifier);
                if ($metadataFormat === self::PREFIX_OAI_RDF_XL) {
                    $concept->loadFullXlLabels($this->conceptManager->getLabelManager());
                }
            } else {
                throw new BadArgumentException('Invalid identifier ' . $identifier);
            }
        } catch (ResourceNotFoundException $exc) {
            throw new IdDoesNotExistException('No matching identifier ' . $identifier, $exc->getCode(), $exc);
        }
        
        return new OaiConcept($concept, $this->getSetsMap(), $metadataFormat);
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
            $this->limit,
            0,
            $from,
            $until,
            $pSet['tenant'],
            $pSet['collection'],
            $pSet['conceptScheme'],
            $numFound
        );

        $items = [];
        foreach ($concepts as $i => $concept) {
            /* @var $concept Concept */
            if ($metadataFormat === self::PREFIX_OAI_RDF_XL) {
                $concept->loadFullXlLabels($this->conceptManager->getLabelManager());
            }
            $items[] = new OaiConcept($concept, $this->getSetsMap(), $metadataFormat);
        }

        $token = null;
        if ($numFound > $this->limit) {
            $token = $this->encodeResumptionToken($this->limit, $from, $until, $metadataFormat, $set);
        }

        return new OaiRecordList($items, $token, $numFound, 0);
    }

    /**
     * @param string $token
     * @return RecordList
     */
    public function listRecordsByToken($token)
    {
        $params = $this->decodeResumptionToken($token);

        $pSet = $this->parseSet($params['set']);

        $cursor = (int)$params['offset'];

        $concepts = $this->getConcepts(
            $this->limit,
            $cursor,
            $params['from'],
            $params['until'],
            $pSet['tenant'],
            $pSet['collection'],
            $pSet['conceptScheme'],
            $numFound
        );

        $items = [];
        foreach ($concepts as $i => $concept) {
            $items[] = new OaiConcept($concept, $this->getSetsMap());
        }

        $token = null;
        if ($numFound > ($cursor + $this->limit)) {
            $token = $this->encodeResumptionToken(
                $cursor + $this->limit,
                $params['from'],
                $params['until'],
                $params['metadataPrefix'],
                $params['set']
            );
        }

        return new OaiRecordList($items, $token, $numFound, $cursor);
    }

    /**
     * @param string $identifier
     * @return MetadataFormatType[]
     */
    public function listMetadataFormats($identifier = null)
    {
        $formats = [];

        // We don't support different metadata formats based on identifier, but spec requires error if identifier
        // can not be found.
        if (!is_null($identifier)) {
            try {
                if (\Rhumsaa\Uuid\Uuid::isValid($identifier)) {
                    $concept = $this->conceptManager->fetchByUuid($identifier);
                } else {
                    throw new BadArgumentException('Invalid identifier ' . $identifier);
                }
            } catch (ResourceNotFoundException $exc) {
                throw new IdDoesNotExistException('No matching identifier ' . $identifier, $exc->getCode(), $exc);
            }
        }

        // @TODO The oai_dc is actually required by the oai-pmh specs. So some day has to be implemented.
//        $formats[] = new ImplementationMetadataFormatType(
//            'oai_dc',
//            'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
//            'http://www.openarchives.org/OAI/2.0/oai_dc/'
//        );

            $formats[] = new ImplementationMetadataFormatType(
                self::PREFIX_OAI_RDF,
                self::SCHEMA_OAI_RDF,
                Namespaces\Skos::NAME_SPACE
            );
            
            $formats[] = new ImplementationMetadataFormatType(
                self::PREFIX_OAI_RDF_XL,
                self::SCHEMA_OAI_RDF,
                Namespaces\SkosXl::NAME_SPACE
            );

        return $formats;
    }

    /**
     * @return SetsMap
     */
    protected function getSetsMap()
    {
        // @TODO DI
        if ($this->setsMap === null) {
            $this->setsMap = new SetsMap($this->schemeManager, $this->setsModel);
        }
        return $this->setsMap;
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
        if (!empty($arrSet[1])) {
            $collections = new \OpenSKOS_Db_Table_Collections();
            $collectionRow = $collections->findByCode($arrSet[1], $tenant);
            if ($collectionRow && !empty($collectionRow->uri)) {
                $collection = $collectionRow->uri;
            } else {
                $collection = new Literal($arrSet[1]);
            }
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
        $sql = $this->setsModel->select(true)
            ->join(
                ['ten' => 'tenant'],
                'tenant = ten.code',
                [
                    'tenant_title' => 'ten.name',
                    'tenant_code' => 'ten.code',
                ]
            )
            ->order('tenant ASC')
            ->setIntegrityCheck(false);

        return $this->setsModel->fetchAll($sql);
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

        if (!empty($token) && is_null(json_decode(base64_decode($token)))) {
            throw new BadResumptionTokenException("Resumption token present but contains invalid data");
        }
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
        if (!$this->earliestDateStamp) {
            $this->earliestDateStamp = $this->conceptManager->fetchMinModifiedDate();
        }

        return $this->earliestDateStamp;
    }

    /**
     * Fetch all concepts based on parameters in the token
     *
     * @param int $limit
     * @param int $offset
     * @param \DateTime $from
     * @param \DateTime $till
     * @param \OpenSKOS2\Rdf\Literal $tenant
     * @param \OpenSKOS2\Rdf\Literal $collection
     * @param \OpenSKOS2\Rdf\Literal $scheme
     * @param int $numFound
     * @return \OpenSKOS2\ConceptCollection
     */
    private function getConcepts(
        $limit = 10,
        $offset = 0,
        \DateTime $from = null,
        \DateTime $till = null,
        $tenant = null,
        $collection = null,
        $scheme = null,
        &$numFound = null
    ) {
        $searchOptions = [
            'start' => $offset,
            'rows' => $limit,
            'directQuery' => true,
            // We include all statuses.
            'status' => Concept::getAvailableStatuses(),
            'sorts' => ['uri' => 'asc'],
        ];

        if (!empty($tenant)) {
            $searchOptions['tenants'] = [$tenant->getValue()];
        }

        if (!empty($collection)) {
            $searchOptions['collections'] = [$collection];
        }

        if (!empty($scheme)) {
            try {
                $schemeObj = $this->schemeManager->fetchByUuid($scheme->getValue());
                $searchOptions['conceptScheme'] = [$schemeObj->getUri()];
            } catch (ResourceNotFoundException $exc) {
                $searchOptions['conceptScheme'] = [$scheme->getValue()];
            }
        }

        if (!empty($from) || !empty($till)) {
            $parser = new ParserText();
            $searchOptions['searchText'] = $parser->buildDatePeriodQuery(
                'd_modified',
                $from,
                $till
            );
        } else {
            $searchOptions['searchText'] = '';
        }

        return $this->searchAutocomplete->search($searchOptions, $numFound);
    }
}
