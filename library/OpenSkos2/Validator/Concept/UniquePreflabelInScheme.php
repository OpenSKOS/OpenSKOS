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
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\AbstractConceptValidator;

class UniquePreflabelInScheme extends AbstractConceptValidator
{
    
    /**
     * Ensure the preflabel does not already exists in the scheme
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        return $this->validateProperty($concept, Skos::PREFLABEL, true, true, false, true);
        
    }
    
    /**
     * Check if the preflabel already exists in scheme
     *
     * @param Concept $concept
     * @param string $label
     * @param string $scheme
     * @return boolean
     */
    private function labelExistsInScheme(Concept $concept, $label, $scheme)
    {
        $uri = null;
        if (!$concept->isBlankNode()) {
            $uri = $concept->getUri();
        }
        return $this->resourceManager->askForMatch(
            [
                [
                    'operator' => \OpenSkos2\Sparql\Operator::EQUAL,
                    'predicate' => Skos::PREFLABEL,
                    'value' => $label
                ],
                [
                    'operator' => \OpenSkos2\Sparql\Operator::EQUAL,
                    'predicate' => Skos::INSCHEME,
                    'value' => $scheme
                ]
            ],
            $uri
        );
    }
}
