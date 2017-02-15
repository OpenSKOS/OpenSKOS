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
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Search\Autocomplete;

class Editor_LabelController extends OpenSKOS_Controller_Editor
{
    public function init()
    {
        parent::init();
        $this->_helper->_layout->setLayout('editor_modal_box');
    }
    
    public function addToConceptAction()
    {
        $this->view->language = $this->getRequest()->getParam('language');
    }
    
    public function createAction()
    {
        $form = Editor_Forms_Label::getInstance();
        $form->populate([
            'language' => $this->getRequest()->getParam('language'),
        ]);
        $this->view->form = $form;
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
        $tenant = $this->getOpenSkos2Tenant();
        
        if ($tenant === null) {
            throw new OpenSkos2\Exception\TenantNotFoundException('Could not get tenant.');
        }
                
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
        
        $label->ensureMetadata($tenant->getCode());
        
        $this->getLabelManager()->replace($label);
        
        $this->view->label = $label;
        $this->view->isCreate = $isCreate;
    }
    
    public function chooseAction()
    {
        $this->view->language = $this->getRequest()->getParam('language');
    }
    
    public function autocompleteAction()
    {
        /* @var $autocomplete Autocomplete */
        $autocomplete = $this->getDI()->get('\OpenSkos2\Search\AutocompleteLabels');

        $options = [
            'searchText' => $this->getRequest()->getParam('query'),
            'language' => $this->getRequest()->getParam('language'),
            'rows' => 20,
            'start' => 0 //TODO: implement pagination
        ];
        $labels = $autocomplete->search($options, $numFound);
        
        $labelsData = [];
        foreach ($labels as $label) {
            $literalForm = $label->getProperty(SkosXl::LITERALFORM)[0];
            $labelsData[] = [
                'uri' => $label->getUri(),
                'language' => $literalForm->getLanguage(),
                'literalForm' => $literalForm->getValue(),
            ];
        }
        
        $response = new Zend\Diactoros\Response\JsonResponse([
            'status' => 'ok',
            'labels' => $labelsData,
        ]);
        $this->emitResponse($response);
    }
    
    public function skosXlLinkedDataAction() {
        $labelXlUri = $this->_request->getParam('uri');
        
        if(empty($labelXlUri) === true) {
            echo 'Uri not specified';
        }
        
        /* @var $labelXL OpenSkos2\SkosXl\Label */
        $labelXL = $this->getLabelManager()->fetchByUri($labelXlUri);
        
        /* @var $conceptManager OpenSkos2\ConceptManager */
        $conceptManager = $this->getDI()->get('OpenSkos2\ConceptManager');
        $relations = $conceptManager->fetchByLabel($labelXL);
        
        $this->view->labelXL = $labelXL;
        $this->view->relations = $relations;
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
