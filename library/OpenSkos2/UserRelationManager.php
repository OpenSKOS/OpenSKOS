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

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Owl;
use OpenSkos2\Namespaces\DcTerms;

class UserRelationManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = UserRelation::TYPE;
    
      //TODO: check conditions when it can be deleted
    public function CanBeDeleted($uri){
        return parent::CanBeDeleted($uri);
    }
    
    public function getUserRelationUriNames() {
        $sparqlQuery = 'select ?rel ?name where {?rel <' . Rdf::TYPE . '> <'. Owl::OBJECT_PROPERTY. '> . ?rel <' . DcTerms::TITLE . '> ?name . }';
        //\Tools\Logging::var_error_log(" Query \n", $sparqlQuery, '/app/data/Logger.txt');
        $resource = $this->query($sparqlQuery);
       
        $result =[];
        foreach ($resource as $value) {
            $result[]['uri'] = $value -> rel ->getUri();
            $result[]['name'] = $value -> name ->getValue();
        }
        return $result;
    }
  
}
