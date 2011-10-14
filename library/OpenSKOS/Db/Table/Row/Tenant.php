<?php
class OpenSKOS_Db_Table_Row_Tenant extends Zend_Db_Table_Row
{
	public function getForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form->addElement('text', 'name');
			$form->setDefaults($this->toArray());
		}
		return $form;
	}
}
