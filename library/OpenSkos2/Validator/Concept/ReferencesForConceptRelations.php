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

class ReferencesForConceptRelations extends AbstractConceptValidator {

  protected function validateConcept(Concept $concept) {
    $nonrelationproperties = array_merge(Concept::$classes['ConceptSchemes'], Concept::$classes['SkosCollections']);
    array_push($nonrelationproperties, \OpenSkos2\Namespaces\DcTerms::CREATOR, \OpenSkos2\Namespaces\Rdf::TYPE);
    $properties = $concept->getProperties();
    foreach ($properties as $key => $values) {
      if (count($values) > 0) {
        if ($values[0] instanceof \OpenSkos2\Rdf\Uri) {
          if (!in_array($key, $nonrelationproperties)) {
            foreach ($values as $value) {
              $messages= $this->existenceCheck($value, Concept::TYPE);
              if (count($messages) === 0) {
                continue;
              }
              if ($this -> softConceptRelationValidation) {
                $this->warningMessages[]= $messages[0] . " Consult the list of dangling references for correction. "; 
                $this->danglingReferences[]=$value->getUri();
              } else {
                $this->errorMessages[]=$messages[0]; 
              }
            }
          }
        }
      }
    }
   return (count($this->errorMessages) === 0);
  }

}
