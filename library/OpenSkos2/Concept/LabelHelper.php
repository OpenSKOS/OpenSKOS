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

use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\Exception\OpenSkosException;

class LabelHelper
{
    /**
     * @var LabelManager
     */
    protected $labelManager;
    
    /**
     * @param LabelManager $labelManager
     */
    public function __construct(LabelManager $labelManager)
    {
        $this->labelManager = $labelManager;
    }

    /**
     * Dump down all xl labels to simple labels.
     * Create xl label for each simple label which is not already presented as xl label.
     * @param Concept &$concept
     * @throws OpenSkosException
     */
    public function assertLabels(Concept &$concept)
    {
        //@TODO Where we handle the complete labels xml...
        //@TODO Can optimize by making 1 request to jena for all labels
        
        $tenant = $concept->getInstitution();
        if (empty($tenant)) {
            throw new OpenSkosException(
                'Could not determite tenant for concept.'
            );
        }
        
        $useXlLabels = (bool)$tenant['enableSkosXl'];
        
        foreach (Concept::$labelsMap as $xlLabelProperty => $simpleLabelProperty) {
            $fullXlLabels = [];
            foreach ($concept->getProperty($xlLabelProperty) as $labelValue) {
                if (!$labelValue instanceof Uri) {
                    throw new OpenSkosException(
                        'Not a valid xl label provided.'
                    );
                }

                if ($labelValue instanceof Label) {
                    $fullXlLabels[] = $labelValue;
                    continue;
                }

                $labelExists = $this->labelManager->askForUri($labelValue->getUri());

                if (!$labelExists && !($labelValue instanceof Label)) {
                    throw new OpenSkosException(
                        'The label ' . $labelValue . ' is not a valid label resource and does not exist in the system.'
                    );
                }

                $fullXlLabels[] = $this->labelManager->fetchByUri($labelValue);
            }

            // Extract all literals to compare agains simple labels
            $xlLabelsLiterals = [];
            foreach ($fullXlLabels as $label) {
                $xlLabelsLiterals[] = $label->getPropertySingleValue(SkosXl::LITERALFORM);
            }

            // Create xl label for any simple label which does not have matching one.
            // Do this only if skos xl labels are disabled, i.e. simple labels are primary.
            if ($useXlLabels === false) {
                foreach ($concept->getProperty($simpleLabelProperty) as $simpleLabel) {
                    if (!$simpleLabel->isInArray($xlLabelsLiterals)) {
                        $label = new Label(Label::generateUri());
                        $label->setProperty(SkosXl::LITERALFORM, $simpleLabel);

                        $concept->addProperty($xlLabelProperty, $label);

                        $xlLabelsLiterals[] = $simpleLabel;
                    }
                }
            }

            // Dumbing down xl labels to simple labels.
            // Match all simple labels to the existing xl labels.
            $concept->setProperties($simpleLabelProperty, $xlLabelsLiterals);
        }
    }
    
    /**
     * Insert any xl labels for the concept which do not exist yet.
     * Meant to be called together with insert of the concept.
     * @param Concept $concept
     * @throws OpenSkosException
     */
    public function insertLabels(Concept $concept)
    {
        //@TODO Can we have labels without uri here...
        //@TODO What we do with deleted concepts
        //@TODO What we do with updated concepts which leave hanging labels (not attached to other concept)
        //@TODO Can we insert them as one graph together with the full concept. What will happen with existing labels
        
        foreach (array_keys(Concept::$labelsMap) as $xlLabelProperty) {
            // Loop through xl labels
            foreach ($concept->getProperty($xlLabelProperty) as $label) {
                if (!$label instanceof Uri) {
                    throw new OpenSkosException(
                        'Not a valid xl label provided.'
                    );
                }
                
                $labelExists = $this->labelManager->askForUri($label->getUri());
                
                if (!$labelExists && !($label instanceof Label)) {
                    throw new OpenSkosException(
                        'The label ' . $label . ' is not a valid label resource and does not exist in the system.'
                    );
                }
                
                if (!$label instanceof Label) {
                    continue; // It is just an uri - nothing to do with it.
                }
                
                // Fetch, insert or replace label
                if ($labelExists) {
                    $this->labelManager->replace($label);
                } else {
                    $this->labelManager->insert($label);
                }
            }
        }
    }
}
