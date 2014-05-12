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
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * Handles jsonp context
 */
class OpenSKOS_Controller_Action_Context_Jsonp
{
    /**
     * What is the param key for the callback function.
     */
    const CALLBACK_FUNCTION_PARAM = 'callback';
    
    /**
     * The context switch action helper from whicht the context was initialized.
     * @var Zend_Controller_Action_Helper_ContextSwitch 
     */
    protected $contextSwitch;
    
    /**
     * The jsonp callback function name.
     * @var string 
     */
    protected $callbackFunction;
    
    /**
     * Construct.
     * @param Zend_Controller_Action_Helper_ContextSwitch $contextSwitch The context switch action helper from whicht the context was initialized.
     */
    public function __construct(Zend_Controller_Action_Helper_ContextSwitch $contextSwitch)
    {
        $this->contextSwitch = $contextSwitch;
    }

    /**
     * Get jsonp context settings for the addContext method.
     * @return type
     */
    public function getContextSettings()
    {
        return array(
            'suffix'    => 'jsonp',
            'headers'   => array('Content-Type' => 'application/javascript'),
            'callbacks' => array(
                'init' => array($this, 'initContext'),
                'post' => array($this, 'postContext')
            )
        );
    }
    
    /**
     * Inits the jsonp context.
     * Calls initJsonContext
     */
    public function initContext()
    {
        $this->contextSwitch->initJsonContext();
        
        $request = $this->contextSwitch->getRequest();
        
        $callbackFunction = $request->getParam(self::CALLBACK_FUNCTION_PARAM);
        if (empty($callbackFunction)) {
            throw new Zend_Controller_Action_Exception(
                'The callback function name for jsonp response must be passed as parameter "' . self::CALLBACK_FUNCTION_PARAM . '".'
            );
        }
        
        $this->callbackFunction = $this->sanitzeCallbackFunction($callbackFunction);
    }
    
    /**
     * JSONP post processing.
     * Mainly calls postJsonContext
     * 
     * @throws Zend_Controller_Action_Exception
     */
    public function postContext()
    {
        $this->contextSwitch->postJsonContext();
        
        $response = $this->contextSwitch->getResponse();        
        
        $response->setBody(
            $this->callbackFunction . '(' . $response->getBody() . ');'
        );
    }
    
    /**
     * Sanitizes jsonp callback function to prevent xss vulnerability
     * @param string $callbackFunction
     * @return string
     */
    protected function sanitzeCallbackFunction($callbackFunction)
    {
        if (preg_match('/[^0-9a-zA-Z\$_]|^(abstract|boolean|break|byte|case|catch|char|class|const|continue|debugger|default|delete|do|double|else|enum|export|extends|false|final|finally|float|for|function|goto|if|implements|import|in|instanceof|int|interface|long|native|new|null|package|private|protected|public|return|short|static|super|switch|synchronized|this|throw|throws|transient|true|try|typeof|var|volatile|void|while|with|NaN|Infinity|undefined)$/', $callbackFunction)) {
            throw new Zend_Controller_Action_Exception(
                'The function name "' . $callbackFunction . '" is not considered a valid safe callback function name. It must be only a function identifier like "myCallback_1234" (and not a reserved word).'
            );
        }
        return $callbackFunction;
    }
    
    /**
     * Returns a new instance of the jsonp context.
     * @param Zend_Controller_Action_Helper_ContextSwitch $contextSwitch
     * @return OpenSKOS_Controller_Action_Context_Jsonp
     */
    public static function factory(Zend_Controller_Action_Helper_ContextSwitch $contextSwitch)
    {
        return new OpenSKOS_Controller_Action_Context_Jsonp($contextSwitch);
    }
}