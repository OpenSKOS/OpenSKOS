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

namespace OpenSkos2\Solr;

use Solarium\Client;
use OpenSkos2\Rdf\Resource;

class ResourceManager
{
    /**
     * @var Client
     */
    protected $solr;
    
    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @var bool
     */
    protected $isNoCommitMode = false;

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @return bool
     */
    public function getIsNoCommitMode()
    {
        return $this->isNoCommitMode;
    }

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @param bool
     */
    public function setIsNoCommitMode($isNoCommitMode)
    {
        $this->isNoCommitMode = $isNoCommitMode;
    }
    
    /**
     * @param Client $solr
     */
    public function __construct(Client $solr)
    {
        $this->solr = $solr;
    }
    
    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @throws type
     */
    public function insert(Resource $resource)
    {
        $update = $this->solr->createUpdate();
        $doc = $update->createDocument();
        $convert = new \OpenSkos2\Solr\Document($resource, $doc);
        $resourceDoc = $convert->getDocument();
        
        $update->addDocument($resourceDoc);
        if (!$this->getIsNoCommitMode()) {
            $update->addCommit(true);
        }
        
        
        // Sometimes solr update fails with timeout.
        $exception = null;
        $tries = 0;
        $maxTries = 3;
        do {
            try {
                $exception = null;
                $result = $this->solr->update($update);
            } catch (\Solarium\Exception\HttpException $exception) {
                $tries ++;
            }
        } while ($exception !== null && $tries < $maxTries);
        
        if ($exception !== null) {
            throw $exception;
        }
    }
    
    /**
     * @param Resource $uri
     */
    public function delete(\OpenSkos2\Rdf\Uri $uri)
    {
        // delete resource in solr
        $update = $this->solr->createUpdate();
        $update->addDeleteById($uri->getUri());
        
        if (!$this->getIsNoCommitMode()) {
            $update->addCommit(true);
        }
        
        $this->solr->update($update);
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
     * @return array Array of uris
     */
    public function search($query, $rows = 20, $start = 0, &$numFound = 0, $sorts = null)
    { 
        $select = $this->solr->createSelect();
        $select->setStart($start)
                ->setRows($rows)
                ->setFields(['uri'])
                ->setQuery($query);
        
        if (!empty($sorts)) {
            $select->setSorts($sorts);
        }
        
        $solrResult = $this->solr->select($select);
        $numFound = $solrResult->getNumFound();
        
        $uris = [];
        foreach ($solrResult as $doc) {
            $uris[] = $doc->uri;
        }
        
        return $uris;
    }
    
    /**
     * Get the max value of a single value field
     * @param string $field Get the max value of a single value field
     * @return string
     */
    public function getMaxFieldValue($query, $field)
    {
        // Solarium brakes stat results when we have long int, so we use ordering.
        
        $select = $this->solr->createSelect()
            ->setQuery($query)
            ->setRows(1)
            ->addSort($field, 'desc')
            ->addField($field);
        
        $solrResult = $this->solr->select($select);
        
        return $solrResult->getIterator()->current()->{$field};
    }
}
