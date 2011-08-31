<?php

class OpenSKOS_Autoloader implements Zend_Loader_Autoloader_Interface
{
	public function autoload($class)
	{
		if (preg_match('/^([a-z]+)_(forms|models|plugins)_([a-z]+)$/i', $class, $match)) {
			list(, $module, $type, $filename) = $match;
			$path = APPLICATION_PATH 
				. '/' . strtolower($module) 
				. '/' . strtolower($type) 
				. '/' . $filename . '.php';
			if (file_exists($path)) {
				require_once $path;
			}
		}
	}
}