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
        $valid = $this->checkConsistencyOfRelations($concept);
        if ($this->conceptReferenceCheckOn) {
            $customInit = $this->resourceManager->getCustomInitArray();
            if (count($customInit) === 0) {
               $strict = true; 
            } else {
                $strict = $this->strictCheckDanglingReferences($concept, $customInit);
                $soft = $this->softCheckDanglingReferences($concept, $customInit);
            }
        } else {
            $strict = true;
        }
        return $valid && $strict;
    }

    private function strictCheckDanglingReferences(Concept $concept, $init)
    {
        $errorsBefore = count($this->errorMessages);
        $toCheck = explode(" ", $init["relations_strict_reference_check"]);
        for ($i = 0; $i < count($toCheck); $i++) {
            $toCheck[$i] = trim($toCheck[$i]);
        }
        $properties = $concept->getProperties();
        foreach ($properties as $key => $values) {
            if (in_array($key, $toCheck)) {
                foreach ($values as $value) {
                    if (!($this->resourceManager->askForUri($value, false, Concept::TYPE))) {
                        $this->errorMessages[] = "The concept referred by  uri {$value->getUri()} is not found. ";
                    }
                }
            }
        }
        return ($errorsBefore === count($this->errorMessages));
    }

    private function softCheckDanglingReferences(Concept $concept, $init)
    {
        $toCheck = explode(" ", $init["relations_soft_reference_check"]);
        for ($i = 0; $i < count($toCheck); $i++) {
            $toCheck[$i] = trim($toCheck[$i]);
        }
        $properties = $concept->getProperties();
        foreach ($properties as $key => $values) {
            if (in_array($key, $toCheck)) {
                foreach ($values as $value) {
                    if (!($this->resourceManager->askForUri($value, false, Concept::TYPE))) {
                        $this->warningMessages[] = "The concept referred by  uri {$value->getUri()} is not found. "
                            . "It is addedto the the list of dangling references.";
                        $this->danglingReferences[] = $value;
                    }
                }
            }
            return true;
        }
    }

    private function checkConsistencyOfRelations(Concept $concept)
    {
        $errorsBefore = count($this->errorMessages);
        $customRelUris = array_values($this->resourceManager->getCustomRelationTypes());
        $registeredRelationUris = array_values($this->resourceManager->getTripleStoreRegisteredCustomRelationTypes());
        $allRelationUris = array_values($this->resourceManager->fetchConceptConceptRelationsNameUri());
        $conceptUri = $concept->getUri();
        $properties = array_keys($concept->getProperties());
        foreach ($properties as $property) {
            if (in_array($property, $allRelationUris)) {
                try {
                    $this->resourceManager->isRelationURIValid(
                        $property, $customRelUris, $registeredRelationUris, $allRelationUris
                    ); // throws an Exception
                    $relatedConcepts = $concept->getProperty($property);
                    foreach ($relatedConcepts as $relConceptUri) {
                        // throw an exception unless it is ok
                        $this->resourceManager->relationTripleCreatesCycle($conceptUri, $relConceptUri, $property);
                    }
                } catch (\Exception $ex) {
                    $this->errorMessages[] = $ex->getMessage();
                }
            }
        }
        return ($errorsBefore === count($this->errorMessages));
    }

}
