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
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;

class ConceptSchemeManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = ConceptScheme::TYPE;

    /**
     * Get all scheme's by collection URI
     *
     * @param string $collectionUri e.g http://openskos.org/api/collections/rce:TEST
     * @return ResourceCollection
     */
    public function getSchemeByCollectionUri($collectionUri)
    {
        $uri = new Uri($collectionUri);
        $escaped = (new NTriple())->serialize($uri);
        $query = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX openskos: <http://openskos.org/xmlns#>
            PREFIX dc: <http://purl.org/dc/terms/>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                SELECT ?subject ?title ?uuid
                WHERE {
                    ?subject rdf:type skos:conceptScheme;
                    <' . OpenSkos::SET .  '> ' . $escaped . ';
                    dc:title ?title;
                    openskos:uuid ?uuid;
            }
        ';

        $result = $this->query($query);

        $collection = new ResourceCollection();
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

            $scheme->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, new Uri($collectionUri));

            $collection[] = $scheme;
        }

        return $collection;
    }
}
