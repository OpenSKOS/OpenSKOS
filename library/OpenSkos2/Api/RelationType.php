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
use OpenSkos2\RelationTypeManager;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;

require_once dirname(__FILE__) . '/../config.inc.php';

class RelationType extends AbstractTripleStoreResource
{

    public function __construct(RelationTypeManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }

    public function mapNameSearchID()
    {
        $index = $this->manager->fetchConceptConceptRelationsNameUri();
        return $index;
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
            $response = $this->manager->fetchAllConceptConceptRelationsOfType($relType, $sourceSchemata, $targetSchemata);
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
            if (isset($params['isTarget'])) {
                if ($params['isTarget'] === 'true') {
                    $isTarget = true;
                } else {
                    if ($params['isTarget'] === 'false') {
                        $isTarget = false;
                    } else {
                        throw new Exception('Wrong value "' . $params['isTarget'] . '" for parameter isTarget, must be "true" or "false"');
                    }
                }
            } else {
                $isTarget = false;
            }
            $concepts = $this->manager->fetchRelatedConcepts($uri, $relType, $isTarget, $schema);

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
                    throw new ApiException('Invalid context: ' . $format, 400);
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
            throw new ApiException(
                'Uri (rdf:about) is missing from the xml. For user relations you must supply it, autogenerateIdentifiers is set to false compulsory.',
                400
            );
        }
        $ttl = $resourceObject->getUri();
        $hakje = strrpos($ttl, "#");
        if (strpos($ttl, 'http://') !== 0 || !$hakje || ($hakje === strlen($ttl) - 1)) {
            throw new ApiException('The user-defined relation uri must have the form <namespace>#<name> where <namespace> starts with http:// and name is not empty.', 400);
        }
        // do not generate idenitifers
        return false;
    }
}
