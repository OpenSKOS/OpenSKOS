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
use OpenSkos2\Rdf\Concept;
use OpenSkos2\Validator\AbstractConceptValidator;

class RelatedToSelf extends AbstractConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $relationFields = array_merge(Resource::$classes['SemanticRelations'], Resource::$classes['MappingProperties']);

        $ownUri = $concept->getUri();
        foreach ($relationFields as $field) {
            foreach ($concept->getProperty($field) as $object) {
                if ($object->getUri() == $ownUri) {
                    $this->errorMessages[] =
                        'The concept can not be related to itself or there is a transitive relation cycle.';
                    return false;
                }
            }
        }

        return true;
    }
}
