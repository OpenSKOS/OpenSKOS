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
use OpenSkos2\FieldsMaps;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\Uri;

/**
 * Transform Resource to a php array with only native values to encode as json output.
 * Provide backwards compatability to the API output from OpenSKOS 1 as much as possible
 */
// Meertens: changes of 16/01/2017 are present (previois changes were from 12/03/2016) 
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
    public function transform()
    {
        $resource = $this->resource;

        /* @var $resource Resource */
        $newResource = [];
        if ($this->doIncludeProperty('uri')) {
            $newResource['uri'] = $resource->getUri();
        }

        foreach (self::getFieldsPlusIsRepeatableMap() as $field => $prop) {
            if (!$this->doIncludeProperty($prop['uri'])) {
                continue;
            }
            if ($resource->isPropertyEmpty($prop['uri'])) {
                continue;
            }
            $newResource = $this->getPropertyValue(
                $resource->getProperty($prop['uri']),
                $field,
                $prop,
                $newResource
            );
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
                if (count($val->getProperties()) > 0) {
                    if ($settings['repeatable']) {
                        $resource[$field][] = (new DataArray($val))->transform();
                    } else {
                        $resource[$field] = (new DataArray($val))->transform();
                    }
                    continue;
                }
            }

            // Some values only have a URI but not getValue or getLanguage
            if ($val instanceof Uri && !method_exists($val, 'getLanguage')) {
                if ($settings['repeatable']) {
                    $resource[$field][] = $val->getUri();
                } else {
                    $resource[$field] = $val->getUri();
                }
                continue;
            }

            $value = $val->getValue();
            if ($value instanceof DateTime) {
                $value = $value->format(DATE_W3C);
            }

            if ($value === null || $value === '') {
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
            DcTerms::PUBLISHER,
            DcTerms::LICENSE,
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
            OpenSkos::ENABLESTATUSSESSYSTEMS,
            OpenSkos::DISABLESEARCHINOTERTENANTS,
            OpenSkos::ALLOW_OAI,
            OpenSkos::CONCEPTBASEURI,
            OpenSkos::CODE,
            OpenSkos::OAI_BASEURL,
            OpenSkos::WEBPAGE,
            Rdf::TYPE,
            VCard::ADR,
            VCard::COUNTRY,
            VCard::EMAIL,
            VCard::LOCALITY,
            VCard::NAME_SPACE,
            VCard::ORG,
            VCard::ORGNAME,
            VCard::ORGUNIT,
            VCard::PCODE,
            VCard::STREET,
            VCard::URL,
        ];

        $map = [];
        foreach (FieldsMaps::getNamesToProperties() as $field => $property) {
            $map[$field] = [
                'uri' => $property,
                'repeatable' => !in_array($property, $notRepeatable),
            ];
        }
        return $map;
    }
}
