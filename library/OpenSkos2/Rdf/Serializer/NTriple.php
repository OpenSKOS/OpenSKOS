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

namespace OpenSkos2\Rdf\Serializer;

class NTriple
{

    /**
     * Serialize an array when retrieving data from
     *
     * @param array $property
     * @return string
     */
    public function serializeArray($property)
    {
        $values = [];
        foreach ($property as $object) {
            $values[] = $this->serialize($object);
        }

        return implode(', ', $values);
    }

    /**
     * Make sure the data is returned in valid ntriple format
     *
     * @param \OpenSkos2\Rdf\Serializer\OpenSkos2\Rdf\Object $object
     * @return string
     */
    public function serialize(\OpenSkos2\Rdf\Object $object)
    {
        $serializer = new \EasyRdf\Serialiser\Ntriples();

        if ($object instanceof \OpenSkos2\Rdf\Literal) {
            return $serializer->serialiseValue([
                    'type' => 'literal',
                    'value' => $object->getValue(),
                    'lang' => $object->getLanguage()
            ]);
        } elseif ($object instanceof \OpenSkos2\Rdf\Uri) {
            return $serializer->serialiseValue([
                    'type' => 'uri',
                    'value' => $object->getUri()
            ]);
        } else {
            throw new Exception\InvalidArgumentException('Invalid object: ' . get_class($object));
        }
    }

    /**
     *
     * @return \OpenSkos2\Rdf\Serializer\NTriple
     */
    public static function getInstance()
    {
        return new self();
    }
}
