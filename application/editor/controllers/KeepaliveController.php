<?php
/**
 * Keepalive controller
 *
 */
class Editor_KeepaliveController extends Zend_Controller_Action
{
	/**
	 * Keepalive action, sessions will not time out
	 *
	 */
	public function indexAction()
	{
		require_once 'Zend/Layout.php';
        if (null !== ($layout = Zend_Layout::getMvcInstance())) {
            $layout->disableLayout();
        }
	}
}