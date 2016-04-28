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
use OpenSkos2\Namespaces\Owl;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;

class Relation extends Resource
{
    const TYPE = Owl::OBJECT_PROPERTY;
    
   
     public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
        $this->addProperty(Rdfs::SUBPROPERTY_OF, new Uri(Skos::RELATED));
    }
    
    
    
    public function addMetadata($user, $params, $oldParams) {
       $metadata = [];
       $nowLiteral = function () {
                return new Literal(date('c'), null, Literal::TYPE_DATETIME);
            };
       $userUri = $user->getFoafPerson()->getUri();
       if (count($oldParams) === 0) { // a completely new resource under creation
             $metadata = [
                DcTerms::CREATOR => new Uri($userUri),
                DcTerms::DATESUBMITTED => $nowLiteral(),
            ];
        } else {
            $metadata = [
                DcTerms::CREATOR => new Uri($oldParams['creator']),
                DcTerms::DATESUBMITTED => new Literal($oldParams['dateSubmitted'], null, Literal::TYPE_DATETIME),
                DcTerms::MODIFIED =>  $nowLiteral()
            ];
        }
        foreach ($metadata as $property => $defaultValue) {
            $this->setProperty($property, $defaultValue);
        }
        if (count($oldParams) >0) {
         $this -> addProperty(DcTerms::CONTRIBUTOR, new Uri($userUri));
        }
    }
}
