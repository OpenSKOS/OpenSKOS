<?php

/*
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

class LanguageRequired extends AbstractConceptValidator
{
    /**
     * Validate if the properties which are normally translated have a language
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $translatedProperties = array_merge(
            Concept::$classes['LexicalLabels'],
            Concept::$classes['DocumentationProperties']
        );
        
        $isValid = true;
        
        foreach ($translatedProperties as $property) {
            foreach ($concept->getProperty($property) as $value) {
                if ($value->getLanguage() === null || $value->getLanguage() === '') {
                    $this->errorMessages[] = sprintf(
                        'Language is required for property "%s" value "%s"',
                        $property,
                        $value->getValue()
                    );
                    $isValid = false;
                }
            }
        }
        
        return $isValid;
    }
}
