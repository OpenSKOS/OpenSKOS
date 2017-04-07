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
     * @var bool Include references to concepts (set to false while first running of the import script, when nt concept are imported)
     */
    private $includeConceptReferences;

    /**
     * @var bool
     */
    private $toBeChecked;

    /**
     * @var Person
     */
    private $user;

    /**
     * Message constructor.
     * @param $user
     * @param $file
     * @param Uri $setUri
     * @param bool $ignoreIncomingStatus
     * @param string $importedConceptStatus
     * @param string $includeConceptReferences
     * @param bool $noUpdates
     * @param bool $toBeChecked
     * @param string $fallbackLanguage
     * @param bool $clearSet
     * @param bool $deleteSchemes
     */
    public function __construct(
        $user,
        $file,
        $setUri,
        $ignoreIncomingStatus,
        $importedConceptStatus,
        $includeConceptReferences,
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
        $this->includeConceptReferences=$includeConceptReferences;
        $this->noUpdates = $noUpdates; // create mode
        $this->toBeChecked = $toBeChecked;
        $this->fallbackLanguage = $fallbackLanguage;
        $this->clearSet = $clearSet;
        $this->deleteSchemes = $deleteSchemes;
        $this->user = $user;
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
        return $this->user;
    }
    
    public function getIncludeConceptReferences()
    {
        return $this->includeConceptReferences;
    }
}
