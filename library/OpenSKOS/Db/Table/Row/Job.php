<?php
class OpenSKOS_Db_Table_Row_Job extends Zend_Db_Table_Row
{
	public function getParam($key)
	{
		$params = $this->getParams();
		return $params && isset($params[$key]) ? $params[$key] : null;
	}
	
	public function getParams()
	{
		static $params;
		if (null === $params) {
			$params = unserialize($this->parameters);
		}
		return $params;
	}
	
	public function delete()
	{
		$params = $this->getParams();
		if (!@unlink($params['destination'] .'/'.$params['name'])) {
			throw new Zend_Db_Table_Row_Exception('Failed to delete file `'.$params['name'].'`');
		}
		return parent::delete();
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Collection
	 */
	public function getCollection()
	{
		static $collection;
		if (null === $collection) {
			$collection = $this->findParentRow('OpenSKOS_Db_Table_Collections');
		}
		return $collection;
	}

	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	public function getUser()
	{
		static $user;
		if (null === $user) {
			$user = $this->findParentRow('OpenSKOS_Db_Table_Users');
		}
		return $user;
	}
}
