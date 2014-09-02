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

/**
 * Validates that the concept has unique pref label inside the scheme. 
 * 
 */
class Editor_Models_ConceptValidator_UniquePrefLabelInScheme extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('prefLabel');		
		$isValid = true;
		
		if (isset($concept['inScheme']) && ! empty($concept['inScheme'])) {
			
			// Get all pref labels for all languages.
			$prefLabels = array();
			$languages = $concept->getConceptLanguages();
			foreach ($languages as $lang) {
				if (isset($concept['prefLabel@' . $lang])) {
					$prefLabels = array_merge($prefLabels, $concept['prefLabel@' . $lang]);
				}
			}
			$query = 'prefLabel:("' . implode('" OR "', $prefLabels) . '")';
			$query .= ' inScheme:("' . implode('" OR "', $concept['inScheme']) . '")';
			if (isset($concept['tenant']) && ! empty($concept['tenant'])) {
				$query .= ' tenant:' . $concept['tenant'];
			} elseif (null !== ($tenant = OpenSKOS_Db_Table_Tenants::fromIdentity())) {
				$query .= ' tenant:' . $tenant->code;
			}
			$query .= ' -uuid:"' . $concept['uuid'] . '"';

			$response = Api_Models_Concepts::factory()->setQueryParams(array('rows' => 0))->getConcepts($query);
			if ($response['response']['numFound'] > 0) {
				$isValid = false;
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('There is already a concept with same preferred label in one of the schemes.'));
		}
		
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_UniquePrefLabelInScheme();
	}
}

