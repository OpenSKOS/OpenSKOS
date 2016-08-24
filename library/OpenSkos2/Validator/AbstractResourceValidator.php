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

namespace OpenSkos2\Validator;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;

abstract class AbstractResourceValidator implements ValidatorInterface
{
    protected $resourceManager;
    protected $resurceType;
    protected $isForUpdate;
    protected $tenantUri;
    protected $referenceCheckOn;
    /**
     * @var array
     */
    protected $errorMessages = [];
    
    
    public function setResourceManager($resourceManager) {
        if ($resourceManager === null) {
            throw new Exception("Passed resource manager is null in this validator. Proper content validation is not possible");
        }
        $this->resourceManager = $resourceManager;
    }

    public function setFlagIsForUpdate($isForUpdate) {
        if ($isForUpdate === null) {
            throw new Exception("Cannot validate the resource because isForUpdateFlag is set to null (cannot differ between create- and update- validation mode.");
        }
        $this->isForUpdate = $isForUpdate;
    }
    
    public function setTenant($tenantUri) {
        if ($tenantUri === null) {
            throw new Exception("Passed tenant uri is null in this validator. Proper content validation is not possible");
        }
        $this->tenantUri = $tenantUri;
    }

    /**
     * @param $resource RdfResource
     * @return boolean
     */
    abstract public function validate(RdfResource $resource); // switcher

    
    /**
     * @return string
     */
    public function getErrorMessages() {
       
        return $this->errorMessages;
    }

    protected function validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isBoolean, $isUnique, $type=null) {
        $this->errorMessages = [];
        $val = $resource->getProperty($propertyUri);
        
       if (count($val)<1) {
            if ($isRequired) {
              $this->errorMessages[] = $propertyUri . ' is required for all resources of this type.';
            } else {
               return true; 
            }
        }
        if (count($val) > 1) {
            if ($isSingle) {
            $this->errorMessages[]='There must be exactly 1 ' . $propertyUri . ' per resource. A few of them are given.';
            }
        }

       
        if ($isBoolean) {
            foreach ($val as $value) {
                $this->errorMessages = array_merge($this->errorMessages, $this-> checkBoolean($value, $propertyUri));
            }
        }

        if ($isUnique) {
            foreach ($val as $value) {
                if ($value instanceof Uri) {
                    $otherResources = $this->resourceManager->fetchSubjectWithPropertyGiven($propertyUri, '<' . $value->getUri() . '>', $this->resourceType);
                    $this->errorMessages = array_merge($this->errorMessages, $this->uniquenessCheck($resource, $otherResources, $propertyUri, $value->getUri()));
                } else { // a literal
                    if ($value instanceof Literal) {
                        $language=$value->getLanguage();
                        if ($language !== null && $language !==''){
                           $completeValue = '"'.$value->getValue().'"@'.$language; 
                        } else {
                           $completeValue = '"'.$value->getValue().'"'; 
                         }
                        $otherResources = $this->resourceManager->fetchSubjectWithPropertyGiven($propertyUri, $completeValue, $this->resourceType);
                        $this->errorMessages = array_merge($this->errorMessages, $this->uniquenessCheck($resource, $otherResources, $propertyUri, $completeValue));
                    } else {
                        $this->errorMessages = array_merge($this->errorMessages, 'Not correct rdf type for value ' . (string) $value);
                    }
                }
            }
        }

        if ($type !== null) { // check is the referred resource of the given type exists in the triple store
            foreach ($val as $value) {
                if ($value instanceof Uri)
                    $this->errorMessages = array_merge($this->errorMessages, $this->existenceCheck($value->getUri(), $type));
            }
        }

        return (count($this->errorMessages)===0);
    }
    
   private function uniquenessCheck($resource, $otherResourcesUris, $propertyUri, $value) {
       $errorMessages = ['The resource with the property ' . $propertyUri . ' set to ' . $value . ' has been already registered in the triple store.'];
       if (count($otherResourcesUris)>0){ 
           if ($this->isForUpdate) {
               if (count($otherResourcesUris)>1) { 
                  return $errorMessages; 
               } else { // a signle other resource is found but it may be the given resource and duplication is not a problem
                   if ($resource ->getUri() !== $otherResourcesUris[0]){ // the same resource
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
   
    private function checkBoolean($val, $propertyUri) {
        $testVal = trim($val);
        if (!($testVal == "true" || $testVal == "false")) {
            return ['The value of ' . $propertyUri . ' must be set to true or false. '];
        } else {
            return [];
        }
    }
    
     // the resource referred by the uri must exist in the triple store, 
    private function existenceCheck($uri, $rdfType) {
            if (!$this->referenceCheckOn){
                return [];
            }
            $count = $this->resourceManager->countTriples('<' . trim($uri) . '>', '<' . Rdf::TYPE . '>', '<' . $rdfType . '>');
            if ($count < 1) {
                return ['The resource (of type ' . $rdfType. ') referred by  uri ' . $uri . ' is not found in the triple store. '];
            } else {
                return [];
            }
    }
    
    // some common for different types of resources properties
    //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isBoolean, $isUnique,  $referencecheckOn, $type)
      
    protected function validateUUID(RdfResource $resource){
        return $this->validateProperty($resource, OpenSkos::UUID, true, true, false, true);
    }
    
    protected function validateOpenskosCode(RdfResource $resource){
        return $this->validateProperty($resource, OpenSkos::CODE, true, true, false, true);
    }
    
    protected function validateTitle(RdfResource $resource){
        $firstRound = $this->validateProperty($resource, DcTerms::TITLE, true, false, false, true);
        $titles=$resource->getProperty(DcTerms::TITLE);
        $pairs=[];
        $errorsBeforeSecondRound=count($this->errorMessages);
        foreach ($titles as $title) {
           $lang = $title->getLanguage();
           $val= $title->getValue();
           if ($lang === null || $lang===''){ // every title must have a language
              $this->errorMessages[]="Title ". $val . " is given without language. ";
           } else {
               if (array_key_exists($lang, $pairs)){
                   if ($pairs[$lang] !== $val) {
                      $this->errorMessages[]="More than 1 disticht title is given for the language tag ".$lang. " .";
                   }
               } else {
                  $pairs[$lang]=$val; 
               }
           }
        }
        $errorsBeforeAfterSecondRound=count($this->errorMessages);
        $secondRound = ($errorsBeforeSecondRound === $errorsBeforeAfterSecondRound);
        return ($firstRound && $secondRound);   
    }
    
    protected function validateDescription(RdfResource $resource){
        return $this->validateProperty($resource, DcTerms::DESCRIPTION, false, true, false, false);
    }
    
    protected function validateType(RdfResource $resource){
        return $this->validateProperty($resource, Rdf::TYPE, true, true, false, false);
    }
    
    //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isBoolean, $isUnique,  $type)
    
    protected function validateInSet(RdfResource $resource) {
        $firstRound = $this->validateProperty($resource, OpenSkos::SET, true, true, false, false, Dcmi::DATASET);
        if ($firstRound) {
            return $this->isSetOfCurrentTenant($resource);
        } else {
            return false;
        }
    }

    private function isSetOfCurrentTenant(RdfResource $resource) {
        $setUris = $resource->getProperty(OpenSkos::SET);
        $errorsBeforeCheck = count($this->errorMessages);
        foreach ($setUris as $setURI) {
            $set = $this->resourceManager->fetchByUri($setURI->getUri(), Dcmi::DATASET);
            $tenantUris = $set->getProperty(DcTerms::PUBLISHER);
            $tenantUri = $tenantUris[0]->getUri();
            if ($tenantUri !== $this->tenantUri) {
                $this->errorMessages[] = "The resource " .$resource->getUri() . " attempts to access the set  " . $setURI->getUri() . ", which does not belong to the user's tenant " . $this->tenantUri . ", but to the tenant " . $tenantUri . ".";
            }
        }
        $errorsAfterCheck = count($this->errorMessages);
        return ($errorsBeforeCheck===$errorsAfterCheck);
    }
    
    private function refersToSetOfCurrentTenant(RdfResource $resource, $referenceName, $referenceType) {
        $referenceUris = $resource->getProperty($referenceName);
        $errorsBeforeCheck = count($this->errorMessages);
        foreach ($referenceUris as $uri) {
            try {
                $refResource = $this->resourceManager->fetchByUri($uri->getUri(), $referenceType); //throws an exception if something is wrong
                $this->isSetOfCurrentTenant($refResource);
            } catch (\Exception $e) {
                $this->errorMessages[] = $e->getMessage();
            }
        }
        $errorsAfterCheck = count($this->errorMessages);
        return ($errorsBeforeCheck===$errorsAfterCheck);
    }

    protected function validateInScheme(RdfResource $resource) {
        $firstRound = $this->validateProperty($resource, Skos::INSCHEME, true, false, false, false, Skos::CONCEPTSCHEME);
        if ($firstRound) {
            if (ALLOWED_CONCEPTS_FOR_OTHER_TENANT_SCHEMES) {
                return true;
            } else {
                return $this->refersToSetOfCurrentTenant($resource, Skos::INSCHEME, Skos::CONCEPTSCHEME);
            }
        } else {
            return false;
        }
        return $firstRound;
    }

    protected function validateInSkosCollection(RdfResource $resource) {
        $firstRound= $this->validateProperty($resource, OpenSkos::INSKOSCOLLECTION, false, false, false, false, Skos::SKOSCOLLECTION);
        return $firstRound;
    }

    //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateCreator(RdfResource $resource){
        return $this->validateProperty($resource, DcTerms::CREATOR, true, true, false, false, Foaf::PERSON);
    }
    
}
