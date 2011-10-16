<?php
class OpenSKOS_Db_Table_Row_User extends Zend_Db_Table_Row
{
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
				->addElement('text', 'name', array('label' => _('Name'), 'required' => true))
				->addElement('text', 'email', array('label' => _('E-mail'), 'required' => true))
				->addElement('password', 'pw1', array('label' => _('Password'), 'maxlength' => 100, 'size' => 15, 'validators' => array(array('identical', false, array('token' => 'pw2')))))
				->addElement('password', 'pw2', array('label' => _('Password (check)'), 'maxlength' => 100, 'size' => 15, 'validators' => array(array('identical', false, array('token' => 'pw1')))))
				->addElement('radio', 'type', array('label' => _('Usertype'), 'required' => true))
				->addElement('text', 'apikey', array('label' => _('API Key (required for API users)'), 'required' => false))
				->addElement('submit', 'submit', array('label'=>_('Submit')))
				->addElement('reset', 'reset', array('label'=>_('Reset')))
				->addElement('submit', 'cancel', array('label'=>_('Cancel')))
				->addElement('submit', 'delete', array('label'=>_('Delete'), 'onclick' => 'return confirm(\''._('Are you sure you want to delete this user?').'\');'))
				->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
				;
			$form->getElement('type')
				->addMultiOptions(array_combine(OpenSKOS_Db_Table_Users::$types, OpenSKOS_Db_Table_Users::$types))
				->setSeparator(' ');
			
			if (!$this->id || (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->id == $this->id)) {
				$form->removeElement('delete');
			}
			
			if (!$this->id) {
				$form->getElement('pw1')->setRequired(true);
			}
			
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueEmail'));
			$validator
				->setMessage(_("there is already a user with e-mail address '%value%'"), Zend_Validate_Callback::INVALID_VALUE);

			$form->getElement('email')
				->addValidator($validator)
				->addValidator(new Zend_Validate_EmailAddress());
			
			
			$validator = new Zend_Validate_Callback(array($this, 'needApiKey'));
			$validator
				->setMessage(_("An API Key is required for users that have access to the API"), Zend_Validate_Callback::INVALID_VALUE);
				
			$form->getElement('type')
				->addValidator($validator, true);
								
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueApiKey'));
			$validator
				->setMessage(_("there is already a user with API key '%value%'"), Zend_Validate_Callback::INVALID_VALUE);
			$form->getElement('apikey')
				->addValidator(new Zend_Validate_Alnum())
				->addValidator($validator)
				->addValidator(new Zend_Validate_StringLength(array('min' => 6)));
			
			$form->setDefaults($this->toArray());
		}
		
		return $form;
	}
	
	public function needApiKey($usertype, Array $data)
	{
		if (OpenSKOS_Db_Table_Users::isApiAllowed($usertype)) {
			return isset($data['apikey']) && trim($data['apikey']);
		} else {
			return true;
		}
	}
	
	public function isDashboardAllowed()
	{
		return OpenSKOS_Db_Table_Users::isDashboardAllowed($this->type);
	}
	
	public function isApiAllowed()
	{
		return OpenSKOS_Db_Table_Users::isApiAllowed($this->type);
	}
	
	public function doNotBlockYourselfFromTheDashboard($type, $data)
	{
		if (!Zend_Auth::getInstance()->hasIdentity() || !$data['id']) return true;
		$id = Zend_Auth::getInstance()->getIdentity()->id;
		if ($id != $data['id']) return true;
		return OpenSKOS_Db_Table_Users::isDashboardAllowed($type);
	}
	
}