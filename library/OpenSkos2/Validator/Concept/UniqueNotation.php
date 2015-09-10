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
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\Validator\DependencyAware\TenantAware;
use OpenSkos2\Validator\DependencyAware\TenantAwareTrait;

class UniqueNotation extends ConceptValidator implements ResourceManagerAware, TenantAware
{
    use ResourceManagerAwareTrait;
    use TenantAwareTrait;
    
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if (!$concept->isPropertyEmpty(Skos::NOTATION)) {
            $patterns = $this->notationsPattern($concept);
            $patterns .= PHP_EOL;
            $patterns .= $this->notSameConceptPattern($concept);
            
            if ($this->isUniquePerSchema()) {
                $patterns .= PHP_EOL;
                $patterns .= $this->schemesPattern($concept);
            }
            
            $hasOther = $this->getResourceManager()->ask($patterns);

            if ($hasOther) {
                if ($this->isUniquePerSchema()) {
                    $this->errorMessage = 'The concept notation must be unique per concept scheme. '
                        . 'There is other concept with same notation in one of the concept schemes.';
                } else {
                    $this->errorMessage = 'The concept notation must be unique per tenant. '
                        . 'There is other concept with same notation in the same tenant.';
                }
                
                return false;
            }
        }

        return true;
    }
    
    /**
     * By default validate per scheme unless the tenant requires it unique per tenant.
     * @return bool
     */
    protected function isUniquePerSchema()
    {
        return !$this->getTenant()->isNotationUniquePerTenant();
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
