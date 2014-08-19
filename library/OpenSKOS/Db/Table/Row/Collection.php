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
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Mark Lindeman
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class OpenSKOS_Db_Table_Row_Collection extends Zend_Db_Table_Row
{
	public function setNamespaces($namespaces)
	{
		$links = $this->findManyToManyRowset(
			'OpenSKOS_Db_Table_Namespaces', 
			'OpenSKOS_Db_Table_CollectionHasNamespaces'
		);
		
		$model = new OpenSKOS_Db_Table_CollectionHasNamespaces();
		foreach ($links as $link) {
			foreach ($model->find($this->id, $link->prefix) as $row) {
				$row->delete();
			}
		}
		
		$Namespaces = new OpenSKOS_Db_Table_Namespaces();
		foreach ($namespaces as $prefix=>$uri) {
			$Namespace = $Namespaces->find($prefix)->current();
			if (null === $Namespace) {
				$Namespace = $Namespaces->createRow(array(
					'prefix' => $prefix,
					'uri' => $uri
				));
				$Namespace->save();
			}
			$this->addNamespace($Namespace);
		}
		return $this;
	}
	
	/**
	 * @return Zend_Db_Table_Rowset
	 */
	public function getJobs($task = null)
	{
	    //new records do not have jobs:
	    if (null === $this->id) return array();
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
	public function getUploadForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form
				->setAttrib('enctype', 'multipart/form-data')
				->addElement('file', 'xml', array('label'=>_('File'), 'required' => true, 'validators' => array('NotEmpty'=>array())));
			
			$availableStatuses = array();
			$availableStatuses[] = 'candidate';
			$availableStatuses[] = 'approved';
			$availableStatuses[] = 'expired';
			$form->addElement('select', 'status', array('label' => 'Status for imported concepts', 'multiOptions' => array_combine($availableStatuses, $availableStatuses)));
			$form->addElement('checkbox', 'ignoreIncomingStatus', array('label' => 'Ignore incoming status'));
			
			$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');			
			$form->addElement('select', 'lang', array('label' => 'The default language to use if no "xml:lang" attribute is found', 'multiOptions' => $editorOptions['languages']));
						
			$form->addElement('checkbox', 'toBeChecked', array('label' => 'Sets the toBeCheked status of imported concepts'));			
			$form->addElement('checkbox', 'purge', array('label' => 'Purge. Delete all concept schemes found in the file. (will also delete concepts inside them)'));
			$form->addElement('checkbox', 'delete-before-import', array('label' => _('Delete concepts in this collection before import')));            
			$form->addElement('checkbox', 'onlyNewConcepts', array('label' => _('Import contains only new concepts. Do not update any concepts if they match by notation.')));
			
			$form->addElement('submit', 'submit', array('label'=>'Submit'));
		}
		return $form;
	}
	
	public function getOaiJobForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form
				->addElement('text', 'from', array('label' => 'Index records modified since', 'style' => 'width: 250px;'))
				->addElement('text', 'until', array('label' => 'Index records modified until', 'style' => 'width: 250px;'))
				->addElement('select', 'set', array('label' => 'OAI setSpec', 'style' => 'width: 250px;'))
				->addElement('select', 'metadataPrefix', array('label' => 'OAI metadataPrefix', 'style' => 'width: 250px;'))
				->addElement('checkbox', 'delete-before-import', array('label' => _('delete concepts in this collection before import')))
				->addElement('submit', 'submit', array('label'=>'Submit'));
			$form->getElement('delete-before-import')->setValue(1);
		}
		$form->getElement('from')->addValidator(new OpenSKOS_Validate_Datestring());
		$form->getElement('until')->addValidator(new OpenSKOS_Validate_Datestring());
		
		$harvester = new OpenSKOS_Oai_Pmh_Harvester($this);
		try {
			$sets = array('' => _('choose optional set:')) + $harvester->listSets()->toArray();
			$form->getElement('set')->setMultiOptions($sets);
		} catch (OpenSKOS_Oai_Pmh_Harvester_Exception $e) {
			$form->getElement('set')->setMultiOptions(array('['._('Failed to load sets from OAI!').']'));
		}
		
		return $form;
	}
	
	public function getId()
	{
		return $this->tenant.':'.$this->code;
	}
	
	public function getClasses(OpenSKOS_Db_Table_Row_Tenant $tenant = null)
	{
		if (null === $tenant) {
			$tenant = OpenSKOS_Db_Table_Tenants::fromCode($this->tenant);
		}
	    if (null === $tenant) {
			$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		}
		return $this->getTable()->getClasses($tenant, $this);
	}
	
	public function getConceptSchemes()
	{
		return $this->getTable()->getConceptSchemes($this);
	}
	
	public function getConceptsBaseUri()
	{
		if (isset($this->conceptsBaseUrl) && !empty($this->conceptsBaseUrl)) {
			return $this->conceptsBaseUrl;
		} else {
			$editorOptions = OpenSKOS_Application_BootstrapAccess::getOption('editor');
			if (isset($editorOptions['conceptSchemesDefaultBaseUri'])) {
				return $editorOptions['conceptSchemesDefaultBaseUri'];
			} else {
				return '';
			}
		}
	}
	
	public function addNamespace(OpenSKOS_Db_Table_Row_Namespace $namespace)
	{
		$model = new OpenSKOS_Db_Table_CollectionHasNamespaces();
		$model->createRow(array(
			'collection' => $this->id,
			'namespace' => $namespace->prefix
		))->save();
		return $this;
	}

	/**
	 * @return Zend_Form
	 */
	public function getForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form
				->addElement('hidden', 'id', array('required' => $this->id ? true : false))
				->addElement('text', 'code', array('label' => _('Code'), 'required' => true))
				->addElement('text', 'dc_title', array('label' => _('Title'), 'required' => true))
				->addElement('textarea', 'dc_description', array('label' => _('Description'), 'cols' => 80, 'row' => 5))
				->addElement('text', 'website', array('label' => _('Website')))
				->addElement('select', 'license', array('label' => _('Standard Licence'), 'style' => 'width: 450px;'))
				->addElement('text', 'license_name', array('label' => _('Custom Licence (name)')))
				->addElement('text', 'license_url', array('label' => _('Custom (URL)')))
				->addElement('checkbox', 'allow_oai', array('label' => _('Allow OpenSKOS OAI Harvesting')))
				->addElement('select', 'OAI_baseURL', array('label' => _('OAI baseURL'), 'style' => 'width: 450px;'))
				->addElement('text', 'conceptsBaseUrl', array('label' => _('Concepts base url'), 'style' => 'width: 450px;'))
				->addElement('submit', 'submit', array('label'=>_('Submit')))
				->addElement('reset', 'reset', array('label'=>_('Reset')))
				->addElement('submit', 'cancel', array('label'=>_('Cancel')))
				->addElement('submit', 'delete', array('label'=>_('Delete'), 'onclick' => 'return confirm(\''._('Are you sure you want to delete this collection and corresponding Concepts?').'\');'))
				->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
				;

			if (!$this->id) {
				$form->removeElement('delete');
			}
			$l = $form->getElement('license')->setOptions(
				array('onchange' => 'if (this.selectedIndex>0) {this.form.elements[\'license_name\'].value=this.options[this.selectedIndex].text; this.form.elements[\'license_url\'].value=this.options[this.selectedIndex].value; }')
			);
			$l->addMultiOption('', _('choose a standard license  or type a custom one:'), '');
			foreach (OpenSKOS_Db_Table_Collections::$licences as $key => $value) {
				$l->addMultiOption($value, $key);
			}
			
			$form->getElement('allow_oai')
				->setCheckedValue('Y')
				->setUncheckedValue('N');
			
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueCode'));
			$validator->setMessage("code '%value%' already exists", Zend_Validate_Callback::INVALID_VALUE);
			$form->getElement('code')->addValidator($validator);
			
			$form->getElement('OAI_baseURL')->addValidator(new OpenSKOS_Validate_Url());
			$form->setDefaults($this->toArray());
			
			//load OAI sources:
			$oai_providers = array('' => _('Pick a provider (or leave empty)...'));
			
			$bootstrap = $this->_getBootstrap();
			$instances = $bootstrap->getOption('instances');
			if (null !== $instances) {
				foreach ($instances as $instance) {
					switch ($instance['type']) {
						case 'openskos':
							//fetch Collections:
							$client = new Zend_Http_Client($instance['url'].'/api/collections');
							$response = $client
								->setParameterGet('allow_oai', 'y')
								->setParameterGet('format', 'json')
								->request('GET');
							if ($response->isError()) {
								throw new Zend_Exception($response->getMessage(), $response->getCode());
							}
							foreach (json_decode($response->getBody())->collections as $collection) {
								$uri = $instance['url'].'/oai-pmh/?set='.$collection->id;
								$oai_providers[$uri] = $collection->dc_title;
							}
							break;
						case 'external':
							$uri = rtrim($instance['url'], '?/');
							if ($instance['set'] || $instance['metadataPrefix']) $uri .= '?';
							if ($instance['set']) $uri .= '&set=' . $instance['set'];
							if ($instance['metadataPrefix']) $uri .= '&metadataPrefix=' . $instance['metadataPrefix'];
							$oai_providers[$uri] = $instance['label'];
							break;
						default:
							throw new Zend_Exception('Unkown OAI instance type: '.$instance['type']);
					}
				}
			}
			
			if ( ! isset($oai_providers[$this->OAI_baseURL])) {
				$oai_providers[$this->OAI_baseURL] = $this->OAI_baseURL;
			}
			
			$form->getElement('OAI_baseURL')->setMultiOptions($oai_providers);
		}
		return $form;
	}
	
	/**
	 * @return Bootstrap
	 */
	protected function _getBootstrap()
	{
		return Zend_Controller_Front::getInstance()->getParam('bootstrap');		
	}
	
	public function delete()
	{
		$collection_id = $this->id;
		$result = parent::delete();
		$solr = OpenSKOS_Solr::getInstance()->delete('collection:'.$collection_id);
		return $result;
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Tenant
	 */
	public function getTenant()
	{
		return $this->findParentRow('OpenSKOS_Db_Table_Tenants');
	}

	/**
	 * @return DOMDocument;
	 */
	public static function getRdfDocument()
	{
		$doc = new DOMDocument();
		$doc->appendChild($doc->createElement('rdf:RDF'));
		$doc->documentElement->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		$doc->documentElement->setAttribute('xmlns:owl', 'http://www.w3.org/2002/07/owl#');
		$doc->documentElement->setAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
		
		return $doc;
	}
	
	public function toRdf($withCreator = true)
	{
		$helper = new Zend_View_Helper_ServerUrl();
		$about = $helper->serverUrl('/api/collection/'.$this->getId());
		$data = array();
		foreach ($this as $key => $val) {
			$data[$key] = htmlspecialchars($val);
		}
		
		$doc = self::getRdfDocument();
		$root = $doc->documentElement->appendChild($doc->createElement('rdf:Description'));
		$root->setAttribute('rdf:about', $about);
		$root->appendChild($doc->createElement('rdf:type'))->setAttribute('rdf:resource', 'http://www.w3.org/2002/07/owl#Ontology');
		$root->appendChild($doc->createElement('dcterms:title', $data['dc_title']));
		if ($data['dc_description']) {
			$root->appendChild($doc->createElement('dcterms:description', $data['dc_description']));
		}
		if ($data['license_name'] || $data['license_url']) {
			$node = $root->appendChild($doc->createElement('dcterms:licence', @$data['license_name']));
			if ($data['license_url']) {
				$node->setAttribute('rdf:about', $data['license_url']);
			}
		}
		
		if ($data['website']) {
			$doc->documentElement->setAttribute('xmlns:owl', 'http://www.w3.org/2002/07/owl#');
			$node = $doc->createElement('owl:sameAs');
			$node->setAttribute('rdf:about', $data['website']);
			$root->appendChild($node);
		}
		
		if ($withCreator) {
			$tenant = $this->getTenant();
			$root->appendChild($doc->createElement('dcterms:creator', htmlspecialchars($tenant->name)))
				->setAttribute('rdf:about', $helper->serverUrl('/api/institution/'.$tenant->code));
		}
		return $doc;
	}
}
