<?php
require_once 'FindConceptsController.php';

class Api_ConceptController extends Api_FindConceptsController {


	public function postAction() {
		$this->_501('POST');
	}

	public function putAction() {
		$this->_501('POST');
	}

	public function deleteAction() {
		$this->_501('DELETE');
	}

}

