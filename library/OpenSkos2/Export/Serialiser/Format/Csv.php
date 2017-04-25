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
use OpenSkos2\Export\Serialiser\FormatAbstract;
use OpenSkos2\Export\Serialiser\Exception\RequiredPropertiesListException;

class Csv extends FormatAbstract
{

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
        // @TODO Beautify properties
        return $this->stringPutCsv(
            array_map(['OpenSkos2\Namespaces', 'shortenProperty'], $this->getPropertiesToSerialise())
        );
    }

    /**
     * Serialises a single resource.
     * @return string
     */
    public function printResource(Resource $resource)
    {
        return $this->stringPutCsv($this->prepareResourceDataForCsv($resource));
    }

    /**
     * Creates the footer of the output.
     * @return string
     */
    public function printFooter()
    {
        return '';
    }

    /**
     * Prepare concept data for exporting in csv format.
     *
     * @param Api_Models_Concept $concept
     * @param array $propertiesToExport
     * @return array The result concept data
     */
    protected function prepareResourceDataForCsv(Resource $resource)
    {
        $resourceData = array();

        foreach ($this->getPropertiesToSerialise() as $property) {
            if ($property == 'uri') { // @TODO Something more clean?
                $resourceData[$property] = $resource->getUri();
            } elseif ($resource->hasProperty($property)) {
                $values = $resource->getProperty($property);
                if (count($values) > 1) {
                    $resourceData[$property] = implode(';', $values);
                } else {
                    $resourceData[$property] = (string) $values[0];
                }
            } else {
                $resourceData[$property] = '';
            }
        }

        return $resourceData;
    }

    /**
     * Puts csv in string.
     * @param array $data
     * @return string
     */
    public function stringPutCsv($data)
    {
        $streamHandle = fopen('php://memory', 'rw');
        fputcsv($streamHandle, $data);
        rewind($streamHandle);
        $result = stream_get_contents($streamHandle);
        fclose($streamHandle);
        return $result;
    }
}
