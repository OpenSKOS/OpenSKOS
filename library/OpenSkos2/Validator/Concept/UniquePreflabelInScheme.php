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
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\Validator\DependencyAware\TenantAware;
use OpenSkos2\Validator\DependencyAware\TenantAwareTrait;

class UniquePreflabelInScheme extends AbstractConceptValidator implements ResourceManagerAware, TenantAware
{
    use ResourceManagerAwareTrait;
    use TenantAwareTrait;
    
    /**
     * Ensure the preflabel does not already exists in the scheme
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $schemes = $concept->getProperty(Skos::INSCHEME);
        $preflabel = $concept->getProperty(Skos::PREFLABEL);
        foreach ($preflabel as $label) {
            foreach ($schemes as $scheme) {
                if ($this->labelExistsInScheme($concept, $label, $scheme)) {
                    $this->errorMessages[] = 'The pref label already exists in that concept scheme.';
                    return false;
                }
            }
        }
        return true;
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
