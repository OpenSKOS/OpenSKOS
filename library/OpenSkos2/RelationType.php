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
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;

class RelationType extends Resource
{

    const TYPE = Owl::OBJECT_PROPERTY;

    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
        $this->addProperty(Rdfs::SUBPROPERTY_OF, new Uri(Skos::RELATED));
    }
    
   /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param \OpenSkos2\Tenant $tenant
     * @param \OpenSkos2\Set $set
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param \OpenSkos2\LabelManager | null  $labelManager
     * @param  \OpenSkos2\Rdf\Resource | null $existingResource, optional $existingResource of one of concrete child types used for update
     * override for a concerete resources when necessary
     */
     public function ensureMetadata(
        \OpenSkos2\Tenant $tenant, 
        \OpenSkos2\Set $set = null, 
        \OpenSkos2\Person $person = null ,
        \OpenSkos2\PersonManager $personManager = null, 
        \OpenSkos2\SkosXl\LabelManager $labelManager = null, 
        $existingConcept = null, 
        $forceCreationOfXl = false)
    {

        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };

        $forFirstTimeInOpenSkos = [
            DcTerms::PUBLISHER => new Uri($tenant->getUri()),
            DcTerms::DATESUBMITTED => $nowLiteral
        ];

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }

        $this->resolveCreator($person, $personManager);

        $this->setModified($person);

    }

}
