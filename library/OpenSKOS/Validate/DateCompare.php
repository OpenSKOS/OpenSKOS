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

/**
 * Usage:
 * $element->addValidator(new OpenSKOS_Validate_DateCompare('12/12/2014')); //exact match
 * $element->addValidator(new OpenSKOS_Validate_DateCompare('startdate','enddate')); //between dates
 * $element->addValidator(new OpenSKOS_Validate_DateCompare('startdate',true)); //not later
 * $element->addValidator(new OpenSKOS_Validate_DateCompare('startdate',false)); //not earlier
 */

class OpenSKOS_Validate_DateCompare extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const NOT_SAME      = 'notSame';
    const MISSING_TOKEN = 'missingToken';
    const NOT_LATER = 'notLater';
    const NOT_EARLIER = 'notEarlier';
    const NOT_BETWEEN = 'notBetween';
    const INVALID_TOKEN_FIELD = 'invalidToken';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_SAME      => "The date '%token%' does not match the given '%value%'",
        self::NOT_BETWEEN      => "The date '%token%' is not in the valid range",
        self::NOT_LATER      => "The date '%token%' is not later than '%value%'",
        self::NOT_EARLIER      => "Invalid date range. Please adjust your choice.",
        self::MISSING_TOKEN => 'No date was provided to match against',
    	self::INVALID_TOKEN_FIELD => "Invalid date found into a date range field."
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'token' => '_tokenString'
    );

    /**
     * Original token against which to validate
     * @var string
     */
    protected $_tokenString;
    protected $_token;
    protected $_compare;

    /**
     * Sets validator options
     *
     * @param  mixed $token
     * @param  mixed $compare
     * @return void
     */
    public function __construct($token = null, $compare=true)
    {
        if (null !== $token) {
            $this->setToken($token);
            $this->setCompare($compare);
        }
    }

    /**
     * Set token against which to compare
     *
     * @param  mixed $token
     * @return Zend_Validate_Identical
     */
    public function setToken($token)
    {
        $this->_tokenString = (string) $token;
        $this->_token       = $token;
        return $this;
    }

    /**
     * Retrieve token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Set compare against which to compare
     *
     * @param  mixed $compare
     * @return Zend_Validate_Identical
     */
    public function setCompare($compare)
    {
        $this->_compareString = (string) $compare;
        $this->_compare       = $compare;
        return $this;
    }

    /**
     * Retrieve compare
     *
     * @return string
     */
    public function getCompare()
    {
        return $this->_compare;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue((string) $value);
        $token        = $this->getToken();

        if ($token === null) {
            $this->_error(self::MISSING_TOKEN);
            return false;
        }

        if (isset($context[$token]) && !empty($context[$token])) {
        	//we verify the token as validator order execution means that we could get here before the
        	//date validation call for the corresponding field is made
        	$token = $context[$token];
        	$validator = new Zend_Validate_Date(OpenSKOS_Solr_Queryparser_Editor::OPTIONS_DATE_FORMAT);
        	if (!$validator->isValid($token)) {
        		$this->_error(self::INVALID_TOKEN_FIELD);
        		return false;
        	}
        } else {
        	return true;
        }
        
        $date1=new Zend_Date($value);
        $date2=new Zend_Date($token);
        if ($this->getCompare()===true){

            if ($date1->compare($date2)<0 || $date1->equals($date2)){

                $this->_error(self::NOT_LATER);
                return false;
            }
        }else if ($this->getCompare()===false){
            if ($date1->compare($date2)>0 || $date1->equals($date2)){
                $this->_error(self::NOT_EARLIER);
                return false;
            }
        }else if ($this->getCompare()===null){
            if (!$date1->equals($date2)){
                $this->_error(self::NOT_SAME);
                return false;
            }
        }else{
            $date3=new Zend_Date($this->getCompare());

            if ($date1->compare($date2)<0 || $date1->compare($date3)>0){
                $this->_error(self::NOT_BETWEEN);
                return false;
            }
        }


        return true;
    }
}
