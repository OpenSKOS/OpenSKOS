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

use OpenSkos2\Tenant;
use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;

class UniqueNotationInTenant extends UniqueNotation
{
    /**
     * @var Tenant
     */
    protected $tenant;
    
    /**
     * @param Tenant $tenant
     */
    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }
    
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if ($concept->hasProperty(Skos::NOTATION)) {
            $patterns = $this->notationsPattern($concept);
            $patterns .= PHP_EOL;
            $patterns .= $this->notSameConceptPattern($concept);
            
            $hasOther = $this->resourceManager->ask($patterns);

            if ($hasOther) {
                $this->errorMessage = 'The concept notation must be unique per tenant. '
                    . 'There is other concept with same notation in the same tenant.';
                return false;
            }
        }

        return true;
    }
}
