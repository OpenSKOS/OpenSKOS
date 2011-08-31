<?php

class OpenSKOS_Application_Resource_Solr extends Zend_Application_Resource_ResourceAbstract
{
    const REGISTRY_KEY = 'OpenSKOS_Solr';

    public function init ()
    {
    	$instances = array();
    	$options = $this->getOptions();
    	$solr = new OpenSKOS_Solr($options);
    	Zend_Registry::set(self::REGISTRY_KEY, $solr);
    }
}