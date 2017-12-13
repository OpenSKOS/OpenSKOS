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
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\SkosXl\LabelCollection;
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Exception\TenantNotFoundException;
use OpenSkos2\Tenant;

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
     * @param bool $forceCreationOfXl , optional, Default: false
     * @throws OpenSkosException
     */
    public function assertLabels(Concept &$concept, $forceCreationOfXl = false)
    {
        /* @var $tenant OpenSKOS_Db_Table_Row_Tenant */
        $tenant = $concept->getInstitution();
        if (empty($tenant)) {
            throw new TenantNotFoundException(
                'Could not determine tenant for concept.'
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
                    if ($labelValue->isUriTempGenerated()) {
                        $labelValue->setUri(Label::generateUri());
                    }
                    $fullXlLabels[] = $labelValue;
                    continue;
                }

                $labelExists = $this->labelManager->askForUri($labelValue->getUri());

                if (!$labelExists && !($labelValue instanceof Label)) {
                    throw new OpenSkosException(
                        'The label ' . $labelValue . ' is not a fully described label resource '
                        . 'and does not exist in the system.'
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
            if ($useXlLabels === false || $forceCreationOfXl) {
                foreach ($concept->getProperty($simpleLabelProperty) as $simpleLabel) {
                    if (!$simpleLabel->isInArray($xlLabelsLiterals)) {
                        $label = new Label(Label::generateUri());
                        $label->setProperty(SkosXl::LITERALFORM, $simpleLabel);
                        $label->ensureMetadata();

                        $concept->addProperty($xlLabelProperty, $label);

                        $xlLabelsLiterals[] = $simpleLabel;
                    }
                }
            }

            $concept->setProperties($simpleLabelProperty, $xlLabelsLiterals);
        }
    }

    /**
     * Insert any xl labels for the concept which do not exist yet.
     * Meant to be called together with insert of the concept.
     * @param Concept $concept
     * @param bool $returnOnly , optional, default: false.
     *  Set to true if the labels have to be returned only. Not inserted. Existing labels still will be deleted.
     * @throws OpenSkosException
     */
    public function insertLabels(Concept $concept)
    {
        $inserAndDelete = $this->getLabelsForInsertAndDelete($concept);

        foreach ($inserAndDelete['delete'] as $deleteLabel) {
            $this->labelManager->delete($deleteLabel);
        }

        $this->labelManager->insertCollection($inserAndDelete['insert']);
    }

    /**
     * Gets collections of labels to insert and to delete.
     * @param Concept $concept
     * @return ['delete' => $deleteLabels, 'insert' => LabelCollection]
     * @throws OpenSkosException
     */
    public function getLabelsForInsertAndDelete($concept)
    {
        $deleteLabels = new LabelCollection([]);
        $insertlabels = new LabelCollection([]);

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
                        'The label ' . $label . ' is not a fully described label resource '
                        . 'and does not exist in the system.'
                    );
                }

                if (!$label instanceof Label) {
                    continue; // It is just an uri - nothing to do with it.
                }

                $label->ensureMetadata();

                // Fetch, insert or replace label
                if ($labelExists) {
                    $deleteLabels->append($label);
                }

                $insertlabels->append($label);
            }
        }

        return [
            'delete' => $deleteLabels,
            'insert' => $insertlabels,
        ];
    }

    /**
     * Creates a new label using the parameters and inserts it into the DB
     * @param string $literalForm
     * @param string $language
     * @param Tenant $tenant
     * @return Label
     * @throws OpenSkosException
     */
    public function createNewLabel($literalForm, $language, Tenant $tenant)
    {
        if (empty($literalForm) || empty($language) || empty($tenant)) {
            throw new OpenSkosException('LiteralForm Language and Tenant must be specified when creating a new label.');
        }

        $rdfLiteral = new Literal($literalForm);
        $rdfLiteral->setLanguage($language);

        $label = new Label(Label::generateUri());
        $label->addProperty(SkosXl::LITERALFORM, $rdfLiteral);
        $label->ensureMetadata();

        $this->labelManager->insert($label);

        return $label;
    }
}
