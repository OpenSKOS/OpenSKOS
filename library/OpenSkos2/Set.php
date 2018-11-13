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

class Set extends Resource
{

    //const TYPE = Dcmi::DATASET;
    const TYPE = OpenSkos::SET;

    /**
     * @return Bootstrap
     */
    protected function getBootstrap()
    {
        return \Zend_Controller_Front::getInstance()->getParam('bootstrap');
    }

    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    public function getPublisherUri()
    {
        $tenants = $this->getProperty(DcTerms::PUBLISHER);
        if (count($tenants) < 1) {
            return null;
        } else {
            return $tenants[0];
        }
    }
    
    public function getAllowOai()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ALLOW_OAI);
        return $this->toBool($val);
    }

  /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param \OpenSkos2\Tenant $tenant
     * @param \OpenSkos2\Set $set
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param \OpenSkos2\SkosXl\LabelManager | null  $labelManager
     * @param  \OpenSkos2\Rdf\Resource | null $existingResource,
   * optional $existingResource of one of concrete child types used for update
     * override for a concerete resources when necessary
     */
    public function ensureMetadata(
        \OpenSkos2\Tenant $tenant,
        \OpenSkos2\Set $set = null,
        \OpenSkos2\Person $person = null,
        \OpenSkos2\PersonManager $personManager = null,
        \OpenSkos2\SkosXl\LabelManager $labelManager = null,
        $existingConcept = null,
        $forceCreationOfXl = false
    ) {
    
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };

            $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            DcTerms::PUBLISHER => new Uri($tenant->getUri()),
            OpenSkos::TENANT => $tenant->getCode(),
            DcTerms::DATESUBMITTED => $nowLiteral()
            ];

            foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
                if (!$this->hasProperty($property)) {
                    $this->setProperty($property, $defaultValue);
                }
            }

            $this->resolveCreator($person, $personManager);

            $this->setModified($person);
    }

    // TODO: discuss the rules for generating Uri's for non-concepts
    protected function assembleUri(
        \OpenSkos2\Tenant $tenant = null,
        \OpenSkos2\Set $collection = null,
        $uuid = null,
        $notation = null,
        $customInit = null
    ) {
    
        return $tenant->getUri() . "/" . $uuid;
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

        if ($this->uri) {
            //When creating a new collection in the editor, the uri could still be null
            $model = new \OpenSKOS_Db_Table_Jobs();
            $select = $model->select()
                ->where('set_uri=?', $this->uri)
                ->where('finished IS NULL')
                ->order('created desc')
                ->order('started asc');
            if (null !== $task) {
                $select->where('task = ?', $task);
            }
            return $model->fetchAll($select);
        }
        return array();
    }

    /**
     * @return Zend_Form
     */
    public function getForm()
    {
        static $form;
        if (null === $form) {
            $currentURI = $this->uri;
            if ($currentURI) {
                $attribs = array('readonly' => 'true');
            } else {
                $attribs = array();
            }
            $form = new \Zend_Form();
            $form
                    ->addElement('hidden', 'id', array(
                        'required' => $this->getPropertySingleValue(DcTerms::TITLE) ? true : false))
                    ->addElement('text', 'code', array('label' => _('Code'), 'attribs' =>array(
                        'required' => true
                    )))
                    ->addElement('text', 'conceptBaseUri', array(
                        'label' => _('Concept Base Uri'),
                        'required' => true,
                        'attribs' => $attribs))
                    ->addElement('text', 'dc_title', array('label' => _('Title'), 'attribs' => array(
                        'required' => true
                    )))
                    ->addElement('textarea', 'dc_description', array(
                        'label' => _('Description'),
                        'cols' => 80,
                        'row' => 5))
                    ->addElement('text', 'website', array('label' => _('Website')))
                    ->addElement('select', 'license', array(
                        'label' => _('Standard Licence'),
                        'style' => 'width: 450px;'))
                    ->addElement('text', 'license_name', array('label' => _('Custom Licence (name)')))
                    ->addElement('text', 'license_url', array('label' => _('Custom (URL)')))
                    ->addElement('checkbox', 'allow_oai', array(
                        'label' => _('Allow OpenSKOS OAI Harvesting')
                    ))
                    ->addElement('select', 'OAI_baseURL', array(
                        'label' => _('OAI baseURL'),
                        'style' => 'width: 450px;'))
                    ->addElement('submit', 'submit', array('label' => _('Submit')))
                    ->addElement('reset', 'reset', array('label' => _('Reset')))
                    ->addElement('submit', 'cancel', array('label' => _('Cancel')))
                    ->addElement('submit', 'delete', array(
                        'label' => _('Delete'),
                        'onclick' => 'return confirm(\'' .
                            _('Are you sure you want to delete this collection and corresponding Concepts?') . '\');'))
                    ->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
            ;
            if (!$this->getPropertySingleValue(DcTerms::TITLE)) {
                $form->removeElement('delete');
            }

            //String too long for PHPCBF
            $getAroundPHPCBF = "if (this.selectedIndex>0) {this.form.elements['license_name'].value";
            $getAroundPHPCBF .= "=this.options[this.selectedIndex].text; ";
            $getAroundPHPCBF .= "this.form.elements['license_url'].value=this.options[this.selectedIndex].value; }";

            $l = $form->getElement('license')->setOptions(
                array('onchange' => $getAroundPHPCBF)
            );
            $l->addMultiOption('', _('choose a standard license  or type a custom one:'), '');
            foreach (\OpenSKOS_Db_Table_Collections::$licences as $key => $value) {
                $l->addMultiOption($value, $key);
            }
            $form->getElement('allow_oai')
                    ->setCheckedValue('Y')
                    ->setUncheckedValue('N');

            $form->getElement('OAI_baseURL')->addValidator(new \OpenSKOS_Validate_Url());
            $form->getElement('license_url')->addValidator(new \OpenSKOS_Validate_Url());
            $form->getElement('website')->addValidator(new \OpenSKOS_Validate_Url());
            $form->getElement('conceptBaseUri')->addValidator(new \OpenSKOS_Validate_Url());
            $formData = $this->dataToArray();
            if ($currentURI && !$formData['conceptBaseUri']) {
                //This is a re-edit of a collection. Some legacy data won't have this filled
                $formData['conceptBaseUri'] = $formData['id'] = $currentURI;
            }
            $form->setDefaults($formData);
            //load OAI sources:
            $oai_providers = array('' => _('Pick a provider (or leave empty)...'));
            $bootstrap = $this->getBootstrap();
            $instances = $bootstrap->getOption('instances');
            if (null !== $instances) {
                foreach ($instances as $instance) {
                    switch ($instance['type']) {
                        case 'openskos':
                            //fetch Sets:
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
            if (isset($formData['OAI_baseURL'])) {
                $oaiVal =$formData['OAI_baseURL']->getUri();
                if (!isset($oai_providers[$oaiVal])) {
                    $oai_providers[$oaiVal] = $oaiVal;
                }
            }
            $form->getElement('OAI_baseURL')->setMultiOptions($oai_providers);
        }
        return $form;
    }

    /*
     * Intended for the AllowOAIPHM field
     */
    protected function toBoolean($value)
    {
        $retval = false;
        if ($value === true ||
            $value === 1 ||
            strtolower($value) === "true" ||
            strtolower($value) === "yes" ||
            strtolower($value) === "y"        //I'm normally expecting this one.
        ) {
            $retval = true;
        }
        return $retval;
    }

    /*
     * Intended for the AllowOAIPHM field
     */
    protected function booleanToCheckbox($value)
    {
        $retval = 'N';
        if ($this->toBoolean($value)) {
            $retval = 'Y';
        }
        return $retval;
    }

    protected function dataToArray()
    {
        $dataOut = array();

        $dataOut['code'] = $dataOut['id'] = $this->getPropertySingleValue(OpenSkos::CODE);
        $dataOut['conceptBaseUri'] = $dataOut['id'] = $this->getPropertySingleValue(OpenSkos::CONCEPTBASEURI);
        $dataOut['dc_title'] = $this->getPropertySingleValue(DcTerms::TITLE);
        $dataOut['dc_description'] = $this->getPropertySingleValue(DcTerms::DESCRIPTION);
        $dataOut['website'] = $this->getPropertySingleValue(OpenSkos::WEBPAGE);
        $dataOut['license_name'] = $this->getPropertySingleValue(DcTerms::LICENSE);
        $dataOut['license_url'] = $this->getPropertySingleValue(Openskos::LICENCE_URL);
        $dataOut['OAI_baseURL'] = $this->getPropertySingleValue(OpenSkos::OAI_BASEURL);


        $allowOai =     $this->getPropertySingleValue(OpenSkos::ALLOW_OAI);
        $dataOut['allow_oai'] = $this->booleanToCheckbox($allowOai->getValue());

        return $dataOut;
    }

    /*
     * @param array $dataIn, Array to convert
     */
    public function arrayToData($dataIn)
    {
        $dataOut = array();

        foreach ($dataIn as $key => $val) {
            switch ($key) {
                case 'tenant':
                    $this->setProperty(OpenSkos::TENANT, new Literal($val));
                    break;
                case 'code':
                    $this->setProperty(OpenSkos::CODE, new Literal($val));
                    break;
                case 'conceptBaseUri':
                    $this->setProperty(OpenSkos::CONCEPTBASEURI, new Literal($val));
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
                case 'license_name':
                    $this->setProperty(DcTerms::LICENSE, new Literal($val));
                    break;
                case 'license_url':
                    if (filter_var($val, FILTER_VALIDATE_URL)) {
                        $this->setProperty(OpenSkos::LICENCE_URL, new Uri($val));
                    }
                    break;
                case 'allow_oai':
                    $this->setProperty(OpenSkos::ALLOW_OAI, new Literal($this->toBoolean($val), null, 'xsd:boolean'));
                    break;
                case 'OAI_baseURL':
                    if (filter_var($val, FILTER_VALIDATE_URL)) {
                        $this->setProperty(OpenSkos::OAI_BASEURL, new Uri($val));
                    }
                    break;
            }
        }
        return $this;
    }

    /**
     * Get the set uri for openskos:set
     *
     * @return \OpenSkos2\Rdf\Uri
     */
    public function generateUri()
    {
        $apiOptions = \OpenSKOS_Application_BootstrapAccess::getOption('api');
        $baseUri = $apiOptions['baseUri'];
        $generatedUri = null;
        if (empty($this->uri)) {
            $conceptBaseUri = $this->getPropertySingleValue(OpenSkos::CONCEPTBASEURI)->getValue();

            if ($conceptBaseUri) {
                $generatedUri = $conceptBaseUri;
            } else {
                $generatedUri = rtrim($baseUri, '/') . '/collections/' . $this->getPropertyFlatValue(Openskos::CODE);
            }
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
                ->addElement(
                    'file',
                    'xml',
                    array(
                        'label' => _('File'),
                        'required' => true,
                        'validators' => array('NotEmpty' => array())
                    )
                );
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
            $form->addElement('select', 'lang', array(
                'label' => 'The default language to use if no "xml:lang" attribute is found',
                'multiOptions' => $editorOptions['languages']));
            $form->addElement(
                'checkbox',
                'toBeChecked',
                array('label' => 'Sets the toBeChecked status of imported concepts')
            );
            $getAroundPHPCBF = "Purge. Delete all concept schemes found in the file. ";
            $getAroundPHPCBF .= "(will also delete concepts inside them)";
            $form->addElement('checkbox', 'purge', array(
                'label' => $getAroundPHPCBF));
            $form->addElement(
                'checkbox',
                'delete-before-import',
                array('label' => _('Delete concepts in this collection before import'))
            );
            $getAroundPHPCBF = "Import contains only new concepts. Do not update any concepts if ";
            $getAroundPHPCBF .= "they match by notation (or uri if useUriAsIdentifier is used).";
            $form->addElement(
                'checkbox',
                'onlyNewConcepts',
                array('label' => _($getAroundPHPCBF))
            );
            $form->addElement('submit', 'submit', array('label' => 'Submit'));
        }
        return $form;
    }
}
