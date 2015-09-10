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

namespace OpenSkos2\Export\Serialiser;

class FormatFactory
{
    /**#@+
     * All possible formats
     */
    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';
    const FORMAT_RTF = 'rtf';
    /**#@-*/

    /**
     * Gets supported formats.
     *
     * @return array
     */
    public static function getFormats()
    {
        $formats = array();
        $formats[self::FORMAT_XML] = 'Xml';
        $formats[self::FORMAT_CSV] = 'Csv';
        $formats[self::FORMAT_RTF] = 'Rtf';

        return $formats;
    }
    
    /**
     * @param string $format
     * @param array $propertiesToSerialise
     * @param array $namespaces
     * @return \OpenSkos2\Export\Serialiser\Format\Xml
     * @throws \RuntimeException
     */
    public static function create($format, $propertiesToSerialise = [], $namespaces = [], $maxDepth = 1)
    {
        // @TODO Allow all easyrdf formats.
        switch ($format) {
            case self::FORMAT_CSV:
                $formatObject = new \OpenSkos2\Export\Serialiser\Format\Csv();
                break;
            case self::FORMAT_RTF:
                $formatObject = new \OpenSkos2\Export\Serialiser\Format\Rtf();
                break;
            case self::FORMAT_XML:
                $formatObject = new \OpenSkos2\Export\Serialiser\Format\Xml();
                break;
            default:
                throw new \RuntimeException('Not supported format "' . $this->format . '"');
        }
        
        // @TODO not all require properties, namespaces and max depth. Validate what is required and what not.
        $formatObject->setPropertiesToSerialise($propertiesToSerialise);
        $formatObject->setNamespaces($namespaces);
        $formatObject->setMaxDepth($maxDepth);
        
        return $formatObject;
    }
}
