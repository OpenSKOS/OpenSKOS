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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Provides the possibility to send attached file to the client.
 *
 * @uses actionHelper OpenSKOS_Controller_Action_Helper
 */
class OpenSKOS_Controller_Action_Helper_File extends Zend_Controller_Action_Helper_Abstract {

	/**
	 * Sends a file as attachment to the client.
	 * Strategy pattern: call helper as broker method
	 * 
	 * @param string $fileName The name of the file for the client. 
	 * @param string $fileContent
	 * @param string $contentType, optianal The MIME content type. Default is "application/octet-stream"
	 */
	public function sendFileContent($fileName, $fileContent, $contentType = 'application/octet-stream') 
	{
		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');			
		echo $fileContent;
		exit;
	}
	
	/**
	 * Sends a file as attachment to the client.
	 * Strategy pattern: call helper as broker method
	 *
	 * @param string $fileName The name of the file for the client.
	 * @param string $filePath
	 * @param string $contentType, optianal The MIME content type. Default is "application/octet-stream"
	 */
	public function sendFile($fileName, $filePath, $contentType = 'application/octet-stream')
	{
		header('Content-Type: ' . $contentType);
		header('Content-Disposition: attachment; filename="' . $fileName . '"');		
		fpassthru(fopen($filePath, 'r'));
		exit;
	}
}