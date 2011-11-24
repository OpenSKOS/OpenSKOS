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

class OpenSKOS_View_Helper_Highlight extends Zend_View_Helper_Abstract
{
	static $instance;
	
	protected $_language, $_source, $_title;
	
	protected $_started = false, $_ended = false;
	
	public function highlight($language = null, $title = null)
	{
		if (null === self::$instance || self::$instance->_ended) {
			if (null === $language) {
				throw new Zend_View_Helper_Partial_Exception('I need to know what language you speak!');
			}
			self::$instance = new OpenSKOS_View_Helper_Highlight();
			self::$instance->_language = $language;
		}
		if (null !== $title) {
			self::$instance->_title = $title;
		}
		return self::$instance;
	}
	
	public function setTitle($title)
	{
		$this->_title = $title;
		return $this;
	}
	
	public function captureStart()
	{
		$this->_started = true;
		ob_start();
		return $this;
	}
	
	public function captureEnd()
	{
		if (!$this->_started) {
			throw new Zend_View_Helper_Partial_Exception('Call captureStart() before captureEnd()');
		}
		if (!$this->_ended) {
			$this->_source = trim(ob_get_contents(), "\n");
			ob_end_clean();
			$this->_ended = true;
		}
		return $this;
	}
	
	public function __toString()
	{
		$this->captureEnd();
		require_once APPLICATION_PATH . '/../library/geshi/geshi.php';
		$geshi = new GeSHi($this->_source, $this->_language);
		$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
		$html = '<div class="example">';
		if ($this->_title) {
			$html .= '<p class="title">' . $this->_title .'</p>';
		}
		$html .= '<div class="code">';
		$html .= $geshi->parse_code();
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}
}