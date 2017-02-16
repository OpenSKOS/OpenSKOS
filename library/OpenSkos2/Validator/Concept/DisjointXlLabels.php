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
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\ConceptManager;
use OpenSkos2\Exception\OpenSkosException;

class DisjointXlLabels extends AbstractConceptValidator implements ResourceManagerAware
{
    
    use ResourceManagerAwareTrait;
    
    /**
     * Validate if a concept's xl labels are pairwise disjoint as stated in
     * https://www.w3.org/TR/skos-reference/#xl-Label
     *
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if (!$this->resourceManager instanceof ConceptManager) {
            throw new OpenSkosException('Resource manager expected to be concept manager. Given ' . get_class($this->resourceManager));
        }
        
        $concept->loadFullXlLabels($this->resourceManager->getLabelManager());
        
        $xlLabelPredicates = [
            \OpenSkos2\Namespaces\SkosXl::PREFLABEL,
            \OpenSkos2\Namespaces\SkosXl::ALTLABEL,
            \OpenSkos2\Namespaces\SkosXl::HIDDENLABEL,
        ];
        
        $literalForms = [];
        
        foreach ($xlLabelPredicates as $predicate) {
            $labels = $concept->getProperty($predicate);
            foreach ($labels as $label) {
                
                /* @var $literalForm Literal */
                $literalForm = $label->getProperty(SkosXl::LITERALFORM)[0];
                
                if ($literalForm->isInArray($literalForms)) {
                    $this->errorMessages[] =
                            "Skos-xl Literal Form \"{$literalForm->getValue()}\" "
                            . "is present more than once in concept <{$concept->getUri()}>";
                    return false;
                }
                
                $literalForms[] = $literalForm;
            }
        }
        
        return true;
    }
}
