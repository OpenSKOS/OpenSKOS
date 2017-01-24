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

use EasyRdf\Sparql\Client;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Solr\ResourceManager as SolrResourceManager;
use OpenSkos2\Rdf\ResourceManager;

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
     * @param Uri $resource
     */
    public function delete(Uri $resource)
    {
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
     * @return ConceptCollection
     */
    public function search($query, $rows = 20, $start = 0, &$numFound = 0, $sorts = null)
    {
        return $this->fetchByUris(
            $this->solrResourceManager->search($query, $rows, $start, $numFound, $sorts)
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
