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

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;

class RelationType extends AbstractTripleStoreResource
{

    public function __construct(
        \OpenSkos2\RelationTypeManager $manager,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->customInit = $this->manager->getCustomInitArray();
        $this->deletionIntegrityCheck = new \OpenSkos2\IntegrityCheck($manager);
        $this->personManager = $personManager;
        $this->limit = $this->customInit['limit'];
    }

   

    public function listRelatedConceptPairs($request)
    {
        $params = $request->fetchUserTenantSetViaRequestParameters();
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
            $response = $this->manager->fetchAllConceptConceptRelationsOfType(
                $relType,
                $sourceSchemata,
                $targetSchemata
            );
            $intermediate = $this->manager->createOutputRelationTriples($response);
            $result = new JsonResponse2($intermediate);
            return $result;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code === null) {
                $code = 500;
            }
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    public function findRelatedConcepts($request, $uri, $format)
    {
        $params = $request->fetchUserTenantSetViaRequestParameters();
        $relType = $params['id'];
        if (isset($params['inScheme'])) {
            $schema = $params['inScheme'];
        } else {
            $schema = null;
        }
        try {
            $customInit = $this->manager->getCustomInitArray();
            if (count($customInit)===0) {
                $maxRows = $this->limit;
            } else {
                $maxRows=$customInit["custom']['maximal_rows"];
            }
            if (isset($params['isTarget'])) {
                if ($params['isTarget'] === 'true') {
                    $isTarget = true;
                } else {
                    if ($params['isTarget'] === 'false') {
                        $isTarget = false;
                    } else {
                        throw new Exception(
                            'Wrong value "' . $params['isTarget'] . '" for parameter isTarget,'
                            . ' must be "true" or "false"'
                        );
                    }
                }
            } else {
                $isTarget = false;
            }
            $concepts = $this->manager->fetchRelatedConcepts($uri, $relType, $isTarget, $schema);

            $result = new ResourceResultSet(
                $concepts,
                $concepts->count(),
                0,
                $maxRows
            );
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
                    throw new \Exception('Invalid context: ' . $format);
            }
            return $response;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code === null) {
                $code = 500;
            }
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    // used when creating an OpenSKOS relation type
    protected function checkResourceIdentifiers(PsrServerRequestInterface $request, $resourceObject)
    {
        if ($resourceObject->isBlankNode()) {
            throw new \Exception(
                'Uri (rdf:about) is missing from the xml. For user relations you must supply it,'
                . ' autogenerateIdentifiers is set to false compulsory.'
            );
        }
        $ttl = $resourceObject->getUri();
        $hakje = strrpos($ttl, "#");
        if (strpos($ttl, 'http://') !== 0 || !$hakje || ($hakje === strlen($ttl) - 1)) {
            throw new \Exception(
                'The user-defined relation uri must have the form <namespace>#<name> '
                . 'where <namespace> starts with http:// and name is not empty.'
            );
        }
        // do not generate idenitifers
        return false;
    }

    protected function getRequiredParameters()
    {
        return ['key', 'tenant'];
    }
}
