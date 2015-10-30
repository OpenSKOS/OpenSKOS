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

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Concept;
use OpenSkos2\Export\Serialiser\FormatAbstract;
use OpenSkos2\Export\Serialiser\Exception\RequiredPropertiesListException;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\DcTerms;

// @TODO remove scripts and zend dependency.
class Rtf extends FormatAbstract
{
    /**
     * Holds an array of field - title map for the fields when used in rtf export.
     * @var array
     */
    protected $rtfFieldsTitlesMap = array(
        'uuid' => 'UUID',
        'uri' => 'URI',
        Skos::BROADER => 'BT',
        Skos::NARROWER => 'NT',
        Skos::RELATED => 'RT',
        Skos::EXAMPLE => 'Voorbeeld:',
        DcTerms::DATESUBMITTED => 'DS',
        DcTerms::DATEACCEPTED => 'DA',
        DcTerms::MODIFIED => 'DM',
        DcTerms::CREATOR => 'C',
    );
    
    /**
     * @var Zend_View
     */
    protected $view;
    
    /**
     * @var string
     */
    protected $template;
    
    public function __construct()
    {
        // @TODO Di
        // @TODO Do we want to be dependent on zend here.
        $this->view = new \Zend_View();
        $this->view->setBasePath(__DIR__);
        
        $this->template = 'rtf.phtml';
    }
    
    /**
     * Gets the array of properties to be serialised.
     * @return array
     * @throws RequiredPropertiesListException
     */
    public function getPropertiesToSerialise()
    {
        if (empty($this->propertiesToSerialise)) {
            throw new RequiredPropertiesListException(
                'Properties to serialise are not specified. Can not export to csv.'
            );
        }
        return $this->propertiesToSerialise;
    }
    
    /**
     * Creates the header of the output.
     * @return string
     */
    public function printHeader()
    {
        $this->view
            ->assign('renderHeader', true)
            ->assign('renderBody', false)
            ->assign('renderFooter', false);
        
        return $this->view->render($this->template);
    }
    
    /**
     * Serialises a single resource.
     * @return string
     */
    public function printResource(Resource $resource)
    {
        $this->view->data = [
            $this->prepareResourceDataForRtf($resource)
        ];
                
        $this->view
            ->assign('renderHeader', false)
            ->assign('renderBody', true)
            ->assign('renderFooter', false);
        
        return $this->view->render($this->template);
    }
    
    /**
     * Creates the footer of the output.
     * @return string
     */
    public function printFooter()
    {
        $this->view
            ->assign('renderHeader', false)
            ->assign('renderBody', false)
            ->assign('renderFooter', true);
        
        return $this->view->render($this->template);
    }
    
    /**
     * Prepares concept data for exporting in rtf format.
     *
     * @param Resource $resource
     * @return array The result resource data
     */
    protected function prepareResourceDataForRtf(Resource $resource)
    {
        $resourceData = [];
        
        // Adds title
        $previewLabel = $resource->getUri();
        if ($resource instanceof Concept) {
            // @TODO Add language support.
            $previewLabel = $resource->getCaption();
        }
        $resourceData['previewLabel'] = $this->constructRtfFieldData('previewLabel', $previewLabel);
        
        // Adds rest of fields.
        $resourceData['fields'] = [];
        foreach ($this->getPropertiesToSerialise() as $predicate) {
            if ($predicate == 'uri') {
                $resourceData['fields'][] = $this->constructRtfFieldData(
                    $predicate,
                    $resource->getUri()
                );
            } else {
                foreach ($resource->getProperty($predicate) as $property) {
                    if ($property instanceof Literal) {
                        $resourceData['fields'][] = $this->constructRtfFieldData(
                            $predicate,
                            $property->getValue(),
                            $property->getLanguage()
                        );
                    } elseif ($property instanceof Uri) {
                        $resourceData['fields'][] = $this->constructRtfFieldData(
                            $predicate,
                            $this->resolveUri($property)
                        );
                    }
                }
            }
        }
        
        // @TODO Resolve narrowers. Related to issue #22757
//        // Get concept children (narrowers)
//        if ($this->get('maxDepth') > 1) {
//            $narrowers = $this->getRtfNarrowers(new Editor_Models_Concept($concept), 1);
//            if (!empty($narrowers)) {
//                $conceptData['narrowers'] = $narrowers;
//            }
//        }

        return $resourceData;
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
            $shortenField = Namespaces::shortenProperty($field);
            
            $fieldTitleParts = preg_split(
                '/(?=[A-Z])/',
                substr($shortenField, strpos($shortenField, ':') + 1)
            );
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
    
    
    

    /**
     * Get the narrowers of the concept prepared for rtf.
     * @TODO refs #22757
     * @param Editor_Models_Concept $concept
     */
    protected function getRtfNarrowers($concept, $depthLevel)
    {
//        $result = array();
//        $narrowers = $concept->getNarrowers();
//        foreach ($narrowers as $key => $narrowerConcept) {
//            $narrowerConceptData = array();
//            $narrowerConceptData['previewLabel'] = $this->constructRtfFieldData(
//                'previewLabel',
//                $narrowerConcept->getPreviewLabel()
//            );
//
//            if ($depthLevel < ($this->get('maxDepth') - 1)) {
//                $narrowerConceptNarrowers = $this->getRtfNarrowers($narrowerConcept, $depthLevel + 1);
//                if (!empty($narrowerConceptNarrowers)) {
//                    $narrowerConceptData['narrowers'] = $narrowerConceptNarrowers;
//                }
//            }
//            $result[] = $narrowerConceptData;
//        }
//        return $result;
    }
    
    
    /**
     * @TODO Make such method globally
     * @param Uri $uri
     */
    protected function resolveUri(Uri $uri)
    {
        return $uri->getUri();
        // @TODO Concept schemes and relations
//        // Prepares concept schemes titles map
//        $schemesUris = array();
//        $schemesFields = Api_Models_Concept::$classes['ConceptSchemes'];
//        foreach ($schemesFields as $schemeField) {
//            if (in_array($schemeField, $fieldsToExport) && !empty($concept[$schemeField])) {
//                $schemesUris = array_merge($schemesUris, $concept[$schemeField]);
//            }
//        }
//  $schemesUris = array_unique($schemesUris);
//  $schemesTitleMap = $this->_getApiClientInstance()->getConceptSchemeMap(
//      'uri', 
//      array('dcterms_title' => 0), 
//      $schemesUris
//  );
//
//        
//        // Prepares related concepts map
//        $relatedConceptsUris = array();
//        $relationFields = array_merge(Api_Models_Concept::$classes['SemanticRelations'], 
//              Api_Models_Concept::$classes['MappingProperties']
//        );
//        foreach ($relationFields as $relationField) {
//            if (in_array($relationField, $fieldsToExport) && !empty($concept[$relationField])) {
//                $relatedConceptsUris = array_merge($relatedConceptsUris, $concept[$relationField]);
//            }
//        }
//        $relatedConceptsUris = array_unique($relatedConceptsUris);
//        $relatedConceptsMap = Api_Models_Concepts::factory()->getEnumeratedConceptsMapByUris($relatedConceptsUris);
    }
}
