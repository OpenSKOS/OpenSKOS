<?php
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
	
	public function getId()
	{
		return $this->tenant.':'.$this->code;
	}
	
	public function getClasses(OpenSKOS_Db_Table_Row_Tenant $tenant = null)
	{
		if (null === $tenant) {
			$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		}
		return $this->getTable()->getClasses($tenant, $this);
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
				->addElement('text', 'code', array('label' => 'Code', 'required' => true))
				->addElement('text', 'dc_title', array('label' => 'Title', 'required' => true))
				->addElement('textarea', 'dc_description', array('label' => 'Description', 'cols' => 80, 'row' => 5))
				->addElement('text', 'website', array('label' => 'Website'))
				->addElement('submit', 'submit', array('label'=>'Submit'))
				->addElement('reset', 'reset', array('label'=>'Reset'))
				->addElement('submit', 'cancel', array('label'=>'Cancel'))
				->addElement('submit', 'delete', array('label'=>'Delete', 'onclick' => 'return confirm(\'Are you sure you want to delete this collection and all of it\\\'s Concepts?\');'))
				->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
				;
			
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueCode'));
			$validator->setMessage("code '%value%' already exists", Zend_Validate_Callback::INVALID_VALUE);
			$form->getElement('code')->addValidator($validator);
			
			$form->setDefaults($this->toArray());
		}
		return $form;
	}
	
	public function delete()
	{
		$collection_id = $this->id;
		$result = parent::delete();
		$solr = OpenSKOS_Solr::getInstance()->delete('collection:'.$collection_id);
		return $result;
	}
}
