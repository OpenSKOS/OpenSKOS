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

class RequriedPrefLabel extends AbstractConceptValidator
{

    /**
     * Ensure the preflabel does not already exists in the scheme
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $enabledSkosXl =$this->tenant->isEnableSkosXl();
        if ($enabledSkosXl) {
            return true;
        }
        $languages = $concept->retrieveLanguages();
        foreach ($languages as $language) {
            $prefLabel = $concept->retrievePropertyInLanguage(Skos::PREFLABEL, $language);
            if (!count(array_filter($prefLabel))) {
                $this->errorMessages[] = 'Prefered label is required in all languages.';
                return false;
            }
        }
        return true;
    }
}
