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

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\Validator\DependencyAware\TenantAware;
use OpenSkos2\Validator\DependencyAware\TenantAwareTrait;

class UniquePreflabelInScheme extends AbstractConceptValidator implements ResourceManagerAware, TenantAware
{
    use ResourceManagerAwareTrait;
    use TenantAwareTrait;

    /**
     * Ensure the preflabel does not already exists in the scheme
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $schemes = $concept->getProperty(Skos::INSCHEME);
        $preflabel = $concept->getProperty(Skos::PREFLABEL);
        foreach ($preflabel as $label) {
            foreach ($schemes as $scheme) {
                if ($this->labelExistsInScheme($concept, $label, $scheme)) {
                    $this->errorMessages[] = 'The pref label already exists in that concept scheme.';
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if the preflabel already exists in scheme
     *
     * @param Concept $concept
     * @param \OpenSkos2\Rdf\Literal $label
     * @param \OpenSkos2\Rdf\Uri $scheme
     * @return boolean
     */
    private function labelExistsInScheme(Concept $concept, \OpenSkos2\Rdf\Literal $label, \OpenSkos2\Rdf\Uri $scheme)
    {
        $uri = null;
        if (!$concept->isBlankNode()) {
            $uri = $concept->getUri();
        }

        $ntriple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $escapedLabel = $ntriple->serialize($label);
        $escapedScheme = $ntriple->serialize($scheme);

        $query = '
                ?subject <'.Skos::PREFLABEL.'> ' . $escapedLabel . ' .
                ?subject <'.Skos::INSCHEME.'> ' . $escapedScheme . ' .
                ?subject <'.OpenSkos::STATUS .'> ?status
                FILTER(
                    ?subject != ' . $ntriple->serialize($concept) . '
                        && (
                            ?status = \''.Concept::STATUS_CANDIDATE.'\' 
                            || ?status = \''.Concept::STATUS_APPROVED.'\'
                            || ?status = \''.Concept::STATUS_REDIRECTED.'\'
                        )
                )';

        return $this->resourceManager->ask($query);
    }
}
