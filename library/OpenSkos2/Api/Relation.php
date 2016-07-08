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
use OpenSkos2\RelationManager;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

require_once dirname(__FILE__) .'/../config.inc.php';

class Relation extends AbstractTripleStoreResource {

    
    public function __construct(RelationManager $manager)
    {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
    public function mapNameSearchID() {
        $index =  $this->manager->fetchConceptConceptRelationsNameUri();
        return $index;
    }
    
    public function findAllPairsForRelation($request) {
        //public function findAllPairsForType(ServerRequestInterface $request)
        $params=$request->getQueryParams();
        $relType = $params['id'];
        $sourceSchemata = null;
        $targetSchemata = null;
        if (isset($params['sourceSchemata'])) {
            $sourceSchemata = $params['sourceSchemata'];
        };
        if (isset($params['targetSchemata'])) {
            $targetSchemata = $params['targetSchemata'];
        }; 
        try {
            $response = $this->manager->fetchAllConceptConceptRelationsOfType($relType, $sourceSchemata, $targetSchemata);
            $intermediate = $this->manager->createOutputRelationTriples($response);
            $result = new JsonResponse2($intermediate);
            return $result;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }
    
    public function findRelatedConcepts($request, $uri, $format) {
        $params=$request->getQueryParams();
        $relType = $params['id'];
        if (isset($params['inSchema'])) {
            $schema = $params['inSchema'];
        } else {
            $schema = null;
        }
        try {
            $concepts = $this->manager->fetchRelationsForConcept($uri, $relType, $schema);
            $result = new ResourceResultSet($concepts, $concepts->count(), 0, MAXIMAL_ROWS);
            switch ($format) {
            case 'json':
                $response = (new JsonResponse($result, []))->getResponse();
                break;
            case 'jsonp':
                $response = (new JsonpResponse($result, $params['callback'], []))->getResponse();
                break;
            case 'rdf':
                $response = (new RdfResponse($result, []))->getResponse();
                break;
            default:
                throw new  ApiException('Invalid context: ' . $format, 400);
        }
              return $response;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }
    

    // used when creating a user-defined relation
    protected function checkResourceIdentifiers(PsrServerRequestInterface $request, $resourceObject) {
        if ($resourceObject->isBlankNode()) {
            throw new ApiException(
            'Uri (rdf:about) is missing from the xml. For user relations you must supply it, autogenerateIdentifiers is set to false compulsory.', 400
            );
        }

       $ttl = $resourceObject->getUri();
       $hakje = strrpos($ttl, "#");
       if (strpos($ttl, 'http://') !== 0 || !$hakje || ($hakje === strlen($ttl)-1)) {
            throw new ApiException('The user-defined relation uri must have the form <namespace>#<name> where <namespace> starts with http:// and name is not empty.', 400);
        
       }
        // do not generate idenitifers
        return false;
    }
   
}
