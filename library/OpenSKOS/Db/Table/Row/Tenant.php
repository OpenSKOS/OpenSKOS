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

class OpenSKOS_Db_Table_Row_Tenant extends Zend_Db_Table_Row
{
	/**
	 * @return Zend_Form
	 */
	public function getForm()
	{
		static $form;
		if (null === $form) {
			$form = new Zend_Form();
			$form
				->addElement('text', 'name', array('label' => _('Name'), 'required' => true))
				->addElement('text', 'organisationUnit', array('label' => _('Organisation unit')))
				->addElement('text', 'website', array('label' => _('Website')))
				->addElement('text', 'email', array('label' => _('E-mail')))
				->addElement('text', 'streetAddress', array('label' => _('Street Address')))
				->addElement('text', 'locality', array('label' => _('Locality')))
				->addElement('text', 'postalCode', array('label' => _('Postal Code')))
				->addElement('text', 'countryName', array('label' => _('Country Name')))
				->addElement('submit', 'submit', array('label'=>_('Submit')))
				;
			
			$form->getElement('email')->addValidator(new Zend_Validate_EmailAddress());
			try {
    			$form->getElement('postalCode')->addValidator(new Zend_Validate_PostCode());
			} catch (Zend_Validate_Exception $e) {
			    //no valid locale found, so be it...
			}
			
			$form->setDefaults($this->toArray());
		}
		return $form;
	}
	
	public function getConceptsPerCollection()
	{
		$solr = OpenSKOS_Solr::getInstance();
		$q = 'class:Concept tenant:'.$this->code;
		$result = $solr->search($q, array(
			'rows' => 0,
			'facet' => 'true',
			'facet.field' => 'collection'
		));
		$classes = array();
		return $result['facet_counts']['facet_fields']['collection'];
	}
	
	/**
	 * @return DOMDocument;
	 */
	public static function getRdfDocument($forOAI = false)
	{
		$doc = new DOMDocument();
		if (true === $forOAI) {
    		$doc->appendChild($doc->createElement('rdf:rdf'));
    		$doc->documentElement->appendChild($doc->createElement('RDF'));
    		$doc->documentElement->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    		$doc->documentElement->setAttribute('xmlns:v', 'http://www.w3.org/2006/vcard/ns#');
		} else {
    		$doc->appendChild($doc->createElement('rdf:RDF'));
    		$doc->documentElement->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    		$doc->documentElement->setAttribute('xmlns:v', 'http://www.w3.org/2006/vcard/ns#');
    	}
    		
		return $doc;
	}
	
	public function toRdf($forOAI = false)
	{
		$helper = new Zend_View_Helper_ServerUrl();
		$about = $helper->serverUrl('/api/institution/'.$this->code);
		$data = array();
		foreach ($this as $key => $val) {
			$data[$key] = htmlspecialchars($val);
		}
		
		$doc = self::getRdfDocument($forOAI);
		$rootNode = true === $forOAI 
		    ? $doc->documentElement->firstChild
		    : $doc->documentElement;
		
		$VCard = $rootNode->appendChild($doc->createElement('v:Vcard'));
		$VCard->setAttribute('rdf:about', $about);
		$VCard->appendChild($doc->createElement('v:fn', $data['name']));
		
		if ($this->website) {
			$VCard->setAttribute('v:url', $this->website);
		}
		$node  = $VCard->appendChild($doc->createElement('rdf:Description'));
		$node->appendChild($doc->createElement('v:organisation-name', $data['name']));
		if ($this->organisationUnit) {
			$node->appendChild($doc->createElement('v:organisation-unit', $data['organisationUnit']));
		}
		
		if ($this->email) {
			$VCard->appendChild($doc->createElement('v:email'))
				->setAttribute('rdf:about', 'mailto:'.$this->email);
		}
		
		
		$adr = $doc->createElement('v:adr');
		foreach (array('street-address', 'locality', 'postal-code', 'country-name') as $name) {
			$dbName = preg_replace_callback(
				'/\-([a-z])/', create_function(
	            '$matches',
	            'return strtoupper($matches[1]);'
        	), $name);
			if ($this->$dbName) {
				$adr->appendChild($doc->createElement('v:'.$name, $data[$dbName]));
			}
		}
		if ($adr->childNodes->length) {
			$VCard->appendChild($adr);
		}
		
		return $doc;
	}

	public function delete()
	{
		$tenant = $this->code;
		$result = parent::delete();
		$solr = OpenSKOS_Solr::getInstance()->delete('tenant:'.$tenant);
		return $result;
	}
		
}
