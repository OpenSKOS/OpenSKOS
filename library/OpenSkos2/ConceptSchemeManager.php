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

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Resource;

class ConceptSchemeManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = ConceptScheme::TYPE;
    
    /**
     * Soft delete resource , sets the openskos:status to deleted
     * and add a delete date.
     *
     * Be careful you need to add the full resource as it will be deleted and added again
     * do not only give a uri or part of the graph
     *
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param Uri $user
     */
    public function deleteSoft(Resource $resource, Uri $user = null)
    {
        $this->delete($resource);
        
        $resource->setUri(rtrim($resource->getUri(), '/') . '/deleted');
        
        $resource->setProperty(OpenSkos::STATUS, new Literal(\OpenSkos2\Concept::STATUS_DELETED));
        $resource->setProperty(OpenSkos::DATE_DELETED, new Literal(date('c'), null, Literal::TYPE_DATETIME));

        if ($user) {
            $resource->setProperty(OpenSkos::DELETEDBY, $user);
        }

        $this->replace($resource);
    }

    //TODO: check conditions when it can be deleted
    public function canBeDeleted($uri)
    {
        return parent::CanBeDeleted($uri);
    }

    /**
     * Get all scheme's by set URI
     *
     * @param string $setUri e.g http://openskos.org/api/collections/rce:TEST
     * @param array $filterUris
     * @return ResourceCollection
     */
    public function getSchemeBySetUri($setUri, $filterUris = [])
    {
        $uri = new Uri($setUri);
        $escaped = (new NTriple())->serialize($uri);
        $query = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX openskos: <http://openskos.org/xmlns#>
            PREFIX dcterms: <http://purl.org/dc/terms/>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                SELECT ?subject ?title ?uuid
                WHERE {
                    ?subject rdf:type skos:ConceptScheme;
                    <' . OpenSkos::SET . '> ' . $escaped . ';
                    dcterms:title ?title;
                    openskos:uuid ?uuid;
            ';

        if (!empty($filterUris)) {
            $query .= 'FILTER (?subject = '
                . implode(' || ?subject = ', array_map([$this, 'valueToTurtle'], $filterUris))
                . ')';
        }

        $query .= '}';

        $result = $this->query($query);

        $retVal = new ResourceCollection();
        foreach ($result as $row) {
            $uri = $row->subject->getUri();

            if (empty($uri)) {
                continue;
            }

            $scheme = new ConceptScheme($uri);
            if (!empty($row->title)) {
                $scheme->addProperty(DcTerms::TITLE, new Literal($row->title->getValue()));
            }

            if (!empty($row->uuid)) {
                $scheme->addProperty(\OpenSkos2\Namespaces\OpenSkos::UUID, new Literal($row->uuid->getValue()));
            }

            $scheme->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, new Uri($setUri));

            $retVal[] = $scheme;
        }

        return $retVal;
    }
}
