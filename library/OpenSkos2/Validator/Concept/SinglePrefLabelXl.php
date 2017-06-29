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
use OpenSkos2\SkosXl\Label;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\ConceptManager;
use OpenSkos2\Exception\OpenSkosException;

class SinglePrefLabelXl extends AbstractConceptValidator
{
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
            $class = get_class($this->resourceManager);
            throw new OpenSkosException('Resource manager expected to be concept manager. Given ' . $class);
        }
        
        $concept->loadFullXlLabels($this->resourceManager->getLabelManager());
        
        $labels = $concept->getProperty(\OpenSkos2\Namespaces\SkosXl::PREFLABEL);
        
        $labelsPerLanguage = [];
        foreach ($labels as $label) {
            /* @var $label Label*/
            $language = $label->getProperty(SkosXl::LITERALFORM)[0]->getLanguage();
            if (isset($labelsPerLanguage[$language])) {
                $this->errorMessages[] =
                    "More than one skos-xl pref labels found in concept <{$concept->getUri()}>";
                return false;
            } else {
                $labelsPerLanguage[$language] = $label;
            }
        }
        
        return true;
    }
}
