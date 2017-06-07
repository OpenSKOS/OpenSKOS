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

use OpenSkos2\Person;
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
    private $clearSet;

    /**
     * @var Uri
     */
    private $setUri;

    /**
     * @var bool Don't import records that already exist
     */
    private $noUpdates;

    /**
     * @var bool
     */
    private $isRemovingDanglingConceptReferencesRound;

    /**
     * @var bool
     */
    private $toBeChecked;

    /**
     * @var Person
     */
    private $person;

    /**
     * Message constructor.
     * @param $person
     * @param $file
     * @param Uri $setUri
     * @param bool $ignoreIncomingStatus
     * @param string $importedConceptStatus
     * @param bool $isRemovingDanglingConceptReferencesRound
     * @param bool $noUpdates
     * @param bool $toBeChecked
     * @param string $fallbackLanguage
     * @param bool $clearSet
     * @param bool $deleteSchemes
     */
    public function __construct(
        $person,
        $file,
        $setUri,
        $ignoreIncomingStatus,
        $importedConceptStatus,
        $isRemovingDanglingConceptReferencesRound,
        $noUpdates = false,
        $toBeChecked = false,
        $fallbackLanguage = null,
        $clearSet = false,
        $deleteSchemes = false
    ) {
        $this->file = $file;
        $this->setUri = $setUri;
        $this->ignoreIncomingStatus = $ignoreIncomingStatus;
        $this->importedConceptStatus = $importedConceptStatus;
        $this->isRemovingDanglingConceptReferencesRound = $isRemovingDanglingConceptReferencesRound;
        $this->noUpdates = $noUpdates; // create mode
        $this->toBeChecked = $toBeChecked;
        $this->fallbackLanguage = $fallbackLanguage;
        $this->clearSet = $clearSet;
        $this->deleteSchemes = $deleteSchemes;
        $this->person = $person;
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
    public function getSetUri()
    {
        return $this->setUri;
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
    public function getClearSet()
    {
        return $this->clearSet;
    }

    /**
     * @return Person
     */
    public function getUser()
    {
        return $this->person;
    }

    public function isRemovingDanglingConceptReferencesRound()
    {
        return $this->isRemovingDanglingConceptReferencesRound;
    }
}
