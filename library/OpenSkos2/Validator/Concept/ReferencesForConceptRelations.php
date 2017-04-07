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
   $properties = $concept->getProperties();
    $errors = [];
    foreach ($properties as $key => $values) {
      if (count($values) > 0) {
        if ($values[0] instanceof \OpenSkos2\Rdf\Uri) {
          if (!in_array($key, $nonrelationproperties)) {
            $this->validateProperty($concept, $key, false, false, false, false, Concept::TYPE);
            $errors = array_merge($errors, $this->getErrorMessages());
          }
        }
      }
    }

    $this->errorMessages = $errors;
    return (count($errors) === 0);
  }

}
