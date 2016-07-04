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

use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractResourceValidator implements ValidatorInterface
{
    protected $resourceManager;
    protected $resurceType;
    protected $forUpdate;
    protected $tenantCode;
    /**
     * @var array
     */
    protected $errorMessages = [];
    
    
    public function setResourceManager($resourceManager) {
        if ($resourceManager === null) {
            throw new Exception("Passed resource manager is null in this validator. Proper content validation is not possible");
        }
        $this->resourceManager = $resourceManager;
    }

    public function setFlagIsForUpdate($isForUpdate) {
        if ($isForUpdate === null) {
            throw new Exception("Cannot validate the resource because isForUpdateFlag is set to null (cannot differ between create- and update- validation mode.");
        }
        $this->forUpdate = $isForUpdate;
    }

    /**
     * @param $resource RdfResource
     * @return boolean
     */
    abstract public function validate(RdfResource $resource); // switcher

    
    /**
     * @return string
     */
    public function getErrorMessages() {
       
        return $this->errorMessages;
    }

}
