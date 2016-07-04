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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_Forms_UploadIcon extends Zend_Form
{
   
    const DEFAULT_UPLOAD_PATH = '/../public/data/icons/uploads';
    const DEFAULT_UPLOAD_HTTP_PATH = '/data/icons/uploads';
    const DEFAULT_ASSIGN_PATH = '/../public/data/icons/assigned';
    const DEFAULT_ASSIGN_HTTP_PATH = '/data/icons/assigned';
    
    public function init()
    {
        $this->setName('uploadicon')
        ->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array('controller' => 'concept-scheme', 'action' => 'upload-icon')))
        ->setMethod('post')
        ->setAttrib('enctype', 'multipart/form-data');

        $this->buildFileUpload()
        ->buildUploadButton();
    }
    
    protected function buildFileUpload()
    {
        $tenantFromIdentity = OpenSKOS_Db_Table_Tenants::fromIdentity();
        
        // We always need tenant for getting icon path.
        if (null !== $tenantFromIdentity) {
            $iconUpload = new Zend_Form_Element_File('icon');
            
            $iconUpload->setLabel('Upload a new icon:')
            ->addValidator('Count', false, 1)
            ->setRequired(true);
            
            $editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
            
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadPath'])) {
                $iconUpload->setDestination(APPLICATION_PATH . $editorOptions['schemeIcons']['uploadPath'] . '/' . $tenantFromIdentity->code);
            } else {
                $iconUpload->setDestination(APPLICATION_PATH . self::DEFAULT_UPLOAD_PATH . '/' . $tenantFromIdentity->code);
            }
            
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['allowedExtensions'])) {
                $iconUpload->addValidator('Extension', false, $editorOptions['schemeIcons']['allowedExtensions']);
            } else {
                $iconUpload->addValidator('Extension', false, 'jpg, jpeg, png, gif');
            }
            
            if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['maxSize'])) {
                $iconUpload->addValidator('Size', false, $editorOptions['schemeIcons']['maxSize']);
                $iconUpload->setMaxFileSize($editorOptions['schemeIcons']['maxSize']);
            } else {
                $iconUpload->addValidator('Size', false, 2097152);
                $iconUpload->setMaxFileSize(2097152);
            }
            
            $this->addElement($iconUpload, 'icon');
        }
        
        return $this;
    }
    
    protected function buildUploadButton()
    {
        $this->addElement('submit', 'uploadButton', array(
                'required' => false,
                'ignore' => true,
                'label' => _('Upload')
        ));
        return $this;
    }
    
    /**
     * @return Editor_Forms_UploadIcon
     */
    public static function getInstance()
    {
        static $instance;
        
        if (null === $instance) {
            $instance = new Editor_Forms_UploadIcon();
        }
        
        return $instance;
    }
}
