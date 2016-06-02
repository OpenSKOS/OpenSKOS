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
    public function fetchInhabitantsForSet($setUri, $rdfType) {
        $retVal = [];
        $query = 'SELECT ?uri ?uuid WHERE  {  ?uri  <' . OpenSkos::SET . '> <' . $setUri . '> .'
                . ' ?uri  <' . Rdf::TYPE . '> <'. $rdfType.'> .'
                . ' ?uri  <' . OpenSkos::UUID . '> ?uuid .}';
        $response = $this->query($query);
        if ($response !== null) {
            if (count($response) > 0) {
                $retVal = $this->arrangeTripleStoreScheme($response);
            }
        }

        return $retVal;
    }
  

    // used only for html output
    private function arrangeTripleStoreScheme($response) {
        $retVal = [];
        foreach ($response as $tuple) {
            $uri = $tuple -> uri->getUri();
            $uuid = $tuple -> uuid->getValue();
            $retVal[$uri]=$uuid;
        }
        return $retVal;
        
    }
    
}
