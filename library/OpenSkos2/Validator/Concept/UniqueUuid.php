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
use OpenSkos2\Sparql\Operator;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;

class UniqueUuid extends AbstractConceptValidator implements ResourceManagerAware
{
    use ResourceManagerAwareTrait;

    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $uuids=$concept->getProperty(OpenSkos::UUID);
        if (count($uuids)<1) {
            $this->errorMessages[] = 'Uuid is not given';
            return false;
        } ;
        
     
        
        $params = [];
        $params[] = [
            'operator' => Operator::EQUAL,
            'predicate' => OpenSkos::UUID,
            'value' => $uuids[0],
        ];
        
        $hasOther = $this->getResourceManager()->askForMatch(
            $params,
            $concept->getUri()
        );

        if ($hasOther) {
            $this->errorMessages[] = 'Uuid (openskos:uuid) must be unique. There is other concept with same uuid.';
            return false;
        } else {
            return true;
        }
    }
}
