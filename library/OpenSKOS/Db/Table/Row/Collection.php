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
	
	public function addNamespace(OpenSKOS_Db_Table_Row_Namespace $namespace)
	{
		$model = new OpenSKOS_Db_Table_CollectionHasNamespaces();
		$model->createRow(array(
			'collection' => $this->id,
			'namespace' => $namespace->prefix
		))->save();
		return $this;
	}
}
