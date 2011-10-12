<?php
class OpenSKOS_Db_Table_CollectionHasNamespaces extends Zend_Db_Table 
{
	protected $_name = 'collection_has_namespace';
	protected $_sequence = false;
	
	protected $_referenceMap    = array(
        'Collections' => array(
            'columns'           => array('collection'),
            'refTableClass'     => 'OpenSKOS_Db_Table_Collections',
            'refColumns'        => array('id')
        ),
        'Namespaces' => array(
            'columns'           => array('namespace'),
            'refTableClass'     => 'OpenSKOS_Db_Table_Namespaces',
            'refColumns'        => array('prefix')
        )
    );
}