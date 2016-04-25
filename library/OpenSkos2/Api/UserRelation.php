<?php

/*
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

namespace OpenSkos2\Api;

use Exception;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\ConceptManager;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Owl;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;

require_once dirname(__FILE__) .'/../config.inc.php';

class UserRelation extends AbstractTripleStoreResource {

    use \OpenSkos2\Api\Response\ApiResponseTrait;
    
    public function __construct(ConceptManager $manager)
    {
        $this->manager = $manager;
    }
    
      // specific content validation
     protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       //must be new
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Owl::OBJECT_PROPERTY);
       
    }
    
    
    // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
       // must not occur as another collection's name if different from the old one 
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Owl::OBJECT_PROPERTY);
    
    }
    
    
    public function findAllPairsForUserRelationType($request) {
        //public function findAllPairsForType(ServerRequestInterface $request)
        $params=$request->getQueryParams();
        $relType = $params['q'];
        $namespace = $params['namespace'];
        $sourceSchemata = null;
        $targetSchemata = null;
        if (isset($params['sourceSchemata'])) {
            $sourceSchemata = $params['sourceSchemata'];
        };
        if (isset($params['targetSchemata'])) {
            $targetSchemata = $params['targetSchemata'];
        }; 
        try {
            $response = $this->manager->fetchAllRelationsOfType($namespace, $relType, $sourceSchemata, $targetSchemata);
            //var_dump($response);
            $intermediate = $this->createOutputRelationTriples($response);
            $result = new JsonResponse2($intermediate);
            return $result;
        } catch (Exception $exc) {
            if ($exc instanceof ApiException) {
                return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
            } else {
                return $this->getErrorResponse(500, $exc->getMessage());
            }
        }
    }
    
    
 
    public function listAllUserRelations(){
         $intermediate = $this->manager->getUserRelationUris();
         $result = new JsonResponse2($intermediate);
         return $result;
    }
    
 
  
}
