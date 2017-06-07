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
    protected $tenant;
    protected $set;
    protected $referenceCheckOn;
    protected $conceptReferenceCheckOn;
    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var array
     */
    protected $warningMessages = [];

    /**
     * @var array
     */
    protected $danglingReferences = [];
    
    
    public function setResourceManager($resourceManager)
    {
        if ($resourceManager === null) {
            throw new \Exception(
                "Passed resource manager is null in this validator. "
                . "Proper content validation is not possible"
            );
        }
        $this->resourceManager = $resourceManager;
    }

    public function setFlagIsForUpdate($isForUpdate)
    {
        if ($isForUpdate === null) {
            throw new \Exception(
                "Cannot validate the resource because isForUpdateFlag is set to null"
                . " (cannot differ between create- and update- validation mode."
            );
        }
        $this->isForUpdate = $isForUpdate;
    }

    public function setTenant($tenant)
    {
        $this->tenant = $tenant;
    }

    public function setSet($set)
    {
        $this->set = $set;
    }
    
    public function setDeleteDanglingConceptRelatonReferences($flag)
    {
        $this->deleteDanglingConceptRelatonReferences = $flag;
    }

    /**
     * @param $resource RdfResource
     * @return boolean
     */
    abstract public function validate(RdfResource $resource); // switcher

    /**
     * @return string[]
     */
    public function getErrorMessages()
    {

        return $this->errorMessages;
    }

    /**
     * @return string[]
     */
    public function getWarningMessages()
    {

        return $this->warningMessages;
    }

    /**
     * @return string[]
     */
    public function getDanglingReferences()
    {

        return $this->danglingReferences;
    }

    protected function validateProperty(
        RdfResource $resource,
        $propertyUri,
        $isRequired,
        $isSingle,
        $isBoolean,
        $isUnique,
        $type = null
    ) {
        $val = $resource->getProperty($propertyUri);
        if (count($val) < 1) {
            if ($isRequired) {
                $this->errorMessages[] = $propertyUri . ' is required for all resources of this type.';
            } else {
                return true;
            }
        }
        if (count($val) > 1) {
            if ($isSingle) {
                $this->errorMessages[] = 'There must be exactly 1 ' .
                    $propertyUri . ' per resource. A few of them are given.';
            }
        }

        foreach ($val as $value) {
            if ($isBoolean) {
                $this->errorMessages = array_merge(
                    $this->errorMessages,
                    $this->checkBoolean($value, $propertyUri)
                );
            }
            if ($value instanceof Uri) {
                if ($type != null) {
                    $this->errorMessages = array_merge(
                        $this->errorMessages,
                        $this->existenceCheck($value, $type)
                    );
                };
                if ($isUnique) {
                    $otherResourceUris = $this->resourceManager->fetchSubjectUriForUriRdfObject(
                        $resource,
                        $propertyUri,
                        $value
                    );
                    $this->errorMessages = array_merge(
                        $this->errorMessages,
                        $this->uniquenessCheck($resource, $otherResourceUris, $propertyUri, $value)
                    );
                }
            }
            if (($value instanceof Literal) && $isUnique) {
                $otherResourceUris = $this->resourceManager->fetchSubjectUriForLiteralRdfObject(
                    $resource,
                    $propertyUri,
                    $value
                );
                $this->errorMessages = array_merge(
                    $this->errorMessages,
                    $this->uniquenessCheck($resource, $otherResourceUris, $propertyUri, $value)
                );
            }
        }
        return (count($this->errorMessages) === 0);
    }

    private function uniquenessCheck($resource, $otherResourceUris, $propertyUri, $value)
    {
        $errorMessages = ['The resource of type ' . $resource->getType()->getUri() .
            ' with the property ' . $propertyUri . ' set to ' . $value .
            ' has been already registered.'];
        if (count($otherResourceUris) > 0) {
            if ($this->isForUpdate) { // for update
                if (count($otherResourceUris) > 1) {
                    return $errorMessages;
                } else {
                    if ($resource->getUri() !== $otherResourceUris[0]) { // the same resource
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

    private function checkBoolean($val, $propertyUri)
    {
        $testVal = trim($val);
        if (!($testVal == "true" || $testVal == "false")) {
            return ['The value of ' . $propertyUri . ' must be set to true or false. '];
        } else {
            return [];
        }
    }

    // the resource referred by the uri must exist in the triple store,
    protected function existenceCheck($uri, $rdfType)
    {
        if (!$this->referenceCheckOn) {
            return [];
        }
        if ($this->resourceManager !== null) {
            $exists = $this->resourceManager->resourceExists(trim($uri->getUri()), $rdfType);
            if (!$exists) {
                return ['The resource (of type ' . $rdfType . ') referred by  uri ' .
                    $uri->getUri() . ' is not found. '];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    // some common for different types of resources properties
    //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle,
    //$isBoolean, $isUnique,  $referencecheckOn, $type)

    protected function validateUUID(RdfResource $resource)
    {
        return $this->validateProperty($resource, OpenSkos::UUID, true, true, false, true);
    }

    protected function validateOpenskosCode(RdfResource $resource)
    {
        return $this->validateProperty($resource, OpenSkos::CODE, true, true, false, true);
    }

    protected function validateTitle(RdfResource $resource)
    {
        $firstRound = $this->validateProperty($resource, DcTerms::TITLE, true, false, false, true);
        $titles = $resource->getProperty(DcTerms::TITLE);
        $pairs = [];
        $errorsBeforeSecondRound = count($this->errorMessages);
        foreach ($titles as $title) {
            $lang = $title->getLanguage();
            $val = $title->getValue();
            if ($lang === null || $lang === '') { // every title must have a language
                $this->errorMessages[] = "Title " . $val . " is given without language. ";
            } else {
                if (array_key_exists($lang, $pairs)) {
                    if ($pairs[$lang] !== $val) {
                        $this->errorMessages[] = "More than 1 disticht title is given for the language tag " .
                            $lang . " .";
                    }
                } else {
                    $pairs[$lang] = $val;
                }
            }
        }
        $errorsBeforeAfterSecondRound = count($this->errorMessages);
        $secondRound = ($errorsBeforeSecondRound === $errorsBeforeAfterSecondRound);
        return ($firstRound && $secondRound);
    }

    protected function validateDescription(RdfResource $resource)
    {
        return $this->validateProperty($resource, DcTerms::DESCRIPTION, false, true, false, false);
    }

    protected function validateType(RdfResource $resource)
    {
        return $this->validateProperty($resource, Rdf::TYPE, true, true, false, false);
    }

    //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isBoolean, $isUnique,  $type)

    protected function validateInSet(RdfResource $resource)
    {
        // set can be derived from the resource parameter, passed to $set
        $firstRound = $this->validateProperty($resource, OpenSkos::SET, true, true, false, false, Dcmi::DATASET);
        if ($firstRound) {
            $secondRound = $this->isSetOfCurrentTenant($resource);
            if ($this->set == null) {
                return $secondRound;
            } else {
                $thirdRound = $this->isSetCoinsideWithSetRequestParameter($resource);
                return $thirdRound;
            }
        } else {
            return false;
        }
    }

    private function isSetOfCurrentTenant(RdfResource $resource)
    {
        $setUris = $resource->getProperty(OpenSkos::SET);
        $errorsBeforeCheck = count($this->errorMessages);
        foreach ($setUris as $setURI) {
            $set = $this->resourceManager->fetchByUri($setURI->getUri(), Dcmi::DATASET);
            $tenantUris = $set->getProperty(DcTerms::PUBLISHER);
            $tenantUri = $tenantUris[0]->getUri();
            if ($tenantUri !== $this->tenant->getUri()) {
                $this->errorMessages[] = "The resource " . $resource->getUri() . " of type " . $resource->getType().
                    " attempts to access the set  " . $setURI->getUri() .
                    ", which does not belong to the user's tenant " . $this->tenant->getUri() .
                    ", but to the tenant " . $tenantUri . ".";
            }
        }
        $errorsAfterCheck = count($this->errorMessages);
        return ($errorsBeforeCheck === $errorsAfterCheck);
    }

    private function isSetCoinsideWithSetRequestParameter(RdfResource $resource)
    {
        $setUris = $resource->getProperty(OpenSkos::SET);
        $errorsBeforeCheck = count($this->errorMessages);
        foreach ($setUris as $setURI) {
            if ($setURI->getUri() !== $this->set->getUri()) {
                $this->errorMessages[] = "The resource " . $resource->getUri() ." of type " . $resource->getType().
                    " attempts to access the set  " . $setURI->getUri() .
                    ", which does not coinside with the set announced by request parameter with " .
                    $this->set->getUri();
            }
        }
        $errorsAfterCheck = count($this->errorMessages);
        return ($errorsBeforeCheck === $errorsAfterCheck);
    }

    private function refersToSetOfCurrentTenant(RdfResource $resource, $referenceName, $referenceType)
    {
        $referenceUris = $resource->getProperty($referenceName);
        $errorsBeforeCheck = count($this->errorMessages);
        foreach ($referenceUris as $uri) {
            try {
                $refResource = $this->resourceManager->fetchByUri(
                    $uri->getUri(),
                    $referenceType
                );
//throws an exception if something is wrong
                $this->isSetOfCurrentTenant($refResource);
            } catch (\Exception $e) {
                $this->errorMessages[] = $e->getMessage();
            }
        }
        $errorsAfterCheck = count($this->errorMessages);
        return ($errorsBeforeCheck === $errorsAfterCheck);
    }

    protected function validateInScheme(RdfResource $resource)
    {
        $retVal = $this->validateInSchemeOrInCollection(
            $resource,
            Skos::INSCHEME,
            Skos::CONCEPTSCHEME,
            true
        );
        return $retVal;
    }

    protected function validateInSkosCollection(RdfResource $resource)
    {
        $retVal = $this->validateInSchemeOrInCollection(
            $resource,
            OpenSkos::INSKOSCOLLECTION,
            Skos::SKOSCOLLECTION,
            false
        );
        return $retVal;
    }

    //validateProperty(RdfResource $resource, $propertyUri, $isRequired,
    //$isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateCreator(RdfResource $resource)
    {
        return $this->validateProperty($resource, DcTerms::CREATOR, true, true, false, false, Foaf::PERSON);
    }

    private function validateInSchemeOrInCollection(RdfResource $resource, $property, $rdftype, $must)
    {
        $firstRound = $this->validateProperty($resource, $property, $must, false, false, false, $rdftype);
        if ($firstRound) {
            $init = $this->resourceManager->getInitArray();
            if ($init["custom.allowed_concepts_for_other_tenant_schemes"]) {
                return true;
            } else {
                $correcttenant = $this->refersToSetOfCurrentTenant($resource, $property, $rdftype);
                return $correcttenant;
            }
        } else {
            return false;
        }
        return $firstRound;
    }
}
