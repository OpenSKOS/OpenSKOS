<?php
class OpenSKOS_Db_Table_Row_Tenant extends Zend_Db_Table_Row
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
				->addElement('text', 'name', array('label' => 'Name', 'required' => true))
				->addElement('text', 'website', array('label' => 'Website'))
				->addElement('text', 'email', array('label' => 'E-mail'))
				->addElement('text', 'streetAddress', array('label' => 'Street Address'))
				->addElement('text', 'locality', array('label' => 'Locality'))
				->addElement('text', 'postalCode', array('label' => 'Postal Code'))
				->addElement('text', 'countryName', array('label' => 'Country Name'))
				->addElement('submit', 'submit', array('label'=>'Submit'))
				;
			
			$form->getElement('email')->addValidator(new Zend_Validate_EmailAddress());
			$form->getElement('postalCode')->addValidator(new Zend_Validate_PostCode());
			
			$form->setDefaults($this->toArray());
		}
		return $form;
	}
}
