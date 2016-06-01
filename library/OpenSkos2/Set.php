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
namespace OpenSkos2;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;

class Set extends Resource
{
    const TYPE = Dcmi::DATASET;
    
   
   public function getCode() {
        $values = $this->getProperty(OpenSkos::CODE);
        if (isset($values[0])) {
            return $values[0];
        }else{
            return new Literal(UNKNOWN);
        }
    }
   
   
     public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }
    
    
    
    public function addMetadata($user, $params, $oldParams) {
        $metadata = [];
       if (count($oldParams)>0){ 
            $metadata = [
            OpenSkos::UUID => new Literal($oldParams['uuid'])];
        }
        foreach ($metadata as $property => $defaultValue) {
            $this->setProperty($property, $defaultValue);
        }
    }
}
