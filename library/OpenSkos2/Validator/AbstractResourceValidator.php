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
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Uri;

abstract class AbstractResourceValidator implements ValidatorInterface
{

    protected $resourceManager;
    protected $resurceType;
    protected $isForUpdate;
    protected $tenant;
    protected $set;
    protected $referenceCheckOn;
    protected $conceptReferenceCheckOn;
    private static $nonresolvableURIs = [Rdf::TYPE, DcTerms::LICENSE, OpenSkos::WEBPAGE,
        OpenSkos::CONCEPTBASEURI, OpenSkos::OAI_BASEURL];

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

    public function emptyErrorMessages()
    {
        $this->errorMessages=[];
    }

    public function emptyWarningMessages()
    {
        $this->warningMessages = [];
    }

    public function emptyDanglingReferences()
    {

        $this->danglingReferences = [];
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
                if (!($value == "true" || $value == "false")) {
                    $this->errorMessages[] = 'The value of ' . $propertyUri . ' must be set to true or false but '
                        . 'it is set to ' . $value;
                }
            }

            if ($isUnique) {
                if (!($this->uniquenessCheck($resource, $propertyUri, $value))) {
                    $this->errorMessages[] = "The resource of type {$this->resourceManager->getResourceType()} with "
                        . "the property  $propertyUri set to  $value has been already registered; {$this->isForUpdate}";
                }
            }

            if ($value instanceof Uri && $this->referenceCheckOn &&
                !in_array($propertyUri, self::$nonresolvableURIs)) { //ERROR
                if (!($exists = $this->resourceManager->askForUri(trim($value->getUri()), false, $type))) {
                    $this->errorMessages[] = "The resource (of type  $type) referred by uri " .
                        "{$value->getUri()} via the property $propertyUri is not found in this triple store ";
                    $this->danglingReferences[] = $value->getUri();
                }
            }

            if ($value instanceof Uri && !($this->referenceCheckOn) && $this->resourceManager != null &&
                !in_array($propertyUri, self::$nonresolvableURIs)) { // WARNING
                if (!($exists = $this->resourceManager->askForUri(trim($value->getUri()), false, $type))) {
                    $this->warningMessages[] = "The resource (of type  $type) referred by  uri " .
                        "{$value->getUri()} via the property $propertyUri is not found in thsi triple store. ";
                    $this->danglingReferences[] = $value->getUri();
                }
            }
        }

        return (count($this->errorMessages) === 0);
    }

    private function uniquenessCheck($resource, $propertyUri, $value)
    {
        $otherResourceUris = $this->resourceManager->fetchSubjectForObject(
            $propertyUri,
            $value,
            $this->resourceManager->getResourceType()
        );
        if (count($otherResourceUris) > 0) {
            if ($this->isForUpdate) { // for update
                if (count($otherResourceUris) > 1) {
                    return false;
                } else {
                    return($resource->getUri() === $otherResourceUris[0]);
                }
            } else { // for create
                return false;
            }
        } else { // no duplications found
            return true;
        }
    }

    protected function validateUUID($resource)
    {
        return $this->validateProperty($resource, OpenSkos::UUID, true, true, false, true);
    }

    protected function validateOpenskosCode($resource)
    {
        return $this->validateProperty($resource, OpenSkos::CODE, true, true, false, true);
    }

    protected function validateTitle($resource)
    {
        $firstRound = $this->validateProperty($resource, DcTerms::TITLE, true, false, false, true);
        $titles = $resource->getProperty(DcTerms::TITLE);
        $pairs = [];
        $errorsBeforeSecondRound = count($this->errorMessages);
        foreach ($titles as $title) {
            $lang = $title->getLanguage();
            $val = $title->getValue();
            if ($lang === null || $lang === '') { // every title must have a language ??
                // $this->errorMessages[] = "Title " . $val . " is given without language. ";
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

    protected function validateDescription($resource)
    {
        return $this->validateProperty($resource, DcTerms::DESCRIPTION, false, true, false, false);
    }

    protected function validateType($resource)
    {
        return $this->validateProperty($resource, Rdf::TYPE, true, true, false, false);
    }

    protected function validateInScheme($resource)
    {
        $retVal = $this->validateInSchemeOrInCollection(
            $resource,
            Skos::INSCHEME,
            Skos::CONCEPTSCHEME,
            true
        );
        return $retVal;
    }

    protected function validateInSkosCollection($resource)
    {
        $retVal = $this->validateInSchemeOrInCollection(
            $resource,
            OpenSkos::INSKOSCOLLECTION,
            Skos::SKOSCOLLECTION,
            false
        );
        return $retVal;
    }

    private function validateInSchemeOrInCollection($resource, $property, $rdftype, $must)
    {
        $firstRound = $this->validateProperty($resource, $property, $must, false, false, false, $rdftype);
        return $firstRound;
    }

    //validateProperty(RdfResource $resource, $propertyUri, $isRequired,
    //$isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateCreator($resource)
    {
        return $this->validateProperty($resource, DcTerms::CREATOR, true, true, false, false, \OpenSkos2\Person::TYPE);
    }

    protected function checkTenant($resource)
    {
        $firstRound = $this->validateProperty(
            $resource,
            DcTerms::PUBLISHER,
            true,
            true,
            false,
            false,
            \OpenSkos2\Tenant::TYPE
        );
        $tenantUri = $resource->getPublisherUri();
        $tenantCode = $resource->getTenant();
        $secondRound = true;
        if ($tenantCode == null) {
            $secondRound = false;
            $this->errorMessages[] = 'No tenant code as openskos:tenant is given. ';
        } else {
            $tripleStoreTenant = $this->resourceManager->fetchSubjectForObject(
                OpenSkos::CODE,
                $tenantCode,
                \OpenSkos2\Tenant::TYPE
            );

            if ($tripleStoreTenant[0] !== $tenantUri->getUri()) {
                $secondRound = false;
                $this->errorMessages[] = "Specified openskos:tenant code {$tenantCode} with the "
                    . "uri {$tripleStoreTenant[0]} does not correspond to dcterms:publisher uri $tenantUri . ";
            }
        }
        return $firstRound && $secondRound;
    }

    protected function checkSet($resource)
    {
        $firstRound = $this->validateProperty($resource, OpenSkos::SET, true, true, false, false, \OpenSkos2\Collection::TYPE);
        $secondRound = true;
        if ($firstRound) {

            $setUris = $resource->getSet();
            foreach ($setUris as $setUri) {
                $set = $this->resourceManager->fetchByUri($setUri, \OpenSkos2\Collection::TYPE);

                $tenantUri = $resource->getPublisherUri()->getUri();

                $setTenantCode = $set->getTenant();
                $tripleStoreSetTenant = $this->resourceManager->fetchSubjectForObject(
                    OpenSkos::CODE,
                    $setTenantCode,
                    \OpenSkos2\Tenant::TYPE
                );

                $publisherUri = $tripleStoreSetTenant[0];

                if ($tenantUri !== $publisherUri) {
                    $this->errorMessages[] = "The set $setUri declared in the resource has the tenant "
                        . "with the uri $publisherUri which does not coincide with the uri $tenantUri of the "
                        . "tenant declared in the resource";
                    $secondRound = false;
                }
            }

            $tenantCode = $resource->getTenant()->getValue();
            if ($set->getTenant() != null) {
                $publisherCode = $set->getTenant()->getValue();
                if ($tenantCode !== $publisherCode) {
                    $this->errorMessages[] = "The set $setUri declared in the resource has the tenant with "
                        . "the code $publisherCode which does not coincide with the code $tenantCode of the tenant "
                        . "declared in the resource";
                    $secondRound = false;
                }
            }
        }
        return $firstRound && $secondRound;
    }
}
