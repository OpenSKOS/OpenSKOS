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

use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Uri;
/**
 * Representation of tenant.
 */
class Tenant extends Resource
{
    
    const TYPE = Org::FORMALORG;

    
    

    public function __construct($uri = null) {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    public function addMetadata($userUri, $params, $existingTenant) {
        $metadata = [];
        if ($existingTenant !== null) {
            if (count($this->getProperty(OpenSkos::UUID)) < 1) {
                $metadata = [OpenSkos::UUID => new Literal($existingTenant->getUuid())];
            }
        }
        foreach ($metadata as $property => $defaultValue) {
            $this->setProperty($property, $defaultValue);
        }
    }

}
