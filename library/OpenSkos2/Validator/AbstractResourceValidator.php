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

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceManager;

abstract class AbstractResourceValidator implements ResourceValidator
{
    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;
    
    /**
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        // @TODO Maybe we need to split validators which need the resource manager and such which don't
        // @TODO Update the tests
        
        $this->resourceManager = $resourceManager;
    }
    
    /**
     * @param $resource Resource
     */
    abstract public function validate(Resource $resource);

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
