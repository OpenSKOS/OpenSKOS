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

namespace OpenSkos2\Api\Transform;

use DateTime;
use EasyRdf\RdfNamespace;
use OpenSkos2\FieldsMaps;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Validator\Resource;

require_once dirname(__FILE__) .'/../../config.inc.php';
/**
 * Transform Resource to a php array with only native values to encode as json output.
 * Provide backwards compatability to the API output from OpenSKOS 1 as much as possible
 */
class DataArray
{
    /**
     * @var Resource
     */
    private $resource;
    
    /**
     * @var array
     */
    private $propertiesList;
    
    /**
     * @param Resource $resource
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(RdfResource $resource, $propertiesList = null)
    {
        $this->resource = $resource;
        $this->propertiesList = $propertiesList;
    }
    
    /**
     * Transform the
     *
     * @return array
     */
    // TODO: refactor to use transform, which is more universal
    public function transformConcept()
    {
        $resource = $this->resource;
        
        /* @var $resource Resource */
        $newResource = [];
        if ($this->doIncludeProperty('uri')) {
            $newResource['uri'] = $resource->getUri();
        }
        
        foreach (self::getFieldsPlusIsRepeatableMap() as $field => $prop) {
            //var_dump($field);
            //var_dump($prop);
            //var_dump('uri: ' . $prop['uri']);
            if (!$this->doIncludeProperty($prop['uri'])) {
                //var_dump($prop['uri']);
                continue;
            }
            
            $data = $resource->getProperty($prop['uri']);
            //var_dump('data ' . $data);
            if (empty($data)) {
                continue;
            }
            $newResource = $this->getPropertyValue($data, $field, $prop, $newResource);
        }
         
        return $newResource;
    }
    
     public function transform()
    {
        $newResource = $this->transformRecursive($this->resource);
        return $newResource;
    }
    public function transformRecursive(RdfResource $rdfresource) {
        $newResource = [];
        $newResource['uri'] = $rdfresource->getUri();
        foreach ($rdfresource->getProperties() as $field => $properties) {
            
            if (!$this->doIncludeProperty($field)) {
                continue;
            }
           
            if (OMIT_JSON_REFICES) {
                $parts = RdfNamespace::splitUri($field);
                $short = $parts[1];
            } else {
                $short = RdfNamespace::shorten($field);
            }
            $newResource = $this->getPropertyValue($properties, $short, array('repeatable' => false), $newResource);
        }
        return $newResource;
    }

    /**
     * Should the property be included in the serialized data.
     * @param string $property
     * @return bool
     */
    protected function doIncludeProperty($property)
    {
        return empty($this->propertiesList) || in_array($property, $this->propertiesList);
    }
    
    /**
     * Get data from property
     *
     * @param array $prop
     * @param array $settings
     * #param string $field field name to map
     * @param array $resource
     * @return array
     */
    private function getPropertyValue(array $prop, $field, $settings, $resource)
    {
        foreach ($prop as $val) {
            if ($val instanceof RdfResource) {
                $resource[$field] = $this->transformRecursive($val);
                continue;
            }

            // Some values only have a URI but not getValue or getLanguage
            if ($val instanceof Uri && !method_exists($val, 'getLanguage')) {
                //var_dump($resource);
                if ($settings['repeatable'] === true) {
                    $resource[$field][] = $val->getUri();
                } else {
                    //var_dump($resource);
                    $resource[$field] = $val->getUri();
                }
                continue;
            }

            $value = $val->getValue();

            if ($value instanceof DateTime) {
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
            
            if ($settings['repeatable'] === true) {
                $resource[$langField][] = $value;
            } else {
                $resource[$langField] = $value;
            }
        }
        
        return $resource;
    }
    
    /**
     * Gets map of fields to properties. Including info for if a field is repeatable.
     * @return array
     */
    public static function getFieldsPlusIsRepeatableMap()
    {
        $notRepeatable = [
            DcTerms::CREATOR,
            DcTerms::DATESUBMITTED,
            DcTerms::DATEACCEPTED,
            DcTerms::MODIFIED,
            DcTerms::TITLE,
            OpenSkos::ACCEPTEDBY,
            OpenSkos::DELETEDBY,
            OpenSkos::DATE_DELETED,
            OpenSkos::STATUS,
            OpenSkos::TENANT,
            OpenSkos::SET,
            OpenSkos::UUID,
            OpenSkos::TOBECHECKED,
            Skos::PREFLABEL,
        ];
        
        $map = [];
        // Olha: it is obsolete after successful migration
        //foreach (FieldsMaps::getOldToProperties() as $field => $property) {
        foreach (FieldsMaps::getNamesToProperties() as $field => $property) {
            $map[$field] = [
                'uri' => $property,
                'repeatable' => !in_array($property, $notRepeatable),
            ];
        }
        return $map;
    }
}
