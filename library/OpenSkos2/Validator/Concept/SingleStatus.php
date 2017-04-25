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
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\AbstractConceptValidator;

class SingleStatus extends AbstractConceptValidator
{

    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $statusses = $concept->getProperty(OpenSkos::STATUS);
        if (count($statusses) > 1) {
            $this->errorMessages[] = 'Only single status is allowed.';
            return false;
        }
        if (count($statusses) < 1) {
            $this->errorMessages[] = ' An obligatory field status is absent. ';
            return false;
        }
        if (count($statusses) > 0) {
            $status = $statusses[0]->getValue();
            if (strtolower($status) !== $status) {
                $this->errorMessages[] = 'Status ' . $status . ' is not valid since it must be all lowercase: ' . strtolower($status);
                return false;
            }
        }
        return true;
    }
}
