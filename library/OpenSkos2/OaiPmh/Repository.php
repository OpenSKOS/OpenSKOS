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
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\ConceptScheme;
use OpenSkos2\ConceptManager;
use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\OaiPmh\Concept as OaiConcept;
use OpenSkos2\Search\Autocomplete;
use OpenSkos2\Search\ParserText;
use OpenSkos2\SetManager;
use OpenSkos2\Tenant;
use Picturae\OaiPmh\Exception\IdDoesNotExistException;
use Picturae\OaiPmh\Implementation\MetadataFormatType as ImplementationMetadataFormatType;
use Picturae\OaiPmh\Implementation\RecordList as OaiRecordList;
use Picturae\OaiPmh\Implementation\Repository\Identity as ImplementationIdentity;
use Picturae\OaiPmh\Implementation\Set as OaiSet;
use Picturae\OaiPmh\Implementation\SetList as OaiSetList;
use Picturae\OaiPmh\Interfaces\MetadataFormatType;
use Picturae\OaiPmh\Interfaces\Record;
use Picturae\OaiPmh\Interfaces\RecordList;
use Picturae\OaiPmh\Interfaces\Repository as InterfaceRepository;
use Picturae\OaiPmh\Interfaces\Repository\Identity;
use Picturae\OaiPmh\Interfaces\SetList as InterfaceSetList;
use Picturae\OaiPmh\Exception\BadArgumentException;
use Picturae\OaiPmh\Exception\BadResumptionTokenException;

// Meertens: 
// -- we have setManager class, with sets inhabiting the triple store.
// As a result we have removed table "collections" from the MySql, the class OpenSKOS_Db_Table_Collections
// and all related code.
// -- all the resources are referred-to in other resources via their Uri's not via their codes, or so; 
// as a result getConcept has tenant, set and conceptschema parameters us Uri's not as literals.
// -- Picturae code changes starting from 21/11/2016 are taken except one (commented) fragment in getConcepts

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
     * @var SetManager
     */
    private $setManager;

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
     * @param SetManager $setManager
     * @param type $description
     */
    public function __construct(
        ConceptManager $conceptManager,
        ConceptSchemeManager $schemeManager,
        Autocomplete $searchAutocomplete,
        $repositoryName,
        $baseUrl,
        array $adminEmails,
        SetManager $setManager,
        $description = null
    ) {
        $this->conceptManager = $conceptManager;
        $this->schemeManager = $schemeManager;
        $this->searchAutocomplete = $searchAutocomplete;
        $this->repositoryName = $repositoryName;
        $this->baseUrl = $baseUrl;
        $this->adminEmails = $adminEmails;
        $this->setManager = $setManager;
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
     * @return InterfaceSetList (sets in a sence of picturae oai
     */
    public function listSets()
    {
        $oaisets = $this->getOaiSets();

        if (count($oaisets) < 1) {
            throw new \Exception("No sets with enabled oai.");
        }

        $items = [];
        $tenantAdded = [];
        foreach ($oaisets as $row) {
            // Tenant spec
            $tenantCode = $row['tenant_code'];
            if (!isset($tenantAdded[$tenantCode])) {
                $items[] = new OaiSet($tenantCode, $row['tenant_title']);
                $tenantAdded[$tenantCode] = $tenantCode;
            }

            // set spec
            $spec = $row['tenant_code'] . ':' . $row['code'];
            $items[] = new OaiSet($spec, $row['dcterms_title']);

            // Concept scheme spec
            $schemes = $this->schemeManager->getSchemeBySetUri($row['uri']);
            foreach ($schemes as $scheme) {
                $uuidProp = $scheme->getProperty(OpenSkos::UUID);
                $uuid = $uuidProp[0]->getValue();
                $schemeSpec = $spec . ':' . $uuid;

                $title = $scheme->getProperty(DcTerms::TITLE);
                $name = $title[0]->getValue();

                $items[] = new OaiSet($schemeSpec, $name);
            }
        }

        return new OaiSetList($items);
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
                $concept = $this->conceptManager->fetchByUuid($identifier, \OpenSkos2\Concept::TYPE);
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
            $pSet['set'],
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

        $cursor = (int) $params['offset'];

        $concepts = $this->getConcepts(
            $this->limit,
            $cursor,
            $params['from'],
            $params['until'],
            $pSet['tenant'],
            $pSet['set'],
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
                    $concept = $this->conceptManager->fetchByUuid($identifier, \OpenSkos2\Concept::TYPE);
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
            $this->setsMap = new SetsMap($this->schemeManager, $this->setManager);
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

        $tenantURI = null;
        if (!empty($arrSet[0])) {
            $tenants = $this->setManager->fetchSubjectForObject(
                OpenSkos::CODE,
                new Literal($arrSet[0]),
                Tenant::TYPE
            );
            if (count($tenants) > 0) {
                $tenantURI = $tenants[0];
            } else {
                throw new \Exception('A tenant with the code ' .
                    $arrSet[0] . " is not found in the triple store");
            }
        }
        $return['tenant'] = $tenantURI;

        $setURI = null;
        if (!empty($arrSet[1])) {
            $sets = $this->setManager->fetchSubjectForObject(
                OpenSkos::CODE,
                new Literal($arrSet[1]),
                Set::TYPE
            );
            if (count($sets) > 0) {
                $setURI = $sets[0];
            } else {
                throw new \Exception('A set with the code ' . $arrSet[1] . " is not found in the triple store");
            }
        }

        $return['set'] = $setURI;
        $conceptSchemeURI = null;

        if (!empty($arrSet[2])) {
            $conceptSchemes = $this->setManager->
                fetchSubjectForObject(
                OpenSkos::UUID,
                new Literal($arrSet[2]),
                ConceptScheme::TYPE
            );
            if (count($conceptSchemes) > 0) {
                $conceptSchemeURI = $conceptSchemes[0];
            } else {
                throw new \Exception('A concept scheme with the uuid ' .
                    $arrSet[2] . " is not found in the triple store");
            }
        }

        $return['conceptScheme'] = $conceptSchemeURI;

        return $return;
    }

    /**
     * Get all oai sets
     *
     * @return OaiSet[]
     */
    private function getOaiSets()
    {
        $sets = $this->setManager->fetchAllSets('true');
        $retVal = [];
        foreach ($sets as $set) {
            $row = [];
            $row['code'] = $set->getCode()->getValue();
            $row['dcterms_title'] = $set->getTitle()->getValue();
            $row['uri'] = $set->getUri();

            $tenantUri = $set->getTenantUri();
            if ($tenantUri == null) {
                $row['tenant_code'] = 'UNKNOWN';
                $row['tenant_title'] = 'UNKNOWN';
            } else {
                $tenantData = $this->fetchTenantSpecDataViaUri($tenantUri, $this->setManager);
                $row['tenant_code'] = $tenantData['tenant_code'];
                $row['tenant_title'] = $tenantData['tenant_title'];
            }

            $retVal[] = $row;
        }

        return $retVal;
    }

    private function fetchTenantSpecDataViaUri($tenantUri, $resourceManager)
    {
        $retVal = [];
        $tenant = $resourceManager->findResourceById($tenantUri, Tenant::TYPE);
        $retVal['tenant_code'] = $tenant->getCode()->getValue();
        $orgElements = $tenant->getProperty(VCard::ORG);
        if (count($orgElements) > 0) {
            $orgElement = $orgElements[0];
            $names = $orgElement->getProperty(VCard::ORGNAME);
            if (count($names) > 0) {
                $retVal['tenant_title'] = $names[0];
            } else {
                $retVal['tenant_title'] = 'UNKNOWN';
            }
        } else {
            $retVal['tenant_title'] = 'UNKNOWN';
        }
        return $retVal;
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
     * @param \OpenSKOS2\Rdf\Uri $tenant
     * @param \OpenSKOS2\Rdf\Uri $set
     * @param \OpenSKOS2\Rdf\Uri $scheme
     * @param int $numFound
     * @return \OpenSKOS2\ConceptCollection
     */
    private function getConcepts(
        $limit = 10,
        $offset = 0,
        \DateTime $from = null,
        \DateTime $till = null,
        $tenant = null,
        $set = null,
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
            $searchOptions['tenant'] = [$tenant];
        }
        if (!empty($set)) {
            $searchOptions['set'] = [$set];
        }

        //Meertens: the scheme is already an Uri.
        // obtaining Uri of the scheme from its uuid happens in parseSet.
        if (!empty($scheme)) {
            $searchOptions['scheme'] = [$scheme];
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
        $retVal = $this->searchAutocomplete->search($searchOptions, $numFound);
        return $retVal;
    }
}
