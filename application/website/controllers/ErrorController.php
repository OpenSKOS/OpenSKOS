<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        $this->getHelper('layout')->enableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/html; charset="utf-8"', true);
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
        
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            
            default:
            	$code = (int)$errors->exception->getCode();
            	if ((100 > $code) || (599 < $code)) {
                	$this->getResponse()->setHttpResponseCode(500);
	                $this->view->message = 'Application error';
            	} else {
            		$this->getResponse()->setHttpResponseCode($code);
	                $this->view->message = $errors->exception->getMessage();
            	}
            	
                break;
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->crit($this->view->message, $errors->exception);
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
		$this->getResponse()
			->setHeader('X-Error-Msg', $errors->exception->getMessage());
        
        if ($this->view->errorOnly) {
	        $this->getResponse()->setHeader('Content-Type', 'text/plain; charset="utf-8"', true);
       		$this->getHelper('layout')->disableLayout();
 	        $this->getHelper('viewRenderer')->setNoRender(true);
			echo $errors->exception->getMessage()."\n"; 
        }
        
		$this->view->request   = $errors->request;
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasPluginResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

