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

namespace OpenSkos2\Validator;

use OpenSkos2\UserRelation;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractUserRelationValidator extends AbstractResourceValidator
{
   
    public function validate(RdfResource $resource)
    {
        if ($resource instanceof UserRelation) {
            return $this->validateUserRelation($resource);
        }
        return false;
    }

    abstract protected function validateUserRelation(Set $set);
}
