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

namespace OpenSkos2;

use Exception;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\PersonManager;
use Rhumsaa\Uuid\Uuid;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\Label;

class Collection extends Resource
{

    //const TYPE = Dcmi::DATASET;
    const TYPE = OpenSkos::SET;


    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    public function getAllowOai()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ALLOW_OAI);
        return $this->toBool($val);
    }

    /**
     * Returns title of collection
     * @return null|string Title
     */
    public function getTitle()
    {
        return $this->getPropertySingleValue(DcTerms::TITLE);
    }
    /**
     * @return Zend_Db_Table_Rowset
     */
    public function getJobs($task = null)
    {
        return array();
        //new records do not have jobs:
        if (null === $this->id) {
            return array();
        }

        $model = new OpenSKOS_Db_Table_Jobs();
        $select = $model->select()
            ->where('collection=?', $this->id)
            ->where('finished IS NULL')
            ->order('created desc')
            ->order('started asc');
        if (null !== $task) {
            $select->where('task = ?', $task);
        }
        return $model->fetchAll($select);
    }

    /**
     * @return Zend_Form
     */
    public function getForm()
    {
        static $form;
        if (null === $form) {
            $form = new \Zend_Form();
            $form
                    ->addElement('hidden', 'id', array('required' => $this->getPropertySingleValue(DcTerms::TITLE) ? true : false))
                    ->addElement('text', 'code', array('label' => _('Code'), 'required' => true))
                    ->addElement('text', 'dc_title', array('label' => _('Title'), 'required' => true))
                    ->addElement('textarea', 'dc_description', array('label' => _('Description'), 'cols' => 80, 'row' => 5))
                    ->addElement('text', 'website', array('label' => _('Website')))
                    ->addElement('select', 'license', array('label' => _('Standard Licence'), 'style' => 'width: 450px;'))
                    ->addElement('text', 'license_name', array('label' => _('Custom Licence (name)')))
                    ->addElement('text', 'license_url', array('label' => _('Custom (URL)')))
                    ->addElement('checkbox', 'allow_oai', array('label' => _('Allow OpenSKOS OAI Harvesting')))
                    ->addElement('select', 'OAI_baseURL', array('label' => _('OAI baseURL'), 'style' => 'width: 450px;'))
                    ->addElement('submit', 'submit', array('label' => _('Submit')))
                    ->addElement('reset', 'reset', array('label' => _('Reset')))
                    ->addElement('submit', 'cancel', array('label' => _('Cancel')))
                    ->addElement('submit', 'delete', array('label' => _('Delete'), 'onclick' => 'return confirm(\'' . _('Are you sure you want to delete this collection and corresponding Concepts?') . '\');'))
                    ->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
            ;
            if (!$this->getPropertySingleValue(DcTerms::TITLE)) {
                $form->removeElement('delete');
            }
            $l = $form->getElement('license')->setOptions(
                array('onchange' => 'if (this.selectedIndex>0) {this.form.elements[\'license_name\'].value=this.options[this.selectedIndex].text; this.form.elements[\'license_url\'].value=this.options[this.selectedIndex].value; }')
            );
            $l->addMultiOption('', _('choose a standard license  or type a custom one:'), '');
            foreach (\OpenSKOS_Db_Table_Collections::$licences as $key => $value) {
                $l->addMultiOption($value, $key);
            }
            $form->getElement('allow_oai')
                    ->setCheckedValue('Y')
                    ->setUncheckedValue('N');
            /*
             * TODO: What's a validator?
            $validator = new \Zend_Validate_Callback(array($this->getTable(), 'uniqueCode'));
            $validator->setMessage("code '%value%' already exists", Zend_Validate_Callback::INVALID_VALUE);
            $form->getElement('code')->addValidator($validator);
             */
            $form->getElement('OAI_baseURL')->addValidator(new \OpenSKOS_Validate_Url());
            $form->setDefaults($this->dataToArray());
            //load OAI sources:
            $oai_providers = array('' => _('Pick a provider (or leave empty)...'));
            /*
            $bootstrap = $this->_getBootstrap();
            $instances = $bootstrap->getOption('instances');
            if (null !== $instances) {
                foreach ($instances as $instance) {
                    switch ($instance['type']) {
                        case 'openskos':
                            //fetch Collections:
                            $client = new Zend_Http_Client($instance['url'] . '/api/collections');
                            $response = $client
                                    ->setParameterGet('allow_oai', 'y')
                                    ->setParameterGet('format', 'json')
                                    ->request('GET');
                            if ($response->isError()) {
                                throw new Zend_Exception($response->getMessage(), $response->getCode());
                            }
                            foreach (json_decode($response->getBody())->collections as $collection) {
                                $uri = $instance['url'] . '/oai-pmh/?set=' . $collection->id;
                                $oai_providers[$uri] = $collection->dc_title;
                            }
                            break;
                        case 'external':
                            $uri = rtrim($instance['url'], '?/');
                            if ($instance['set'] || $instance['metadataPrefix']) {
                                $uri .= '?';
                            }
                            if ($instance['set']) {
                                $uri .= '&set=' . $instance['set'];
                            }
                            if ($instance['metadataPrefix']) {
                                $uri .= '&metadataPrefix=' . $instance['metadataPrefix'];
                            }
                            $oai_providers[$uri] = $instance['label'];
                            break;
                        default:
                            throw new Zend_Exception('Unkown OAI instance type: ' . $instance['type']);
                    }
                }
            }
            if (!isset($oai_providers[$this->OAI_baseURL])) {
                $oai_providers[$this->OAI_baseURL] = $this->OAI_baseURL;
            }
            $form->getElement('OAI_baseURL')->setMultiOptions($oai_providers);
            */
        }
        return $form;
    }

    protected function dataToArray()
    {
        $dataOut = array();

        $dataOut['code'] = $dataOut['id'] = $this->getPropertySingleValue(OpenSkos::CODE);

        $dataOut['dc_title'] = $this->getPropertySingleValue( DcTerms::TITLE);
        $dataOut['dc_description'] = $this->getPropertySingleValue( DcTerms::DESCRIPTION);
        $dataOut['website'] = $this->getPropertySingleValue(OpenSkos::WEBPAGE);
        $dataOut['license'] = $this->getPropertySingleValue( DcTerms::LICENSE);
        //$dataOut['license_name'] = $this->getPropertySingleValue();
        $dataOut['license_url'] = $this->getPropertySingleValue( Openskos::CONCEPTBASEURI);
        $dataOut['allow_oai'] = $this->getPropertySingleValue(OpenSkos::ALLOW_OAI);
        $dataOut['OAI_baseURL'] = $this->getPropertySingleValue(OpenSkos::OAI_BASEURL);

        return $dataOut;

    }

    /*
     * @param array $dataIn, Array to convert
     */
    public function arrayToData($dataIn)
    {
        $dataOut = array();

        foreach ($dataIn as $key => $val){
            switch($key){
                case 'tenant':
                    $this->setProperty(OpenSkos::TENANT, new Literal($val));
                    break;
                case 'code':
                    $this->setProperty(OpenSkos::CODE, new Literal($val));
                    break;
                case 'dc_title':
                    $this->setProperty(DcTerms::TITLE, new Literal($val));
                    break;
                case 'dc_description':
                    $this->setProperty(DcTerms::DESCRIPTION, new Literal($val));
                    break;
                case 'website':
                    $this->setProperty(OpenSkos::WEBPAGE, new Literal($val));
                    break;
                case 'license':
                    $this->setProperty(DcTerms::LICENSE, new Uri($val));
                    break;
                case 'license_url':
                    $this->setProperty(OpenSkos::CONCEPTBASEURI, new Literal($val));
                    break;
                case 'allow_oai':
                    $this->setProperty(OpenSkos::ALLOW_OAI, new Literal($val));
                    break;
                case 'OAI_baseURL':
                    $this->setProperty(OpenSkos::OAI_BASEURL, new Literal($val));
                    break;
            }


        }
        return $this;

    }

    /**
     * Get the set uri for openskos:collection
     *
     * @return \OpenSkos2\Rdf\Uri
     */
    public function generateUri()
    {
        $apiOptions = \OpenSKOS_Application_BootstrapAccess::getOption('api');
        $baseUri = $apiOptions['baseUri'];
        $generatedUri = null;
        // If we don't have uri yet - use base uri or generate one.
        if (empty($this->uri)) {
            $generatedUri = rtrim($baseUri, '/') . '/collections/' . $this->getPropertyFlatValue(Openskos::CODE);
            $this->uri = $generatedUri;

        }

        return $this->uri;
    }

    /**
     * @return Zend_Form
     */
    public function getUploadForm()
    {
        static $form;
        if (null === $form) {
            $form = new \Zend_Form();
            $form
                ->setAttrib('enctype', 'multipart/form-data')
                ->addElement('file', 'xml', array('label' => _('File'), 'required' => true, 'validators' => array('NotEmpty' => array())));
            $statusOptions = [
                'label' => 'Status for imported concepts',
            ];
            if (false && $this->getTenant()['enableStatusesSystem']) {  //TODO
                $statusOptions['multiOptions'] = \OpenSKOS_Concept_Status::statusesToOptions();
            } else {
                $statusOptions['multiOptions'] = [\OpenSKOS_Concept_Status::APPROVED];
                $statusOptions['disabled'] = true;
            }
            $form->addElement('select', 'status', $statusOptions);
            $form->addElement('checkbox', 'ignoreIncomingStatus', array('label' => 'Ignore incoming status'));
            $editorOptions = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
            $form->addElement('select', 'lang', array('label' => 'The default language to use if no "xml:lang" attribute is found', 'multiOptions' => $editorOptions['languages']));
            $form->addElement('checkbox', 'toBeChecked', array('label' => 'Sets the toBeChecked status of imported concepts'));
            $form->addElement('checkbox', 'purge', array('label' => 'Purge. Delete all concept schemes found in the file. (will also delete concepts inside them)'));
            $form->addElement('checkbox', 'delete-before-import', array('label' => _('Delete concepts in this collection before import')));
            $form->addElement('checkbox', 'onlyNewConcepts', array('label' => _('Import contains only new concepts. Do not update any concepts if they match by notation (or uri if useUriAsIdentifier is used).')));
            $form->addElement('submit', 'submit', array('label' => 'Submit'));
        }
        return $form;
    }

}
