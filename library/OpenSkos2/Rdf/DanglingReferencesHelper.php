<?php

/**
 * OpenSKOS
 * /Users/olha/WorkProjects/open-skos-2/OpenSKOS2tempMeertens/library/OpenSkos2/Rdf/ResourceManager.php
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

namespace OpenSkos2\Rdf;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Uri;

class DanglingReferencesHelper {
    
    private $resourceManager;
    private $referent;
    private $property;
    private $dangling;
    
     public function __construct(ResourceManager $manager, $danglingReferences)
    {
        $this->resourceManager = $manager;
        $this -> initReferents($danglingReferences);
    }
    
    private function initReferents($danglingReferences) {
        foreach ($danglingReferences as $danglingUri) {
              $response = $this->resourceManager->fetchSubjectTypePropertyForObject($danglingUri);
              foreach ($response as $triple) {
                  $this->referent[] = $this->resourceManager -> fetchByUri($triple->subject->getUri(), $triple->type->getUri());
                  $this->property[]=$triple->property->getUri();
                  $this->dangling[]=$danglingUri;
              }       
        }
    }
       
    
    public function removeDanglingReferences() {
        $removed = 0;
        $resources = array_values($this->referent);
        $properties = array_values($this->property);
        $dangling = array_values($this->dangling);
        foreach ($resources as $resource) {
            foreach ($properties as $property) {
                $values = $resource->getProperty($property);
                $resource->unsetProperty($property);
                $this->resourceManager->replace($resource);
                return;
                foreach ($values as $value) {
                    if ($value instanceof Uri) {
                        $uri = $value->getUri();
                        if (!in_array($uri, $dangling)) {
                            $resource->addProperty($property, $value);
                        } else {
                            echo "\n Removed: ". $uri . " as ". $property . "from ".$resource->getUri();
                        }
                    } 
                }
            }
            $this->resourceManager->replace($resource);
        }
        echo "\n Removed " . $removed . " references \n";
    }

}