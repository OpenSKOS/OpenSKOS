<?php
/**
 * OpenSKOS
*
* LICENSE
*
* This source file is subject to the GPLv3 license that is bundled
* with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://www.gnu.org/licenses/gpl-3.0.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@zend.com so we can send you a copy immediately.
*
* @category   OpenSKOS
* @package    OpenSKOS
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Mark Lindeman
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class OpenSKOS_Db_Table_Row_Job extends Zend_Db_Table_Row
{
	const STATUS_ERROR = 'ERROR';
	const STATUS_SUCCESS = 'SUCCESS';
	
	const JOB_TASK_IMPORT = 'import';
	const JOB_TASK_EXPORT = 'export';
	const JOB_TASK_HARVEST = 'harvest';
	const JOB_TASK_DELETE_CONCEPT_SCHEME = 'delete_concept_scheme';
	
	protected $parametersSerialized = null;
	
	public function getParam($key)
	{
		$params = $this->getParams();
		return $params && isset($params[$key]) ? $params[$key] : null;
	}
	
	public function getParams()
	{
		if (null === $this->parametersSerialized) {
			$this->parametersSerialized = unserialize($this->parameters);
		}
		return $this->parametersSerialized;
	}
	
	/**
	 * Sets job info.
	 *
	 * @param string $info
	 * @return OpenSKOS_Db_Table_Row_Job
	 */
	public function setInfo($info)
	{
		$this->info = $info;
		return $this;
	}
	
	/**
	 * Gets job info.
	 * 
	 * @return string
	 */
	public function getInfo()
	{
		return $this->info;
	}
		
	public function delete()
	{
		if ($this->task == self::JOB_TASK_IMPORT) {
			$params = $this->getParams();
			if (!@unlink($params['destination'] .'/'.$params['name'])) {
				throw new Zend_Db_Table_Row_Exception(_('Failed to delete file').' `'.$params['name'].'`');
			}
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
	
	public function getDisplayFileName()
	{
		$name = basename($this->getParam('name'));
		
		// Removes uniqid prefix
		if (strpos($name, '_') !== false) {
			$name = substr($name, strpos($name, '_') + 1);
		}
		return $name;
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
		if (null === $this->status) {
			$this->status = self::STATUS_SUCCESS;
		}
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
