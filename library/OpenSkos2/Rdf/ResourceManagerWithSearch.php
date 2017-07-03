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

namespace OpenSkos2\Rdf;

use OpenSkos2\EasyRdf\Sparql\Client;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Solr\ResourceManager as SolrResourceManager;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Namespaces\SkosXl;

// @TODO Include resource type in insert/delete/search. Now we know it is only concepts

class ResourceManagerWithSearch extends ResourceManager
{

    /**
     * @var \OpenSkos2\Solr\ResourceManager
     */
    protected $solrResourceManager;

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @return bool
     */
    public function getIsNoCommitMode()
    {
        return $this->solrResourceManager->getIsNoCommitMode();
    }

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @param bool
     */
    public function setIsNoCommitMode($isNoCommitMode)
    {
        $this->solrResourceManager->setIsNoCommitMode($isNoCommitMode);
    }

    /**
     * @param Client $client
     * @param SolrResourceManager $solrResourceManager
     */
    public function __construct(Client $client, SolrResourceManager $solrResourceManager)
    {
        parent::__construct($client);
        $this->solrResourceManager = $solrResourceManager;
    }

    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @throws ResourceAlreadyExistsException
     */
    public function insert(Resource $resource)
    {
        parent::insert($resource);
        $this->solrResourceManager->insert($resource);
    }

    /**
     * @param \OpenSkos2\Rdf\ResourceCollection $resourceCollection
     * @throws ResourceAlreadyExistsException
     */
    public function insertCollection(ResourceCollection $resourceCollection)
    {
        parent::insertCollection($resourceCollection);
        $this->solrResourceManager->insertCollection($resourceCollection);
    }

    /**
     * @param \OpenSkos2\Rdf\ResourceCollection $resourceCollection
     * @throws ResourceAlreadyExistsException
     */
    public function addToCollection(ResourceCollection $resourceCollection)
    {
        parent::addToCollection($resourceCollection);
        $this->solrResourceManager->insertCollection($resourceCollection);
    }

    /**
     * Deletes and then inserts the resourse.
     * @param \OpenSkos2\Rdf\Resource $resource
     */
    public function replace(Resource $resource)
    {
        parent::replace($resource);
        if ($resource->getType()->getUri() === \OpenSkos2\Concept::TYPE) {
            $this->solrResourceManager->delete($resource);
            $this->solrResourceManager->insert($resource);
        }
    }

    /**
     * @param \OpenSkos2\Rdf\ResourceCollection $resourceCollection
     * @throws ResourceAlreadyExistsException
     */
    public function replaceCollection(ResourceCollection $resourceCollection)
    {
        parent::replaceCollection($resourceCollection);
        $this->solrResourceManager->insertCollection($resourceCollection);
    }

    /**
     * @param Uri $resource
     */
    public function delete(Uri $resource)
    {
        if ($this->resourceType === \OpenSkos2\Concept::TYPE) {
            $this->client->update("DELETE WHERE {<{$resource->getUri()}> <" . SkosXl::PREFLABEL . "> ?object . "
                . "?object ?predicate2 ?object2 .}");
            $this->client->update("DELETE WHERE {<{$resource->getUri()}> <" . SkosXl::ALTLABEL . "> ?object . "
                . "?object ?predicate2 ?object2 .}");
            $this->client->update("DELETE WHERE {<{$resource->getUri()}> <" . SkosXl::HIDDENLABEL . "> ?object . "
                . "?object ?predicate2 ?object2 .}");
        }
        parent::delete($resource);
        $this->solrResourceManager->delete($resource);
    }

    /**
     * Perform a full text query
     * lucene / solr queries are possible
     * for the available fields see schema.xml
     *
     * @param string $query
     * @param int $rows
     * @param int $start
     * @param int &$numFound output Total number of found records.
     * @param array $sorts
     * @return ResourceCollection
     */
    public function search($query, $rows = 20, $start = 0, &$numFound = 0, $sorts = null)
    {
        $filterQueries = null;

        if (!empty($this->resourceType)) {
            $filterQueries = [
                'rdfTypeFilter' => 's_rdfType:"' . $this->resourceType . '"'
            ];
        }

        return $this->fetchByUris(
            $this->solrResourceManager->search($query, $rows, $start, $numFound, $sorts, $filterQueries)
        );
    }

    /**
     * Commit the transaction
     */
    public function commit()
    {
        $this->solrResourceManager->commit();
    }
}
