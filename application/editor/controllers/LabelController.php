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

use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\Rdf\Literal;

class Editor_LabelController extends OpenSKOS_Controller_Editor
{
    public function init()
    {
        $this->_helper->_layout->setLayout('editor_modal_box');
    }
    
    public function addToConceptAction()
    {
        
    }
    
    public function createAction()
    {
        $this->view->form = Editor_Forms_Label::getInstance();
    }
    
    public function editAction()
    {
        $form = Editor_Forms_Label::getInstance();
        
        $label = $this->getLabel();
        $literalForm = $label->getProperty(SkosXl::LITERALFORM)[0];
        
        $form->populate([
            'uri' => $label->getUri(),
            'literalForm' => $literalForm->getValue(),
            'language' => $literalForm->getLanguage(),
        ]);
        
        $this->view->form = $form;
        $this->view->label = $label;
    }
    
    public function saveAction()
    {
        $form = Editor_Forms_Label::getInstance();
        $label = $this->getLabel();
        
        $isCreate = empty($label);
        if ($isCreate) {
            $label = new Label(Label::generateUri());
        }
        
        $form->populate($this->getRequest()->getParams());
        
        $label->setProperty(
            SkosXl::LITERALFORM,
            new Literal(
                $form->getValue('literalForm'),
                $form->getValue('language')
            )
        );
        
        $this->getLabelManager()->replace($label);
        
        $this->view->label = $label;
        $this->view->isCreate = $isCreate;
    }
    
    public function chooseAction()
    {
        // @TODO
        $this->view->labels = $this->getLabelManager()->fetch();
    }
    
    /**
     * @return OpenSkos2\SkosXl\Label
     * @throws ResourceNotFoundException
     */
    protected function getLabel()
    {
        $uri = $this->getRequest()->getParam('uri');
        if (!empty($uri)) {
            return $this->getLabelManager()->fetchByUri($uri);
        } else {
            return null;
        }
    }
    
    /**
     * @return OpenSkos2\SkosXl\LabelManager
     */
    protected function getLabelManager()
    {
        return $this->getDI()->get('\OpenSkos2\SkosXl\LabelManager');
    }
}
