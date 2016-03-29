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
    /**
     * @var array
     */
    protected $errorMessages = [];
    
    /**
     * @param $resource RdfResource
     * @return boolean
     */
    abstract public function validate(RdfResource $resource); // switcher

    
    /**
     * @return string
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }
    
    
    protected function genericValidate($callback, $arg1, $arg2=null, $arg3=null, $arg4=null)
    {
        //var_dump($this -> errorMessages);
        $newErrors = call_user_func(__NAMESPACE__ . $callback, $arg1, $arg2, $arg3, $arg4);
        //var_dump($callback);
        //var_dump($newErrors);
        if (count($newErrors) > 0) {
            $this -> errorMessages = array_merge($this -> errorMessages, $newErrors);
            return false;
        } else {
            return true;
        }
    }
    
}
