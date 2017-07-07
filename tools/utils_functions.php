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
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
/* VOORBEELD!!!!
 * Run the file as :  php tenant.php --epic=true --code=testcode8 --name=testtenant8 --disableSearchInOtherTenants=true --enableStatussesSystem=true --email=o4@mail.com --uri=http://ergens/xxx5 --uuid=yyy5 --password=xxx create
 */

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\ConceptScheme;
use OpenSkos2\SkosCollection;
use Rhumsaa\Uuid\Uuid;

function check_if_admin($tenant_code, $key, $resourceManager, $user_model)
{

    if (null === $tenant_code) {
        fwrite(STDERR, "missing required `tenant_code` argument\n");
        exit(1);
    }

    if (null === $key) {
        fwrite(STDERR, "missing required `key` argument\n");
        exit(1);
    } else {
        $admin = $resourceManager->fetchRowWithRetries($user_model, 
            'apikey = ' . $user_model->getAdapter()->quote($key) . ' '
            . 'AND tenant = ' . $user_model->getAdapter()->quote($tenant_code)
        );

        if (null === $admin) {
            fwrite(STDERR, 'There is no user with the key ' . $key . ' in the '
                . 'tenant with the code ' . $tenant_code . "\n");
            exit(1);
        }
        if ($admin->role !== OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR) {
            fwrite(STDERR, "The user with the key " . $key . ' is not the '
                . 'administrator of the tenant with the code ' . $tenant_code . "\n");
            exit(1);
        }
    }
}

function set_property_with_check(&$resource, 
    $property, 
    $val, 
    $isURI = false, 
    $isBOOL = false, 
    $lang = null)
{
    if ($isURI) {
        if (isset($val)) {
            if (trim($val) !== '') {
                $resource->setProperty($property, new Uri($val));
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
            $resource->setProperty($property, new Literal($val, 
                null, 
                Literal::TYPE_BOOL));
        } else {
            // default value is 'false'
            $resource->setProperty($property, new Literal('false', 
                null, 
                Literal::TYPE_BOOL));
        }
        return;
    }

    // the property must be a literal
    if ($lang == null) {
        $resource->setProperty($property, new Literal($val));
    } else {
        $resource->setProperty($property, new Literal($val, $lang));
    };
}

function insert_set($tenant_code, 
    $resourceManager, 
    $uri, 
    $uuid, 
    $code, 
    $title, 
    $license, 
    $description, 
    $concep_base_uri, 
    $oai_base_uri, 
    $allow_oai, 
    $web_page)
{
    $count_sets = $resourceManager->countRdfTriples($uri, 
        Rdf::TYPE, 
        new Uri(Set::TYPE));
    if ($count_sets > 0) {
        fwrite(STDERR, 'The set with uri ' . $uri . "' "
            . "already exists in the triple store.\n");
        exit(1);
    }
    $count_sets = count($resourceManager->fetchSubjectForObject(OpenSKOS::UUID, 
        new Literal($uuid), 
        new Uri(Set::TYPE)));
    if ($count_sets > 0) {
        fwrite(STDERR, 'The set with uuid ' . $uuid . "' already exists in the triple store.\n");
        exit(1);
    }
    $count_sets = count($resourceManager->fetchSubjectForObject(OpenSKOS::CODE, 
        new Literal($code), 
        new Uri(Set::TYPE)));
    if ($count_sets > 0) {
        fwrite(STDERR, 'The set with code ' . $code . 
            "' already exists in the triple store.\n");
        exit(1);
    }

    $setResource = new Set();
    $setResource->setUri($uri);
    set_property_with_check($setResource, OpenSkos::CODE, $code);
    set_property_with_check($setResource, OpenSkos::UUID, $uuid);

    $publisher = $resourceManager->fetchByUuid($tenant_code, 
        \OpenSkos2\Tenant::TYPE, 
        'openskos:code');
    if ($publisher == null) {
        fwrite(STDERR, "Something went terribly worng: the tenant with the code "
            . "$tenant_code  has not been found in the triple store.\n");
        exit(1);
    } else {
        var_dump("PublisherURI: " . $publisher->getUri() . "\n");
    }
    $publisherURI = $publisher->getUri();

    set_property_with_check($setResource, 
        DcTerms::PUBLISHER, $publisherURI, true);
    set_property_with_check($setResource, 
        OpenSkos::TENANT, $tenant_code);

    set_property_with_check($setResource, DcTerms::TITLE, $title, false, false);
    if ($description !== NULL) {
        set_property_with_check($setResource, DcTerms::DESCRIPTION, $description);
    }
    set_property_with_check($setResource, OpenSkos::WEBPAGE, $web_page, true);
    set_property_with_check($setResource, DcTerms::LICENSE, $license, true);
    set_property_with_check($setResource, OpenSkos::OAI_BASEURL, $oai_base_uri, true);
    set_property_with_check($setResource, OpenSkos::ALLOW_OAI, $allow_oai, false, true);
    set_property_with_check($setResource, OpenSkos::CONCEPTBASEURI, $concep_base_uri, true);
    $resourceManager->insert($setResource);
    return $uri;
}

function insert_conceptscheme_or_skoscollection($setUri, $resourceManager, 
    $uri, 
    $uuid, 
    $title, 
    $description, 
    $rdftype)
{
    $count = $resourceManager->countRdfTriples($uri, Rdf::TYPE, 
        new Uri($rdftype));
    if ($count > 0) {
        fwrite(STDERR, "The resource of type $rdftype with the uri   $uri  "
            . " already exists in the triple store.\n");
        exit(1);
    }
    $count = count($resourceManager->fetchSubjectForObject(OpenSKOS::UUID, 
        new Literal($uuid), 
        $rdftype));
    if ($count > 0) {
        fwrite(STDERR, "The resource of type $rdftype with the uuid   $uuid "
            . "  already exists in the triple store.\n");
        exit(1);
    }
    $count = count($resourceManager->fetchSubjectForObject(DcTerms::TITLE, new Literal($title), $rdftype));
    if ($count > 0) {
        fwrite(STDERR, "The resource of type $rdftype with the title   $title "
            . "  already exists in the triple store.\n");
        exit(1);
    }

    switch ($rdftype) {
        case Skos::CONCEPTSCHEME:
            $resource = new ConceptScheme();
            break;
        case Skos::SKOSCOLLECTION:
            $resource = new SkosCollection();
            break;
        default:
            fwrite(STDERR, "`rdftype` can be set to either uri of scheme-type"
                . " or the uri for skos-collection-type\n");
            exit(1);
    }
    $resource->setUri($uri);
    set_property_with_check($resource, OpenSkos::UUID, $uuid);
    $set = $resourceManager->fetchByUri($setUri, Set::TYPE);
    if (count($set) < 1) {
        fwrite(STDERR, "The set with the uri $setUri has not been found in "
            . "the triple store.\n");
        exit(1);
    }
    set_property_with_check($resource, OpenSkos::SET, $setUri, true);

    $tenant_code = $set->getTenant();
    $tenantUri = $resourceManager->fetchSubjectForObject(OpenSkos::CODE, 
        $tenant_code, 
        \OpenSkos2\Tenant::TYPE);
    if (count($tenantUri) > 1) {
        fwrite(STDERR, "The tenant with the code $tenant_code has not been "
            . "found in the triple store.\n");
        exit(1);
    }
    set_property_with_check($resource, OpenSkos::TENANT, $tenant_code->getValue());
    set_property_with_check($resource, DcTerms::PUBLISHER, $tenantUri[0], true);

    set_property_with_check($resource, DcTerms::TITLE, $title, false, false);
    if ($description !== NULL) {
        set_property_with_check($resource, DcTerms::DESCRIPTION, $description);
    }
    $resourceManager->insert($resource);
    return $uri;
}

function createTenantRdf($code, 
    $name, 
    $uri, 
    $uuid, 
    $disableSearchInOtherTenants, 
    $enableStatussesSystem, 
    $enableSkosXl, 
    $resourceManager)
{

    $resources = $resourceManager->fetchSubjectForObject(OpenSkos::CODE, 
        new Literal($code), 
        Tenant::TYPE);
    if (count($resources) > 0) {
        fwrite(STDERR, 'A tenant  with the code ' . $code . 
            " has been already registered in the triple store. \n ");
        exit(1);
    }

    $insts = $resourceManager->fetchSubjectForObject(VCard::ORGNAME, 
        new Literal($name));
    if (count($insts) > 0) {
        fwrite(STDERR, "An institution with the name " . $name . 
            " has been already registered in the triple store. \n");
        exit(1);
    }

    $tenantResource = new Tenant();
    setID($tenantResource, $uri, $uuid, $resourceManager);


    $tenantResource->setProperty(OpenSkos::CODE, new Literal($code));
    $blank1 = "_:genid_" . Uuid::uuid4();
    $organisation = new Resource($blank1);
    if (isset($name)) {
        $organisation->setProperty(VCard::ORGNAME, new Literal($name));
    }
    //$resourceManager->setLiteralWithEmptinessCheck($organisation, 
    //vCard::ORGUNIT, " ");
    $tenantResource->setProperty(VCard::ORG, $organisation);
    //$resourceManager->setUriWithEmptinessCheck($tenantResource, 
    //OpenSkos::WEBPAGE, " ");
    //$resourceManager->setLiteralWithEmptinessCheck($tenantResource, 
    //vCard::EMAIL, "");

    $blank2 = "_:genid_" . Uuid::uuid4();
    $adress = new Resource($blank2);
    //$resourceManager->setLiteralWithEmptinessCheck($adress, 
    //vCard::STREET, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, 
    //vCard::LOCALITY, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, 
    //vCard::PCODE, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, 
    //vCard::COUNTRY, "");
    $tenantResource->setProperty(VCard::ADR, $adress);

    $disableBool = makeRdfBoolean($disableSearchInOtherTenants);
    $tenantResource->setProperty(OpenSkos::DISABLESEARCHINOTERTENANTS, 
        $disableBool);
    
    $enableStatussesBool = makeRdfBoolean($enableStatussesSystem); 
    $tenantResource->setProperty(OpenSkos::ENABLESTATUSSESSYSTEMS, 
        $enableStatussesBool);

    $enableSkosBool = makeRdfBoolean($enableSkosXl);
    $tenantResource->setProperty(OpenSkos::ENABLESKOSXL,  
        $enableSkosBool);
    
    return $tenantResource;
}

function setID(&$resource, $uri, $uuid, $resourceManager)
{
    if ($uri !== null && $uri !== "") {
        $exists = $resourceManager->askForUri($uri);
        if ($exists) {
            fwrite(STDERR, "An institution with the uri " . $uri . " "
                . "has been already registered in the triple store. \n");
            exit(1);
        }
        if ($uuid !== null && $uuid !== "") {
            $insts = $resourceManager->fetchSubjectForObject(OpenSkos::UUID, 
                new Literal($uuid), 
                Tenant::TYPE);
            if (count($insts) > 0) {
                fwrite(STDERR, "A institution with the uuid " . $uuid . 
                    " has been already registered in the triple store. \n");
                exit(1);
            }
            $resource->setUri($uri);
            $resource->setProperty(OpenSkos::UUID, new Literal($uuid));
        } else {
            fwrite(STDERR, "You should provide an uuid as well. \n");
            exit(1);
        }
    } else {
        fwrite(STDERR, "You should provide an uri \n");
        exit(1);
    }
}

/**
 * Make sure all cli options are given
 *
 * @param \Zend_Console_Getopt $opts
 */
function validateOptions(\Zend_Console_Getopt $opts)
{
    $required = [
        'db-hostname',
        'db-database',
        'db-username',
        //'db-password',
    ];
    foreach ($required as $req) {
        $reqOption = $opts->getOption($req);
        if (empty($reqOption)) {
            echo "absent {$req} \n";
            echo $opts->getUsageMessage();
            exit();
        }
    }
}

function insertResource(\OpenSkos2\Rdf\ResourceManager $resourceManager, 
    \OpenSkos2\Rdf\Resource 
    $resource, 
    $retry = 20)
{
    $tried = 0;
    filterLastModifiedDate($resource);
    do {
        try {
            return $resourceManager->replace($resource);
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo 'failed inserting retry' . PHP_EOL;
            //var_dump($resource);
            //exit(1);
            $tried++;
            sleep(5);
        }
    } while ($tried < $retry);
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
function filterLastModifiedDate(\OpenSkos2\Rdf\Resource $resource)
{
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

function makeRdfBoolean($rawVal){
    if (empty($rawVal)) {
        return (new Literal("false", null, Literal::TYPE_BOOL));
    }
    
    $loweredVal= strtolower($rawVal); 
    if ($loweredVal === "y" || $rawVal === "1" || 
        $rawVal === "true" || $rawVal === "yes") {
        return (new Literal("true", null, Literal::TYPE_BOOL));
    } else {
        return (new Literal("false", null, Literal::TYPE_BOOL));
    }
}
   

class Collections
{

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
    public function __construct(\Zend_Db_Adapter_Abstract $db)
    {
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
    public function validateCollections($resourceManager)
    {
        $retVal = [];
        foreach ($this->fetchAll() as $row) {
            if (!filter_var($row->conceptsBaseUrl, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Could not validate url for '
                    . 'collection: ' . var_export($row, true));
            }
                $set = new \OpenSkos2\Set($row->uri);
                
                $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::CONCEPTBASEURI, 
                    new Uri($row->conceptsBaseUrl));
                $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::CODE, 
                    new Literal($row->code));
                $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::TENANT, 
                    new Literal($row->tenant));
                
                $tenant = $resourceManager->fetchByUuid($row->tenant, 
                    \OpenSkos2\Tenant::TYPE, 'openskos:code');
                
                $set->setProperty(\OpenSkos2\Namespaces\DcTerms::PUBLISHER, 
                    new Uri($tenant->getUri()));
                if (!empty($row->dc_title)) {
                    $set->setProperty(\OpenSkos2\Namespaces\DcTerms::TITLE, 
                        new Literal($row->dc_title));
                }
                if (!empty($row->dc_description)) {
                    $set->setProperty(\OpenSkos2\Namespaces\DcTerms::DESCRIPTION, 
                        new Literal($row->dc_description));
                }
                if (!empty($row->website)) {
                    $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::WEBPAGE, 
                        new Uri($row->website));
                }
                if (!empty($row->license_url)) {
                    $set->setProperty(\OpenSkos2\Namespaces\DcTerms::LICENSE, 
                        new Uri($row->license_url));
                } else {
                    if (!empty($row->license_name)) {
                        $set->setProperty(\OpenSkos2\Namespaces\DcTerms::LICENSE, 
                            new Literal($row->license_name));
                    } else {
                        $set->setProperty(\OpenSkos2\Namespaces\DcTerms::LICENSE, 
                            new Uri("http://creativecommons.org/licenses/by/4.0/"));
                    }
                }
                if (!empty($row->OAI_baseURL)) {
                    $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::OAI_BASEURL, 
                        new Uri($row->OAI_baseURL));
                }
                $oai=makeRdfBoolean($row->allow_oai);
                $set->setProperty(\OpenSkos2\Namespaces\OpenSkos::ALLOW_OAI, 
                    $oai);
                
                $retVal[] = $set;
            }
        
        return $retVal;
    }

}

class Institutions
{

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * @var array
     */
    private $institutions = [];

    /**
     * Use the source db as parameter not the target.
     *
     * @param \Zend_Db_Adapter_Abstract $db
     */
    public function __construct(\Zend_Db_Adapter_Abstract $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public function fetchById($id)
    {
        if (!isset($this->fetchAll()[$id])) {
            throw new \RunTimeException('Institution not found');
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
        if (!empty($this->institutions)) {
            return $this->nstitutions;
        }
        $insts = $this->db->fetchAll('select * from tenant');
        foreach ($insts as $inst) {
            $this->institutions[$inst->code] = $inst;
        }
        return $this->institutions;
    }

    /**
     * Check if
     *
     * @throw \RuntimeException
     */
    public function validateInstitutions($resourceManager)
    {
        $retVal = [];
        foreach ($this->fetchAll() as $row) {
            $uuid = Uuid::uuid4();
            $uri = "http://tenant/{$uuid}";
            if (!empty($row->enableSkosXl)) {
               $skosXl = $row->enableSkosXl; 
            } else {
               $skosXl = "false";
            }
            if (!empty($row->epic)) {
               $epic = $row->epic; 
            } else {
               $epic = "false";
            }
            if (!empty($row->enableStatussesSystem)) {
               $enableStatussesSystem = $row->enableStatussesSystem; 
            } else {
               $enableStatussesSystem = "false";
            }
            $tenant = createTenantRdf($row->code, 
                $row->name, 
                $uri, 
                $uuid, 
                $row->disableSearchInOtherTenants, 
                $enableStatussesSystem, 
                $skosXl, 
                $resourceManager);
            $retVal[] = $tenant;
        }
        return $retVal;
    }

}
