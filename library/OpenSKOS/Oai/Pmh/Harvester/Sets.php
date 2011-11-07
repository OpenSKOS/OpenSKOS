<?php

class OpenSKOS_Oai_Pmh_Harvester_Sets extends OpenSKOS_Oai_Pmh_Harvester_Abstract
{
	protected function _getXpathQuery()
	{
		return '/oai:OAI-PMH/oai:ListSets/oai:set';
	}
	
	public function toArray()
	{
		$result = array();
		foreach ($this as $set) {
			$result[$set->setSpec] = $set->setName;
		}
		return $result;
	}
}
