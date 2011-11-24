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

class OpenSKOS_Oai_Pmh_Harvester_MetadataFormats extends OpenSKOS_Oai_Pmh_Harvester_Abstract
{
	protected function _getXpathQuery()
	{
		return '/oai:OAI-PMH/oai:ListMetadataFormats/oai:metadataFormat';
	}

	public function toArray()
	{
	    throw new Exception('Not implemented');
	}
}
