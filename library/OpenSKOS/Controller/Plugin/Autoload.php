<?php

class OpenSKOS_Controller_Plugin_Autoload extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		set_include_path(implode(PATH_SEPARATOR, array(
			APPLICATION_PATH . '/' . $request->getModuleName(),
			get_include_path() 
		)));
		$namespacePrefix = ucfirst($request->getModuleName()).'_';
//			exit($namespacePrefix.'Forms_');
		$loader = new OpenSKOS_Autoloader();
		
		Zend_Loader_Autoloader::getInstance()
			->pushAutoloader($loader, $namespacePrefix);
	}
}