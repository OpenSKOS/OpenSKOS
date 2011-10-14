<?php
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
				->addElement('text', 'name', array('label' => 'Name', 'required' => true))
				->addElement('text', 'organisationUnit', array('label' => 'Organisation unit'))
				->addElement('text', 'website', array('label' => 'Website'))
				->addElement('text', 'email', array('label' => 'E-mail'))
				->addElement('text', 'streetAddress', array('label' => 'Street Address'))
				->addElement('text', 'locality', array('label' => 'Locality'))
				->addElement('text', 'postalCode', array('label' => 'Postal Code'))
				->addElement('text', 'countryName', array('label' => 'Country Name'))
				->addElement('submit', 'submit', array('label'=>'Submit'))
				;
			
			$form->getElement('email')->addValidator(new Zend_Validate_EmailAddress());
			$form->getElement('postalCode')->addValidator(new Zend_Validate_PostCode());
			
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
	public static function getRdfDocument()
	{
		$doc = new DOMDocument();
		$doc->appendChild($doc->createElement('rdf:RDF'));
		$doc->documentElement->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		$doc->documentElement->setAttribute('xmlns:v', 'http://www.w3.org/2006/vcard/ns#');
		
		return $doc;
	}
	
	public function toRdf()
	{
		$helper = new Zend_View_Helper_ServerUrl();
		$about = $helper->serverUrl('/api/institution/'.$this->code);
		$data = array();
		foreach ($this as $key => $val) {
			$data[$key] = htmlspecialchars($val);
		}
		
		$doc = self::getRdfDocument();
		$VCard = $doc->documentElement->appendChild($doc->createElement('v:Vcard'));
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
}
