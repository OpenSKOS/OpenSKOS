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
use OpenSkos2\Validator\ConceptValidator;

class UniqueNotation extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if (!$concept->isPropertyEmpty(Skos::NOTATION)) {
            $patterns = $this->notationsPattern($concept);
            $patterns .= PHP_EOL;
            $patterns .= $this->schemesPattern($concept);
            $patterns .= PHP_EOL;
            $patterns .= $this->notSameConceptPattern($concept);
            
            $hasOther = $this->resourceManager->ask($patterns);

            if ($hasOther) {
                $this->errorMessage = 'The concept notation must be unique per concept scheme. '
                    . 'There is other concept with same notation in one of the concept schemes.';
                return false;
            }
        }

        return true;
    }
    
    /**
     * Pattern to match any of the notations of the concept.
     * @param Concept $concept
     * @return string
     */
    protected function notationsPattern(Concept $concept)
    {
        $pattern = '?subject <' . Skos::NOTATION . '> ?notation';
        $pattern .= PHP_EOL;
        $pattern .= 'FILTER (?notation IN (\'' . implode('\', \'', $concept->getProperty(Skos::NOTATION)) . '\'))';
        
        return $pattern;
    }
    
    /**
     * Pattern to match any of the schemes of the concept.
     * @param Concept $concept
     * @return string
     */
    protected function schemesPattern(Concept $concept)
    {
        $pattern = '?subject <' . Skos::INSCHEME . '> ?scheme';
        $pattern .= PHP_EOL;
        $pattern .= 'FILTER (?scheme IN (<' . implode('>, <', $concept->getProperty(Skos::INSCHEME)) . '>))';
        
        return $pattern;
    }
    
    /**
     * Filter to not match the concept which we validate.
     * @param Concept $concept
     * @return type
     */
    protected function notSameConceptPattern(Concept $concept)
    {
        $pattern = '';
        $uri = $concept->getUri();
        if (!empty($uri)) {
            $pattern = 'FILTER (?subject != <' . $uri . '>)';
        }
        return $pattern;
    }
}
