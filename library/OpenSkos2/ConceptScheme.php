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
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Namespaces\DcTerms;

class ConceptScheme extends Resource
{
    const TYPE = 'http://www.w3.org/2004/02/skos/core#conceptScheme';
    
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
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws \Exception
     */
    public function getCaption($language = null)
    {
        if ($this->hasPropertyInLanguage(DcTerms::TITLE, $language)) {
            return $this->getPropertyFlatValue(DcTerms::TITLE, $language);
        } else {
            return $this->getPropertyFlatValue(DcTerms::TITLE);
        }
    }
        
    /**
     * Check if the concept scheme is deleted
     * @return boolean
     */
    public function isDeleted()
    {
        // @TODO Not supported at all yet.
        return false;
    }
    
    /**
     * Get openskos:uuid if it exists
     * Identifier for backwards compatability. Always use uri as identifier.
     * @return string
     */
    public function getUuid()
    {
        return $this->getPropertySingleValue(OpenSkos::UUID);
    }
    
    /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param string $tenantCode
     * @param Uri $set
     * @param Uri $person
     */
    public function ensureMetadata($tenantCode, Uri $set, Uri $person)
    {
        //@TODO Combine with concept ensure metadata.
        
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        
        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            OpenSkos::TENANT => new Literal($tenantCode),
            OpenSkos::SET => $set,
            DcTerms::CREATOR => $person,
            DcTerms::DATESUBMITTED => $nowLiteral(),
        ];
        
        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }
        
        // @TODO Should we add modified instead of replace it. Or put it only on create.
        $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
        $this->addUniqueProperty(DcTerms::CONTRIBUTOR, $person);
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
        return self::buildIconPath($this->getPropertySingleValue(OpenSkos::UUID), $tenant);
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
}
