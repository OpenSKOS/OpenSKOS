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

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Validator\AbstractConceptValidator;

class ReferencesForConceptRelations extends AbstractConceptValidator
{

    protected function validateConcept(Concept $concept)
    {
        $valid = $this->checkValidityOfRelations($concept);
        if ($this->conceptReferenceCheckOn) {
            $init = $this->manager->getInitArray();
            $strict = $this->strictCheckRelatedConcepts($concept, $init);
            $soft = $this->softCheckRelatedConcepts($concept, $init);
        } else {
            $strict = true;
        }
        return $valid && $strict;
    }

    private function strictCheckRelatedConcepts(Concept $concept, $init)
    {
        $errorsBefore = count($this->errorMessages);
        $toCheck = explode(" ", $init["custom.relations_strict_reference_check"]);
        for ($i = 0; $i < count($toCheck); $i++) {
            $toCheck[$i] = trim($toCheck[$i]);
        }
        $properties = $concept->getProperties();
        foreach ($properties as $key => $values) {
            if (in_array($key, $toCheck)) {
                foreach ($values as $value) {
                    $messages = $this->existenceCheck($value, Concept::TYPE);
                    if (count($messages) > 0) {
                        $this->errorMessages[] = implode($messages);
                    }
                }
            }
        }
        return ($errorsBefore === count($this->errorMessages));
    }

    private function softCheckRelatedConcepts(Concept $concept, $init)
    {
        $toCheck = explode(" ", $init["custom.relations_soft_reference_check"]);
        for ($i = 0; $i < count($toCheck); $i++) {
            $toCheck[$i] = trim($toCheck[$i]);
        }
        $properties = $concept->getProperties();
        foreach ($properties as $key => $values) {
            if (in_array($key, $toCheck)) {
                foreach ($values as $value) {
                    $messages = $this->existenceCheck($value, Concept::TYPE);
                    if (count($messages) > 0) {
                        $this->warningMessages[] = implode($messages) .
                            " Consult the list of dangling references for correction. ";
                        $this->danglingReferences[] = $value;
                    }
                }
            }
            return true;
        }
    }

    private function checkValidityOfRelations(Concept $concept)
    {
        $errorsBefore = count($this->errorMessages);
        $customRelUris = array_values($this->getCustomRelationTypes());
        $registeredRelationUris = array_values($this->manager->getTripleStoreRegisteredCustomRelationTypes());
        $allRelationUris = array_values($this->manager->fetchConceptConceptRelationsNameUri());
        $conceptUri = $concept->getUri();
        $properties = array_keys($concept->getProperties());
        foreach ($properties as $property) {
            if (in_array($property, $allRelationUris)) {
                try {
                    $this->resourceManager->isRelationURIValid($property, 
                        $customRelUris, 
                        $registeredRelationUris, 
                        $allRelationUris); // throws an Exception
                    $relatedConcepts = $concept->getProperty($property);
                    foreach ($relatedConcepts as $relConceptUri) {
                        // both throw an exception unless it is ok
                        $this->resourceManager->relationTripleIsDuplicated($conceptUri, $relConceptUri, $property);
                        $this->resourceManager->relationTripleCreatesCycle($conceptUri, $relConceptUri, $property);
                    }
                } catch (Exception $ex) {
                    $this->errorMessages[] = $ex->getMessage();
                }
            } 
        }
        return ($errorsBefore === count($this->errorMessages));
    }
}
