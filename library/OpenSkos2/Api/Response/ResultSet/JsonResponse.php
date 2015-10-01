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

namespace OpenSkos2\Api\Response\ResultSet;

/**
 * Provide the json output for find-concepts api
 */
class JsonResponse implements \OpenSkos2\Api\Response\ResponseInterface
{

    /**
     * @var \OpenSkos2\Api\ConceptResultSet
     */
    private $result;

    /**
     *
     * @param \OpenSkos2\Api\ConceptResultSet $result
     */
    public function __construct(\OpenSkos2\Api\ConceptResultSet $result)
    {
        $this->result = $result;
    }

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $response = [
            'response' => [
                'numFound' => $this->result->getTotal(),
                'docs' => $this->getDocs()
            ]
        ];

        return new \Zend\Diactoros\Response\JsonResponse($response);
    }

    /**
     * Get docs property response
     *
     * @return array
     */
    private function getDocs()
    {
        $docs = [];

        $map = [
            'created_timestamp' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::CREATED,
                'repeatable' => false
            ],
            'modified_timestamp' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::MODIFIED,
                'repeatable' => false
            ],
            //'approved_timestamp' => ['uri' => \OpenSkos2\Namespaces\DcTerms::MODIFIED, 'repeatable' => false],
            'status' => [
                'uri' => \OpenSkos2\Namespaces\OpenSkos::STATUS,
                'repeatable' => false
            ],
            'tenant' => [
                'uri' => \OpenSkos2\Namespaces\OpenSkos::TENANT,
                'repeatable' => false
            ],
            'collection' => [
                'uri' => \OpenSkos2\Namespaces\OpenSkos::COLLECTION,
                'repeatable' => false
            ],
            'uuid' => [
                'uri' => \OpenSkos2\Namespaces\OpenSkos::UUID,
                'repeatable' => false
            ],
            'prefLabel' => [
                'uri' => \OpenSkos2\Namespaces\Skos::PREFLABEL,
                'repeatable' => false
            ],
            'altLabel' => [
                'uri' => \OpenSkos2\Namespaces\Skos::ALTLABEL,
                'repeatable' => true
            ],
            'related' => [
                'uri' => \OpenSkos2\Namespaces\Skos::RELATED,
                'repeatable' => true
            ],
            'inScheme' => [
                'uri' => \OpenSkos2\Namespaces\Skos::INSCHEME,
                'repeatable' => true
            ],
            'topConceptOf' => [
                'uri' => \OpenSkos2\Namespaces\Skos::TOPCONCEPTOF,
                'repeatable' => true
            ],
            'dcterms_dateAccepted' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::DATEACCEPTED,
                'repeatable' => true
            ],
            'dcterms_modified' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::MODIFIED,
                'repeatable' => true
            ],
            'dcterms_creator' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::CREATOR,
                'repeatable' => true
            ],
            'dcterms_dateSubmitted' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::DATESUBMITTED,
                'repeatable' => true
            ],
            'dcterms_contributor' => [
                'uri' => \OpenSkos2\Namespaces\DcTerms::CONTRIBUTOR,
                'repeatable' => true
            ],
        ];

        foreach ($this->result->getConcepts() as $concept) {
            /* @var $concept \OpenSkos2\Concept */
            $newConcept = [
                'uri' => $concept->getUri()
            ];
            foreach ($map as $field => $prop) {
                $data = $concept->getProperty($prop['uri']);
                if (empty($data)) {
                    continue;
                }
                $newConcept = $this->getPropertyValue($data, $field, $prop, $newConcept);
            }
            $docs[] = $newConcept;
        }

        return $docs;
    }
    
    /**
     * Get data from property
     *
     * @param array $prop
     * @param array $settings
     * #param string $field field name to map
     * @param array $concept
     * @return int|string|array
     */
    private function getPropertyValue(array $prop, $field, $settings, $concept)
    {
        if (isset($prop[0]) && $settings['repeatable'] === false) {
            $lang = $prop[0]->getLanguage();
            if (!empty($lang)) {
                $field = $field . '@' . $lang;
            }
            
            $val = $prop[0]->getValue();
            $concept[$field] = $val;
            
            if ($val instanceof \DateTime) {
                $concept[$field] = $val->format(\DATE_W3C);
            }
        }
        
        if ($settings['repeatable'] === true) {
            foreach ($prop as $val) {
                if ($val instanceof \OpenSkos2\Rdf\Uri) {
                    $concept[$field] = $val->getUri();
                    continue;
                }
                
                $value = $val->getValue();
                
                if ($value instanceof \DateTime) {
                    $value = $value->format(DATE_W3C);
                }
                
                if (empty($value)) {
                    continue;
                }
                $lang = $val->getLanguage();
                $langField = $field;
                if (!empty($lang)) {
                    $langField .= '@' . $lang;
                }
                $concept[$langField][] = $value;
            }
        }
        
        return $concept;
    }
}
