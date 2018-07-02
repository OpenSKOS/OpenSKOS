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
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\AbstractConceptValidator;

class CycleBroaderAndNarrower extends AbstractConceptValidator
{

    /**
     * Validate if a concept will make a cyclic relationship, this is supported by SKOS
     * but was not supported in OpenSKOS this validator provides a way to restrict it in a similar way
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $broaderTerms = $this->buildArray($concept->getProperty(Skos::BROADER));
        $narrowerTerms = $this->buildArray($concept->getProperty(Skos::NARROWER));

        $duplicate = array_intersect($broaderTerms, $narrowerTerms);

        if (empty($duplicate)) {
            return true;
        }

        $this->errorMessages[] = 'Duplicate found in broader and narrower';
        return false;
    }

    /**
     * Build array with strings
     *
     * @param array $values
     * @return array
     */
    private function buildArray($values)
    {
        $new = [];
        foreach ($values as $val) {
            $new[] = (string) $val;
        }
        return $new;
    }
}
