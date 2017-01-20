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

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;

class CycleInBroader extends AbstractConceptValidator implements ResourceManagerAware
{

    use ResourceManagerAwareTrait;

    /**
     * Validate if a concept will make a cyclic relationship, this is supported by SKOS
     * but was not supported in OpenSKOS this validator provides a way to restrict it in a similar way
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $broaderTerms = $concept->getProperty(Skos::BROADER);

        if (empty($broaderTerms)) {
            return true;
        }

        $uri = new Uri($concept->getUri());

        $query = '?broader skos:broader+ ' . (new NTriple())->serialize($uri) . PHP_EOL
                . ' FILTER(?broader IN (' . (new NTriple())->serializeArray($broaderTerms) . '))';

        if ($this->resourceManager->ask($query)) {
            $this->errorMessages[] = "Cyclic broader relation detected for concept: {$concept->getUri()}";

            return false;
        }

        return true;
    }
}
