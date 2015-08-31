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

namespace OpenSkos2\Import;


use OpenSkos2\Rdf\Uri;

class Message
{
    /**
     * @var string file to import
     */
    private $file;

    /**
     * @var string status that new imports should have
     */
    private $importedConceptStatus;

    /**
     * @var boolean
     */
    private $ignoreIncomingStatus;

    /**
     * @var string
     */
    private $fallbackLanguage;

    private $deleteSchemes;

    private $clearCollection;

    /**
     * @var Uri
     */
    private $collection;

    /**
     * @var bool Don't import records that already exist
     */
    private $noUpdates;

    /**
     * @var bool
     */
    private $uriAsIdentifier;

    /**
     * @var bool
     */
    private $toBeChecked;
    /**
     * @var bool
     */
    private $uriAsScheme;

    /**
     * Message constructor.
     * @param $file
     * @param Uri $collection
     * @param bool $ignoreIncomingStatus
     * @param string $importedConceptStatus
     * @param bool $noUpdates
     * @param bool $toBeChecked
     * @param string $fallbackLanguage
     * @param bool $clearCollection
     * @param bool $deleteSchemes
     * @param bool $uriAsScheme
     */
    public function __construct(
        $file,
        Uri $collection,
        $ignoreIncomingStatus,
        $importedConceptStatus,
        $noUpdates = false,
        $toBeChecked = false,
        $fallbackLanguage = null,
        $clearCollection = false,
        $deleteSchemes = false,
        $uriAsScheme = false
    ) {
        $this->file = $file;
        $this->collection = $collection;
        $this->ignoreIncomingStatus = $ignoreIncomingStatus;
        $this->importedConceptStatus = $importedConceptStatus;
        $this->noUpdates = $noUpdates;
        $this->toBeChecked = $toBeChecked;
        $this->fallbackLanguage = $fallbackLanguage;
        $this->clearCollection = $clearCollection;
        $this->deleteSchemes = $deleteSchemes;
        $this->uriAsScheme = $uriAsScheme;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return Uri
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return string
     */
    public function getImportedConceptStatus()
    {
        return $this->importedConceptStatus;
    }

    /**
     * @return boolean
     */
    public function getIgnoreIncomingStatus()
    {
        return $this->ignoreIncomingStatus;
    }

    /**
     * @return boolean
     */
    public function getNoUpdates()
    {
        return $this->noUpdates;
    }

    /**
     * @return boolean
     */
    public function getToBeChecked()
    {
        return $this->toBeChecked;
    }

    /**
     * @return string
     */
    public function getFallbackLanguage()
    {
        return $this->fallbackLanguage;
    }

    /**
     * @return boolean
     */
    public function getDeleteSchemes()
    {
        return $this->deleteSchemes;
    }

    /**
     * @return boolean
     */
    public function getClearCollection()
    {
        return $this->clearCollection;
    }

    /**
     * @return bool
     */
    public function getUriAsIdentifier()
    {
        return $this->uriAsIdentifier;
    }


}