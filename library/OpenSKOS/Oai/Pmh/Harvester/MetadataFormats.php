<?php

class OpenSKOS_Oai_Pmh_Harvester_MetadataFormats extends OpenSKOS_Oai_Pmh_Harvester_Abstract
{
	protected function _getXpathQuery()
	{
		return '/oai:OAI-PMH/oai:ListMetadataFormats/oai:metadataFormat';
	}
}
