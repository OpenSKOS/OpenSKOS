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

class OpenSKOS_Db_Table_Row_User extends Zend_Db_Table_Row
{
	/**
	 * @var int
	 */
	const USER_HISTORY_SIZE = 100;
	
	/**
	 * @var int
	 */
	const USER_SELECTION_SIZE = 100;
	
	/**
	 * @return Zend_Form
	 */
	public function getForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form
				->addElement('hidden', 'id', array('required' => $this->id ? true : false))
				->addElement('text', 'tenant', array('label' => _('Tenant'), 'readonly' => true, 'disabled' => true))
				->addElement('text', 'name', array('label' => _('Name'), 'required' => true))
				->addElement('text', 'email', array('label' => _('E-mail'), 'required' => true))
				->addElement('password', 'pw1', array('label' => _('Password'), 'maxlength' => 100, 'size' => 15, 'validators' => array(array('StringLength', false, array(4, 30)), array('identical', false, array('token' => 'pw2')))))
				->addElement('password', 'pw2', array('label' => _('Password (check)'), 'maxlength' => 100, 'size' => 15, 'validators' => array(array('identical', false, array('token' => 'pw1')))))
				->addElement('select', 'role', array('label' => _('Role'), 'required' => true))
				->addElement('radio', 'type', array('label' => _('Usertype'), 'required' => true))
				->addElement('text', 'apikey', array('label' => _('API Key (required for API users)'), 'required' => false))
				->addElement('text', 'eppn', array('label' => _('eduPersonPrincipalName (for SAML authentication)'), 'required' => false))
				->addElement('multiselect', 'defaultSearchProfileIds', array('label' => _('Search Profile Id'), 'required' => false))
				->addElement('checkbox', 'disableSearchProfileChanging', array('label' => _('Disable changing search profile'), 'required' => false))
				->addElement('submit', 'submit', array('label'=>_('Submit')))
				->addElement('reset', 'reset', array('label'=>_('Reset')))
				->addElement('submit', 'cancel', array('label'=>_('Cancel')))
				->addElement('submit', 'delete', array('label'=>_('Delete'), 'onclick' => 'return confirm(\''._('Are you sure you want to delete this user?').'\');'))
				->addDisplayGroup(array('submit', 'reset', 'cancel', 'delete'), 'buttons')
				;
			$form->getElement('type')
				->addMultiOptions(array_combine(OpenSKOS_Db_Table_Users::$types, OpenSKOS_Db_Table_Users::$types))
				->setSeparator(' ');
			
			$form->getElement('role')->addMultiOptions(array_combine(OpenSKOS_Db_Table_Users::$roles, OpenSKOS_Db_Table_Users::$roles));
			
			$searchProfilesModel = new OpenSKOS_Db_Table_SearchProfiles();
			$select = $searchProfilesModel->select();
			if (Zend_Auth::getInstance()->hasIdentity()) {
				$select->where('tenant=?', Zend_Auth::getInstance()->getIdentity()->tenant);
			}
			$searchProfiles = $searchProfilesModel->fetchAll($select);
			$searchProfilesOptions = array();
			foreach ($searchProfiles as $profile) {
				$searchProfilesOptions[$profile->id] = $profile->name;
			}
			$form->getElement('defaultSearchProfileIds')->addMultiOptions($searchProfilesOptions);
			
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueEmail'));
			$validator
				->setMessage(_("there is already a user with e-mail address '%value%'"), Zend_Validate_Callback::INVALID_VALUE);

			$form->getElement('email')
				->addValidator($validator)
				->addValidator(new Zend_Validate_EmailAddress());
			
			
			$validator = new Zend_Validate_Callback(array($this, 'needApiKey'));
			$validator
				->setMessage(_("An API Key is required for users that have access to the API"), Zend_Validate_Callback::INVALID_VALUE);
				
			$form->getElement('type')
				->addValidator($validator, true);
			
			$validator = new Zend_Validate_Callback(array($this->getTable(), 'uniqueApiKey'));
			$validator
				->setMessage(_("there is already a user with API key '%value%'"), Zend_Validate_Callback::INVALID_VALUE);
			$form->getElement('apikey')
				->addValidator(new Zend_Validate_Alnum())
				->addValidator($validator)
				->addValidator(new Zend_Validate_StringLength(array('min' => 6)));
			
                        $userData = $this->toArray();
                        $userData['defaultSearchProfileIds'] = explode(', ', $userData['defaultSearchProfileIds']);
			$form->setDefaults($userData);
			
			if (!$this->id || (Zend_Auth::getInstance()->hasIdentity() && Zend_Auth::getInstance()->getIdentity()->id == $this->id)) {
				$form->removeElement('delete');
				if ( ! OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed('editor.users', 'manage')) {
					// Currently only password edit is allowed.
					$form->removeElement('name');
					$form->removeElement('email');
					$form->removeElement('role');
					$form->removeElement('type');
					$form->removeElement('apikey');
					$form->removeElement('eppn');
					$form->removeElement('defaultSearchProfileIds');
					$form->removeElement('disableSearchProfileChanging');
				}
			}
		}
		
		return $form;
	}
        	
	public function needApiKey($usertype, Array $data)
	{
		if (OpenSKOS_Db_Table_Users::isApiAllowed($usertype)) {
			return isset($data['apikey']) && trim($data['apikey']);
		} else {
			return true;
		}
	}
	
	public function setPassword($password)
	{
		$this->password = md5($password);
		return $this;
	}
	
	public function isApiAllowed()
	{
		return OpenSKOS_Db_Table_Users::isApiAllowed($this->type);
	}
	
	/**
	 * 
	 * @param A Zend Resource identifier $resource
	 * @param A Zend Privilege identifier $privilege
	 * @return boolean
	 */
	public function isAllowed($resource = null, $privilege)
	{
		$key = OpenSKOS_Application_Resource_Acl::REGISTRY_KEY;
		if (Zend_Registry::isRegistered($key)) {
			return Zend_Registry::get($key)->isAllowed($this->role, $resource, $privilege);
		}
	}
	
	public function didIBlockMyselfFromTheEditor()
	{
		$id = Zend_Auth::getInstance()->getIdentity()->id;
		if ($id != $this->id) return false;
		return !OpenSKOS_Db_Table_Users::isEditorAllowed($this->type, $this->role);
	}
	
	public function setSearchOptions($optionsData)
	{
            if (isset($optionsData['searchProfileId'])
                    && ! $this->isAllowedToUseSearchProfile($optionsData['searchProfileId'])) {
                
                throw new Exception('The selected search profile is not allowed for that user.');
            }
            
            $this->searchOptions = serialize($optionsData);
            $this->save();

            $userOptions = new Zend_Session_Namespace('userOptions');
            $userOptions->searchOptions = $optionsData;

            return $this;
	}
	
	public function getSearchOptions($loadFromDb = false)
	{
            $searchOptions = array();
            if ( ! $loadFromDb) {
                $userOptions = new Zend_Session_Namespace('userOptions');
                if (isset($userOptions->searchOptions)) {
                    $searchOptions = $userOptions->searchOptions;
                } else {
                    if ( ! empty($this->searchOptions)) {
                        $optionsData = unserialize($this->searchOptions);
                        $userOptions->searchOptions = $optionsData;
                        if ( ! empty($optionsData)) {
                            $searchOptions = $optionsData;
                        }
                    }
                }
            } else {
                if ( ! empty($this->searchOptions)) {
                    $searchOptions = unserialize($this->searchOptions);
                }
            }

            // If the user has old search profile settings wich are not allowed for him - use the first of the default search profiles.
            if (isset($searchOptions['searchProfileId'])
                && ! $this->isAllowedToUseSearchProfile($searchOptions['searchProfileId'])) {
                
                $searchOptions = array();
                
                $firstProfile = $this->getFirstDefaultSearchProfile();                
                if ($firstProfile !== null) {
                    $searchOptions = $firstProfile->getSearchOptions();
                }
            }
            
            return $searchOptions;
	}
	
	public function getUserHistory()
	{
		$userOptions = new Zend_Session_Namespace('userOptions');
		$conceptUuids =  isset($userOptions->userHistory) ? $userOptions->userHistory: array();		
		return Api_Models_Concepts::factory()->getEnumeratedConcepts($conceptUuids);
	}
	
	public function updateUserHistory($identifier)
	{
		$userOptions = new Zend_Session_Namespace('userOptions');
		if (!isset($userOptions->userHistory) || !is_array($userOptions->userHistory))
			$userOptions->userHistory = array();
		array_unshift($userOptions->userHistory, $identifier);
		$userOptions->userHistory = array_unique($userOptions->userHistory);
		
		if (count($userOptions->userHistory) > self::USER_HISTORY_SIZE)
			array_pop($userOptions->userHistory); 
	}
	
	public function clearUserHistory()
	{
		$userOptions = new Zend_Session_Namespace('userOptions');
		if (isset($userOptions->userHistory))
			$userOptions->userHistory = array();
	}
	
	/**
	 * Adds multiple concepts to the user's selection - both in session and in the db.
	 * 
	 * @param array $uuids The uuids of the concepts
	 * @return bool True if concept is added. False if size is reached and concept is not added.
	 */
	public function addConceptsToSelection($uuids)
	{
		$selection = $this->getConceptsSelectionUuids();
		
		$isSelectionChanged = false;
		if ( ! empty($uuids)) {
			foreach ($uuids as $uuid) {
				if ( ! in_array($uuid, $selection)) {				
					$selection[] = $uuid;
					$isSelectionChanged = true;
				}
			}
		}
		
		if ($isSelectionChanged && count($selection) > self::USER_SELECTION_SIZE) {
			return false;
		}
		
		if ($isSelectionChanged) {
			$userOptions = new Zend_Session_Namespace('userOptions');
			$userOptions->conceptsSelection = $selection;
			$this->conceptsSelection = serialize($selection);
			$this->save();
		}
		
		return true;
	}
	
	/**
	 * Gets the concepts from the selection
	 * 
	 * @return array An array of Api_Models_Concept objects
	 */
	public function getConceptsSelection() 
	{
		$selection = $this->getConceptsSelectionUuids();		
		return Api_Models_Concepts::factory()->getEnumeratedConcepts($selection);
	}
	
	/**
	 * Gets the concepts selection uuids from session or from database (if not in session).
	 * Adds them to session if they are not there.
	 *
	 * @return array
	 */
	public function getConceptsSelectionUuids() 
	{
		$userOptions = new Zend_Session_Namespace('userOptions');
		if (isset($userOptions->conceptsSelection)) {
			return $userOptions->conceptsSelection;
		} else {
			if ( ! empty($this->conceptsSelection)) {
				$selection = unserialize($this->conceptsSelection);
				$userOptions->conceptsSelection = $selection;
				return $selection;
			} else {
				return array();
			}
		}
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	public function clearConceptsSelection() 
	{
		$userOptions = new Zend_Session_Namespace('userOptions');
		$userOptions->conceptsSelection = array();
		$this->conceptsSelection = serialize($userOptions->conceptsSelection);
		$this->save();
	}
	
	/**
	 * Removes a single concept from user's selection.
	 * 
	 * @param string $uuid
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	public function removeConceptFromSelection($uuid) 
	{
		$selection = $this->getConceptsSelectionUuids();
		$conceptInd = array_search($uuid, $selection);
		if ($conceptInd !== false) {
			unset($selection[$conceptInd]);
			
			$userOptions = new Zend_Session_Namespace('userOptions');
			$userOptions->conceptsSelection = $selection;
			$this->conceptsSelection = serialize($userOptions->conceptsSelection);
			$this->save();
		}
	}
	
	/**
	 * Sets the user search options to his default profile options if they are not already set.
	 * 
	 */
	public function applyDefaultSearchProfile()
	{
		if ( ! empty($this->defaultSearchProfileIds) && empty($this->searchOptions)) {
			$profile = $this->getFirstDefaultSearchProfile();
			$options = unserialize($profile->searchOptions);
			$options['searchProfileId'] = $profile->id;
			$this->searchOptions = serialize($options);
			$this->save();
		}
	}
        
        /**
         * Gets list of the search profiles for the user.
         * @return OpenSKOS_Db_Table_Row_SearchProfile[]
         */
        public function listDefaultSearchProfiles()
        {
            $profiles = array();
            if (! empty($this->defaultSearchProfileIds)) {
                $defaultSearchProfilesIds = explode(', ', $this->defaultSearchProfileIds);
                
                $profilesModels = new OpenSKOS_Db_Table_SearchProfiles();
                
                foreach ($defaultSearchProfilesIds as $profileId) {
                    $profiles[] = $profilesModels->find($profileId)->current();
                }
            }
            
            return $profiles;
        }
        
        /**
         * Gets the first of the search profiles for the user.
         * @return OpenSKOS_Db_Table_Row_SearchProfile
         */
        public function getFirstDefaultSearchProfile()
        {
            if (! empty($this->defaultSearchProfileIds)) {
                $defaultSearchProfilesIds = explode(', ', $this->defaultSearchProfileIds);
                
                $profilesModels = new OpenSKOS_Db_Table_SearchProfiles();
                
                return $profilesModels->find($defaultSearchProfilesIds[0])->current();
            } else {
                return null;
            }
        }
        
        /**
         * Gets the first of the search profiles for the user.
         * @return OpenSKOS_Db_Table_Row_SearchProfile
         */
        public function isAllowedToUseSearchProfile($profileKey)
        {
            if ($this->disableSearchProfileChanging 
                    && ! empty($this->defaultSearchProfileIds)) {
                
                $defaultSearchProfilesIds = explode(', ', $this->defaultSearchProfileIds);                
                return in_array($profileKey, $defaultSearchProfilesIds);
            } else {
                return true;
            }
        }
}