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
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Set;
use OpenSKOS_Db_Table_Collections;

class SetManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Set::TYPE;
    
      //TODO: check conditions when it can be deleted
    public function CanBeDeleted($uri){
        return parent::CanBeDeleted($uri);
    }
    
      // used only for HTML output
    public function fetchInstitutionCodeByUri($uri) {
        $query = 'SELECT ?code WHERE { <'.$uri.'>  <' . OpenSkos::CODE . '> ?name .  ?uri  <' . Rdf::TYPE . '> <'. Org::FORMALORG .'> .}';
        $codes = $this->query($query);
        if (count($codes) < 1) {
            return null;
        } else {
            return $codes[0];
        }
    }
    
   
   
  
     // used only for HTML representation
    public function fetchInhabitantsForSetViaCode($setCode, $rdfType) {
        if (TENANTS_AND_SETS_IN_MYSQL) {
        $query = "SELECT ?uri ?uuid WHERE  {  ?uri  <" . OpenSkos::SET . "> '" . $setCode . "' ."
                . ' ?uri  <' . Rdf::TYPE . '> <'. $rdfType.'> .'
                . ' ?uri  <' . OpenSkos::UUID . '> ?uuid .}';
        } else {
            $query = "SELECT ?uri ?uuid WHERE  {  ?seturi  <" . OpenSkos::CODE . "> '" . $setCode . "' . "
                 . "?uri  <" . OpenSkos::SET . "> ?seturi ."
                . ' ?uri  <' . Rdf::TYPE . '> <'. $rdfType.'> .'
                . ' ?uri  <' . OpenSkos::UUID . '> ?uuid .}';
        }
        $retVal = $this->fetchInhabitantsForSet($query);
        return $retVal;
    }

     // used only for HTML representation
    private function fetchInhabitantsForSet($query) {
        $retVal = [];
        $response = $this->query($query);
        if ($response !== null) {
            if (count($response) > 0) {
                foreach ($response as $tuple) {
                    $uri = $tuple->uri->getUri();
                    $uuid = $tuple->uuid->getValue();
                    $retVal[$uri] = $uuid;
                }
            }
        }

        return $retVal;
    }

    public function translateMySqlCollectionToRdfSet($collectionMySQL) {
        $setResource = new Set();
        if (!isset($collectionMySQL['uri'])) {
            $setResource->setUri('http://unset_uri_in_mysqldatabase');
        } else {
            $setResource->setUri($collectionMySQL['uri']);
        }
        
        $this->setLiteralWithEmptinessCheck($setResource, OpenSkos::CODE, $collectionMySQL['code']);
        $this->setLiteralWithEmptinessCheck($setResource, DcTerms::PUBLISHER, $collectionMySQL['tenant']);
        $this->setLiteralWithEmptinessCheck($setResource, DcTerms::TITLE, $collectionMySQL['dc_title']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::WEBPAGE, $collectionMySQL['website']);
        $this->setUriWithEmptinessCheck($setResource, DcTerms::LICENSE, $collectionMySQL['license_url']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::OAI_BASEURL, $collectionMySQL['OAI_baseURL']);
        $this->setBooleanLiteralWithEmptinessCheck($setResource, OpenSkos::ALLOW_OAI, $collectionMySQL['allow_oai']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::CONCEPTBASEURI, $collectionMySQL['conceptsBaseUrl']);
        return $setResource;
    }
    
    public function fetchFromMySQL($params) {
        $model = new OpenSKOS_Db_Table_Collections();
        $select = $model->select();
        if ($params['allow_oai']==='true') {
                    $select->where('allow_oai=?', 'Y');
        };
        if ($params['allow_oai']==='false') {
                    $select->where('allow_oai=?', 'N');
        };
        $mysqlres = $model->fetchAll($select);
        $index = new SetCollection();
        foreach ($mysqlres as $collection) {
            $rdfSet = $this->translateMySqlCollectionToRdfSet($collection);
            $index->append($rdfSet);
        }
        return $index;
    }
}
