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

namespace OpenSkos2;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Namespaces\DcTerms;

class ConceptScheme extends Resource
{
    const TYPE = Skos::CONCEPTSCHEME;
    
    /**
     * Resource constructor.
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        if (!$this->hasProperty(OpenSkos::STATUS)) {
            return null;
        } else {
            return $this->getProperty(OpenSkos::STATUS)[0]->getValue();
        }
    }
    
    /**
     * Check if the concept is deleted
     *
     * @return boolean
     */
    public function isDeleted()
    {
        if ($this->getStatus() === self::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    /**
     * Gets preview title for the concept schema.
     * @param string $language
     * @return string
     * @throws \Exception
     */
    public function getTitle($language = null)
    {
        if ($this->hasPropertyInLanguage(DcTerms::TITLE, $language)) {
            return $this->getPropertyFlatValue(DcTerms::TITLE, $language);
        } else {
            return $this->getPropertyFlatValue(DcTerms::TITLE);
        }
    }
    
    /**
     * Get openskos:uuid if it exists
     * Identifier for backwards compatability. Always use uri as identifier.
     * @return string|null
     */
    public function getUuid()
    {
        if ($this->hasProperty(OpenSkos::UUID)) {
            return (string)$this->getPropertySingleValue(OpenSkos::UUID);
        } else {
            return null;
        }
    }
    
    /**
     * Get openskos:set if it exists
     * Identifier for backwards compatability. Always use uri as identifier.
     * @return string|null
     */
    public function getSet()
    {
        if ($this->hasProperty(OpenSkos::SET)) {
            return (string)$this->getPropertySingleValue(OpenSkos::SET);
        } else {
            return null;
        }
    }

    /**
     * Get tenant
     *
     * @return Literal
     */
    public function getTenant()
    {
        $values = $this->getProperty(OpenSkos::TENANT);
        if (isset($values[0])) {
            return $values[0];
        }
    }
    
    public function getDescription()
    {
        if ($this->hasProperty(DcTerms::DESCRIPTION)) {
            return (string)$this->getPropertySingleValue(DcTerms::DESCRIPTION);
        } else {
            return null;
        }
    }
  
 
    
    /**
     * Builds the path to the concept scheme icon.
     * Returns empty string if the file does not exist.
     *
     * @todo Moved from Editor_Models_ConceptScheme for backwards compatibility,
     * refactor later to not depend on the zend application
     * @param srtring $uuid
     * @param OpenSKOS_Db_Table_Row_Tenant $tenant optional, Default null.
     * If not set the currently logged one will be used.
     * @return string
     */
    public function getIconPath($tenant = null)
    {
        return self::buildIconPath((string)$this->getPropertySingleValue(OpenSkos::UUID), $tenant);
    }
    
    /**
     *
     * Builds the path to the concept scheme icon.
     * Returns empty string if the file does not exist.
     *
     * @todo Moved from Editor_Models_ConceptScheme for backwards compatibility,
     * refactor later to not depend on the zend application
     * @param srtring $uuid
     * @param OpenSKOS_Db_Table_Row_Tenant $tenant optional, Default null.
     * If not set the currently logged one will be used.
     * @return string
     */
    public static function buildIconPath($uuid, $tenant = null)
    {
        $editorOptions = \OpenSKOS_Application_BootstrapAccess::getBootstrap()->getOption('editor');
        
        if (null === $tenant) {
            $tenant = \OpenSKOS_Db_Table_Tenants::fromIdentity();
        }
        
        $ap = APPLICATION_PATH;
        // We always need tenant for getting icon path.
        if (null !== $tenant) {
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignPath'])) {
                $iconsAssignPath = $ap . $editorOptions['schemeIcons']['assignPath'] . '/' . $tenant->code;
            } else {
                $iconsAssignPath = $ap . \Editor_Forms_UploadIcon::DEFAULT_ASSIGN_PATH . '/' . $tenant->code;
            }
            
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignHttpPath'])) {
                $iconsAssignHttpPath = $editorOptions['schemeIcons']['assignHttpPath'] . '/' . $tenant->code;
            } else {
                $iconsAssignHttpPath = \Editor_Forms_UploadIcon::DEFAULT_ASSIGN_HTTP_PATH . '/' . $tenant->code;
            }
            
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['extension'])) {
                $iconsExtension = $editorOptions['schemeIcons']['extension'];
            } else {
                $iconsExtension = 'png';
            }
            
            if (is_file($iconsAssignPath . '/' . $uuid . '.' . $iconsExtension)) {
                return $iconsAssignHttpPath . '/' . $uuid . '.' . $iconsExtension . '?nocache=' . time();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
    
    public function addMetadata($user, $params, $oldParams) {
        $metadata = [];

        if (count($oldParams) === 0) { // a completely new resource under creation
            $userUri = $user->getFoafPerson()->getUri();
            $nowLiteral = function () {
                return new Literal(date('c'), null, Literal::TYPE_DATETIME);
            };

            $metadata = [
                DcTerms::CREATOR => new Uri($userUri),
                DcTerms::DATESUBMITTED => $nowLiteral(),
            ];
        } else {
            $metadata = [
                OpenSkos::UUID => new Literal($oldParams['uuid']),
                DcTerms::CREATOR => new Uri($oldParams['creator']),
                DcTerms::DATESUBMITTED => new Literal($oldParams['dateSubmitted'], null, Literal::TYPE_DATETIME),
            ];
        }
        foreach ($metadata as $property => $defaultValue) {
            $this->setProperty($property, $defaultValue);
        }
    }

}
