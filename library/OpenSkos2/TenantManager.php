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

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Tenant;

class TenantManager extends ResourceManager
{
  
    protected $resourceType = Tenant::TYPE;
   
    public function fetchNameUri() {
        $result = $this->fetchTenantNameUri();
        return $result;
    }
    
    
    
    // used only for HTML representation
    public function fetchSetsForTenant($code) {
        $query = 'SELECT ?seturi ?p ?o WHERE  { ?tenanturi  <' . OpenSkos::CODE . "> '" . $code . "' ."
                . ' ?seturi  <' . DcTerms::PUBLISHER . '> ?tenanturi .'
                . ' ?seturi  ?p ?o .}';
        $response = $this->query($query);
        if ($response !== null) {
            if (count($response) > 0) {
                $retVal = $this->arrangeTripleStoreSets($response);
                return $retVal;
            }
        }

        return [];
    }

    // used only for html output
    private function arrangeTripleStoreSets($response) {
        $retVal = [];
        foreach ($response as $triple) {
            $seturi = $triple -> seturi->getUri();
            if (!array_key_exists($seturi, $retVal)) {
                $retVal[$seturi]=[];
            };
            switch ($triple -> p) {
                case DcTerms::TITLE:
                    $retVal[$seturi]['dcterms_title']=$triple->o->getValue();
                    continue;
                case DcTerms::DESCRIPTION:
                    $retVal[$seturi]['dcterms_description']=$triple->o->getValue();
                    continue;
                case OpenSkos::WEBPAGE:
                    $retVal[$seturi]['openskos_webpage']=$triple->o->getUri();
                    continue;
                case OpenSkos::CODE:
                    $retVal[$seturi]['openskos_code']=$triple->o->getValue();
                    continue; 
                case OpenSkos::UUID:
                    $retVal[$seturi]['openskos_uuid']=$triple->o->getValue();
                    continue;
                default: continue;
            }
        }
        return $retVal;
        
    }
   
}
