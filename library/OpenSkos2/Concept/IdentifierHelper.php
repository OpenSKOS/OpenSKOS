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
namespace OpenSkos2\Concept;

use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Concept;
use OpenSkos2\Tenant;
use OpenSkos2\ConceptManager;
use OpenSkos2\Rdf\Literal;
use Rhumsaa\Uuid\Uuid;

class IdentifierHelper
{
    /**
     * @var Tenant
     */
    protected $tenant;
    
    /**
     * @var ConceptManager
     */
    protected $conceptManager;
    
    /**
     * @param Tenant $tenant
     * @param ConceptManager $conceptManager
     */
    public function __construct(Tenant $tenant, ConceptManager $conceptManager)
    {
        $this->tenant = $tenant;
        $this->conceptManager = $conceptManager;
    }

    /**
     * Generate notation unique per tenant. Based on tenant notations sequence.
     * @param Concept &$concept
     */
    public function generateNotation(Concept &$concept)
    {
        // @TODO A raise condition is possible. The validation will fail in that case - so should not be problem.
        //     B.Hillier. I think you meant 'Race Condition'. Is certainly possible; I've seen it happen.
        
        $notation = 1;
        
        $maxNumericNotation = $this->conceptManager->fetchMaxNumericNotationFromIndex($this->tenant);
        if (!empty($maxNumericNotation)) {
            $notation = $maxNumericNotation + 1;
        }

        $concept->addProperty(
            Skos::NOTATION,
            new Literal($notation)
        );
    }
    
    /**
     * Generates an uri for the concept.
     * Requires a URI from to an openskos collection
     *
     * @param Concept &$concept
     * @return string The generated uri.
     * @throws UriGenerationException
     */
    public function generateUri(Concept &$concept)
    {
        if (!$concept->isBlankNode()) {
            throw new UriGenerationException(
                'The concept already has an uri. Can not generate new one.'
            );
        }
        
        if ($concept->isPropertyEmpty(OpenSkos::SET)) {
            throw new UriGenerationException(
                'Property openskos:set (collection) is required to generate concept uri.'
            );
        }
        
        if ($concept->isPropertyEmpty(Skos::NOTATION) && $this->tenant->isNotationAutoGenerated()) {
            $this->generateNotation($concept);
        }
        
        if ($concept->isPropertyEmpty(Skos::NOTATION)) {
            $uri = self::assembleUri(
                $concept->getPropertySingleValue(OpenSkos::SET),
                $concept->getPropertySingleValue(OpenSkos::UUID)
            );
        } else {
            $uri = self::assembleUri(
                $concept->getPropertySingleValue(OpenSkos::SET),
                $concept->getPropertySingleValue(OpenSkos::UUID),
                $concept->getProperty(Skos::NOTATION)[0]->getValue()
            );
        }
        if ($this->conceptManager->askForUri($uri, true)) {
            throw new UriGenerationException(
                'The generated uri "' . $uri . '" is already in use.'
            );
        }
        
        $concept->setUri($uri);
        return $uri;
    }
    
    /**
     * Generates concept uri from collection and notation
     * @param string $setUri
     * @param string $collectionUuid, optional: The generated UUID for this collection
     * @param string $firstNotation , optional. New uuid will be used if empty
     * @return string
     */
    protected function assembleUri($setUri, $collectionUuid = null, $firstNotation = null)
    {
        $separator = '/';
        
        $setUri = rtrim($setUri, $separator);

        $uuidToPossiblyUse = $collectionUuid ? $collectionUuid : Uuid::uuid4();
        
        if (empty($firstNotation)) {
            $uri = $setUri . $separator . $uuidToPossiblyUse;
        } else {
            $uri = $setUri . $separator . $firstNotation;
        }
        
        return $uri;
    }
}
