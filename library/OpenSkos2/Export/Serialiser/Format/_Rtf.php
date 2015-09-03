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

namespace OpenSkos2\Export\Serialiser\Format;

use OpenSkos2\Export\Serialiser\FormatAbstract;

// @TODO Refactor or change completely.
// @TODO getConcepts
// @TODO fieldsToExport
class Rtf extends FormatAbstract
{
    /**
     * Holds an array of field - title map for the fields when used in rtf export.
     * @var array
     */
    protected $rtfFieldsTitlesMap = array(
        'uuid' => 'UUID',
        'uri' => 'URI',
        'broader' => 'BT',
        'narrower' => 'NT',
        'related' => 'RT',
        'exampleNote' => 'Voorbeeld:',
        'dcterms_dateSubmited' => 'DS',
        'dcterms_dateAccepted' => 'DA',
        'dcterms_modified' => 'DM',
        'dcterms_creator' => 'C',
    );
    
    /**
     * Exports in rtf format. Wrtites the result to the stream.
     *
     * @param long $streamHandle
     */
    public function export($streamHandle)
    {
        $view = new Zend_View();
        $view->setBasePath(APPLICATION_PATH . '/editor/views/');
        $template = 'export/rtf.phtml';

        // Writes the header of the rtf.
        $view->assign('renderHeader', true)->assign('renderBody', false)->assign('renderFooter', false);
        fwrite($streamHandle, $view->render($template));

        // Writes the concepts data. The body of the rtf.
        // Gets fields which will be exported.
        $fieldsToExport = $this->get('fieldsToExport');
        if (empty($fieldsToExport)) {
            $fieldsToExport = self::getExportableConceptFields();
        }

        $step = self::CONCEPTS_EXPORT_STEP;
        $start = 0;
        $hasMore = false;
        do {
            $concepts = $this->_getConcepts($start, $step, $hasMore);

            if (!empty($concepts)) {
                $conceptsData = array();
                foreach ($concepts as $concept) {
                    $conceptsData[] = $this->prepareConceptDataForRtf($concept, $fieldsToExport);
                }

                $view->data = $conceptsData;
                $view->assign('renderHeader', false)->assign('renderBody', true)->assign('renderFooter', false);
                fwrite($streamHandle, $view->render($template));
            }

            $start += $step;
        } while ($hasMore);

        // Writes the footer of the rtf.
        $view->assign('renderHeader', false)->assign('renderBody', false)->assign('renderFooter', true);
        fwrite($streamHandle, $view->render($template));
    }

    /**
     * Prepares concept data for exporting in rtf format.
     * 
     * @param Api_Models_Concept $concept
     * @param array $fieldsToExport
     * @return array The result concept data
     */
    protected function prepareConceptDataForRtf(Api_Models_Concept $concept, $fieldsToExport)
    {
        $conceptData = array();
        $conceptData['previewLabel'] = $this->constructRtfFieldData('previewLabel', $concept->getPreviewLabel());
        $conceptData['fields'] = array();

        // Prepares concept schemes titles map
        $schemesUris = array();
        $schemesFields = Api_Models_Concept::$classes['ConceptSchemes'];
        foreach ($schemesFields as $schemeField) {
            if (in_array($schemeField, $fieldsToExport) && !empty($concept[$schemeField])) {
                $schemesUris = array_merge($schemesUris, $concept[$schemeField]);
            }
        }
        $schemesUris = array_unique($schemesUris);
        $schemesTitleMap = $this->_getApiClientInstance()->getConceptSchemeMap('uri', array('dcterms_title' => 0), $schemesUris);

        // Prepares related concepts map
        $relatedConceptsUris = array();
        $relationFields = array_merge(Api_Models_Concept::$classes['SemanticRelations'], Api_Models_Concept::$classes['MappingProperties']);
        foreach ($relationFields as $relationField) {
            if (in_array($relationField, $fieldsToExport) && !empty($concept[$relationField])) {
                $relatedConceptsUris = array_merge($relatedConceptsUris, $concept[$relationField]);
            }
        }
        $relatedConceptsUris = array_unique($relatedConceptsUris);
        $relatedConceptsMap = Api_Models_Concepts::factory()->getEnumeratedConceptsMapByUris($relatedConceptsUris);

        // Prepare language dependant fields. Remove the orginal field from fields to export and adds each per language field (field@en for example).
        $allConceptFields = $concept->getFields();
        $fieldsToExportInLanguages = array();
        foreach ($allConceptFields as $currentConceptField) {
            if (preg_match('/^([^@]+)@([^@]+)$/i', $currentConceptField, $matches) && in_array($matches[1], $fieldsToExport)) {
                if (!isset($fieldsToExportInLanguages[$matches[1]])) {
                    $fieldsToExportInLanguages[$matches[1]] = array();
                }
                $fieldsToExportInLanguages[$matches[1]][] = $matches[2];
            }
        }

        // Goes trought each export field
        foreach ($fieldsToExport as $field) {

            if (isset($concept[$field])) {

                if (isset($fieldsToExportInLanguages[$field])) {

                    foreach ($fieldsToExportInLanguages[$field] as $language) {
                        foreach ($concept[$field . '@' . $language] as $value) {
                            $conceptData['fields'][] = $this->constructRtfFieldData($field, $value, $language);
                        }
                    }
                } else if (is_array($concept[$field])) {

                    foreach ($concept[$field] as $value) {

                        if (in_array($field, $schemesFields) && isset($schemesTitleMap[$value])) {
                            $value = $schemesTitleMap[$value];
                        } else if (in_array($field, $relationFields) && isset($relatedConceptsMap[$value])) {
                            $value = $relatedConceptsMap[$value]->getPreviewLabel();
                        }

                        $conceptData['fields'][] = $this->constructRtfFieldData($field, $value);
                    }
                } else {
                    $conceptData['fields'][] = $this->constructRtfFieldData($field, $concept[$field]);
                }
            }
        }

        // Get concept children (narrowers)
        if ($this->get('maxDepth') > 1) {
            $narrowers = $this->getRtfNarrowers(new Editor_Models_Concept($concept), 1);
            if (!empty($narrowers)) {
                $conceptData['narrowers'] = $narrowers;
            }
        }

        return $conceptData;
    }

    /**
     * Get the narrowers of the concept prepared for rtf.
     * 
     * @param Editor_Models_Concept $concept
     */
    protected function getRtfNarrowers($concept, $depthLevel)
    {
        $result = array();
        $narrowers = $concept->getNarrowers();
        foreach ($narrowers as $key => $narrowerConcept) {
            $narrowerConceptData = array();
            $narrowerConceptData['previewLabel'] = $this->constructRtfFieldData(
                'previewLabel',
                $narrowerConcept->getPreviewLabel()
            );

            if ($depthLevel < ($this->get('maxDepth') - 1)) {
                $narrowerConceptNarrowers = $this->getRtfNarrowers($narrowerConcept, $depthLevel + 1);
                if (!empty($narrowerConceptNarrowers)) {
                    $narrowerConceptData['narrowers'] = $narrowerConceptNarrowers;
                }
            }
            $result[] = $narrowerConceptData;
        }
        return $result;
    }

    /**
     * Returns an array of the data needed to create field in the rtf export.
     *
     * @param string $field
     * @param string $value
     * @param string $language, optional
     * @param array $children, optional
     */
    protected function constructRtfFieldData($field, $value, $language = '', $children = array())
    {
        // Field title
        $result = array();
        if (isset($this->rtfFieldsTitlesMap[$field])) {
            $result['fieldTitle'] = $this->rtfFieldsTitlesMap[$field];
        } else {
            $fieldTitleParts = preg_split('/(?=[A-Z])/', $field);
            $result['fieldTitle'] = '';
            foreach ($fieldTitleParts as $part) {
                $result['fieldTitle'] .= strtoupper(substr($part, 0, 1));
            }
        }

        // Field value (escape)
        $result['value'] = str_replace('\\', '\\\\', $value);
        $result['value'] = str_replace('{', '\\{', $result['value']);
        $result['value'] = str_replace('}', '\\}', $result['value']);
        $result['value'] = $this->utf82rtf($result['value']);

        // Language
        $result['language'] = $language;

        // Children
        if (!empty($children)) {
            $result['children'] = $children;
        }

        return $result;
    }

    /**
     * Converts the unicode chars inside the given utf8 text to rtf replacements.
     * 
     * @param string $utf8_text
     * @return string
     */
    protected function utf82rtf($utf8_text)
    {
        $utf8_text = str_replace("\n", "\\par\n", str_replace("\r", "\n", str_replace("\r\n", "\n", $utf8_text)));
        return preg_replace_callback("/([\\xC2-\\xF4][\\x80-\\xBF]+)/", array($this, 'fixUnicodeForRtf'), $utf8_text);
    }

    /**
     * Use for callback in utf82rtf's preg_replace_callback.
     * 
     * @param array $matches
     * @return string
     */
    protected function fixUnicodeForRtf($matches)
    {
        return '\u' . hexdec(bin2hex(iconv('UTF-8', 'UTF-16BE', $matches[1]))) . '?';
    }
}
