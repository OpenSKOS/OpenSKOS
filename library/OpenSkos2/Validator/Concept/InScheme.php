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

class InScheme extends AbstractConceptValidator
{
    protected function validateConcept(Concept $concept)
    {
        //B.Hillier: 26 June 2018. Restored Pre-Meertens test.
        // The Meertens test also checked against the DB. This failed if the data had not been committed to Jena yet.
        if (!count($concept->getProperty(Skos::INSCHEME))) {
            $this->errorMessages[] = 'The concept must be included in at least one scheme.';
            return false;
        }
        return true;
    }
}
