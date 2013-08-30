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

class OpenSKOS_Solr_Client extends Zend_Http_Client
{
    /**
     * POST data encoding methods
     * 
     * These must be overriden from the parent class, since
     * there does not seem to be a method for setting these
     * thru the normal methods
     */
    const ENC_URLENCODED = 'application/x-www-form-urlencoded; charset=UTF-8';
    const ENC_FORMDATA   = 'multipart/form-data; charset=UTF-8';
    
    /**
     * Prepare the request headers
     *
     * @return array
     */
    protected function _prepareHeaders()
    {
        $headers = parent::_prepareHeaders();
        // Set the Content-Type header
        if (
            ($this->method == self::POST || $this->method == self::PUT) 
                &&
           (
                (! isset($this->headers[strtolower(self::CONTENT_TYPE)]) && isset($this->enctype))
                ||
                (isset($this->headers[strtolower(self::CONTENT_TYPE)]) && isset($this->enctype)
                    && ($this->enctype !== self::ENC_URLENCODED || $this->enctype !== self::ENC_FORMDATA)
                )
            )
        ) {
        	if (isset($this->headers[strtolower(self::CONTENT_TYPE)])) {
        		$ContentTypeHeader = $this->headers[strtolower(self::CONTENT_TYPE)];
        		switch ($ContentTypeHeader[1]) {
        			case parent::ENC_FORMDATA:
        				$enctype = self::ENC_FORMDATA;
        				break;
        			case parent::ENC_URLENCODED:
        				$enctype = self::ENC_URLENCODED;
        				break;
        			default:
        				$enctype = self::CONTENT_TYPE;
        				break;
        		}
        		unset($this->headers[strtolower(self::CONTENT_TYPE)]);
        	} else {
        		$enctype = $this->enctype;
        	}
            
            foreach ($headers as $key => $val) {
                if (0 === strpos($val, self::CONTENT_TYPE)) {
                    unset($headers[$key]);
                    break;
                }
            }
            
            $headers[] = self::CONTENT_TYPE . ': ' . $enctype;
        }
        return $headers;
    }
}