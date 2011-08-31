<?php
class Dashboard_Forms_Login extends Zend_Form
{
   public function init() {
        $this->setName("sign");
        $this->setMethod('post');
        $this->addElement('text', 'tenant', array(
            'filters' => array('StringTrim', 'StringToLower'),
            'validators' => array(array('StringLength', false, array(2, 10))),
            'required' => true,
            'label' => 'Institution code',
        ));
        $this->addElement('text', 'username', array(
            'filters' => array('StringTrim', 'StringToLower'),
            'validators' => array(array('StringLength', false, array(2, 10))),
            'required' => true,
            'label' => 'Username',
        ));
        $this->addElement('password', 'password', array(
            'filters' => array('StringTrim'),
            'validators' => array(array('StringLength', false, array(4, 30))),
            'required' => true,
            'label' => 'Password',
        ));
//        $this->addElement('hash', 'csrf', array('ignore' => true));
        $this->addElement('submit', 'login', array(
            'required' => false,
            'ignore' => true,
            'label' => 'Login',
        ));
    }
    
    /**
	 * @return Dashboard_Forms_Login
	 */
	public static function getInstance()
	{
		static $instance;
		
		if (null === $instance) {
			$instance = new Dashboard_Forms_Login();
		}
		
		return $instance;
	}
}