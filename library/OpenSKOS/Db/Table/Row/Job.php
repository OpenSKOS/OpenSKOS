<?php
class OpenSKOS_Db_Table_Row_Job extends Zend_Db_Table_Row
{
	const STATUS_ERROR = 'ERROR';
	const STATUS_SUCCESS = 'SUCCESS';
	
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
			throw new Zend_Db_Table_Row_Exception(_('Failed to delete file').' `'.$params['name'].'`');
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
	
	public function getFile()
	{
		$path = realpath($this->getParam('destination').DIRECTORY_SEPARATOR.$this->getParam('name'));
		
		return $path ? $path : null;
	}
	
	public function isZip($path = null)
	{
		if (null === $path) {
			$path = $this->getFile();
		}
		if (null === $path) return;
		return 0 === strpos($this->getMime($path), 'application/zip');
	}
	
	public function getMime($path = null)
	{
		if (!class_exists('finfo')) {
			throw new Zend_Db_Table_Row_Exception('Finfo required (see http://www.php.net/manual/en/book.fileinfo.php)');
		}
		if (null === $path) {
			$path = $this->getFile();
		}
		if (null === $path) return;
		$finfo = new finfo(FILEINFO_MIME);
		return $finfo->file($path);
	}

	/**
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	public function getUser()
	{
		static $user;
		if (null === $user) {
			$user = $this->findParentRow('OpenSKOS_Db_Table_Users');
		}
		return $user;
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	public function finish()
	{
		$this->finished = new Zend_Db_Expr('NOW()');
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	public function start()
	{
		$this->started = new Zend_Db_Expr('NOW()');
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	public function error($msg)
	{
		$this->status = self::STATUS_ERROR;
		$this->info = $msg;
		return $this;
	}
}
