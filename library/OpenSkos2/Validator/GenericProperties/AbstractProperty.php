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

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractProperty
{
   
    // must return the array of errors
    public static function validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique, $type=null, $isForUpdate=null) {
        $retval = array();
        $val = $resource->getProperty($propertyUri);
        
       if (count($val)<1) {
            if ($isRequired) {
              $retval[] = $propertyUri . ' is required for all resources of this type';
            } else {
               return []; 
            }
        }
        if (count($val) > 1) {
            if ($isSingle) {
            $retval[]='There must be exactly 1 ' . $propertyUri . ' per resource. A few of them are given.';
            }
        }

       
        if ($isBoolean) {
            foreach ($val as $value) {
                $retval = array_merge($retval, self::checkBoolean($value, $propertyUri));
            }
        }

        if ($isUnique) {
            if ($isUri) {
                foreach ($val as $value) {
                    $otherResources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '<' . $value . '>', $this->resourceType);
                    $retval= array_merge($retval, self::uniquenessCheck($resource, $otherResources, $isForUpdate, $propertyUri, $value));
                }
            } else { // a literal
                foreach ($val as $value) {
                    $otherResources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '"' . $value . '"', $this->resourceType);
                    $retval= array_merge($retval, self::uniquenessCheck($resource, $otherResources, $isForUpdate, $propertyUri, $value));
                
                }
            }
        }
        
        if ($isUri && $type !== null) { // check is the referred resoource of the given type exists in the triple store
            foreach ($val as $value) {
                $retval = array_merge($retval, self::existenceCheck($value, $type));
            }
        }

        return $retval;
    }
    
   private static function uniquenessCheck($resource, $otherResources, $isForUpdate, $propertyUri, $value) {
       $errorMessages = ['The resource with the property ' . $propertyUri . ' set to ' . $value . ' has been already registered in the triple store.'];
       if (count($otherResources)>0){ 
           if ($isForUpdate) {
               if (count($otherResources)>1) { 
                  return $errorMessages; 
               } else { // a signle other resource is found but it may be the given resource and duplication is not a problem
                  if ($resource ->getUri() !== $otherResources[0]->getUri()){ // the same resource
                      return $errorMessages;
                  } else {
                      return [];
                  }
               }
           } else { // for create
              return $errorMessages;
           }
       } else { // no duplications found
           return [];
       }
   }
   
    private static function checkBoolean($val, $propertyUri) {
        $testVal = trim($val);
        if (!($testVal === "true" || $testVal === "false")) {
            return ['The value of ' . $propertyUri . ' must be set to true or false. '];
        } else {
            return [];
        }
    }
    
     // the resource referred by the uri must exist in the triple store, 
    private static function existenceCheck($uri, $rdfType) {
            $count = $this->resourceManager->countTriples('<' . trim($uri) . '>', '<' . Rdf::TYPE . '>', '<' . $rdfType . '>');
            if ($count < 1) {
                return ['The resource referred by  uri ' . $uri . ' is not found in the triple store. '];
            } else {
                return [];
            }
    }

}
