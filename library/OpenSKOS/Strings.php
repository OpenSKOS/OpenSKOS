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

//extra strings for PO/MO generation:
class OpenSKOS_Strings
{
	protected static function translatables()
	{
		throw new Exception('This is just for Gettext PO/MO editors');
		_('dashboard');
		_('api');
		_('both');
		_('Supplied credential is invalid.');
		_('Authentication successful.');
		_("Invalid type given, value should be a string");
		_("'%value%' is no valid email address in the basic format local-part@hostname");
		_("'%hostname%' is no valid hostname for email address '%value%'");
		_("'%hostname%' does not appear to have a valid MX record for the email address '%value%'");
		_("'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network.");
		_("'%localPart%' can not be matched against dot-atom format");
		_("'%localPart%' can not be matched against quoted-string format");
		_("'%localPart%' is no valid local part for email address '%value%'");
		_("'%value%' exceeds the allowed length");
		_("Invalid type given, value should be a string");
		_("'%value%' is less than %min% characters long");
		_("'%value%' is more than %max% characters long");
		_("Value is required and can't be empty");
		_("The token '%token%' does not match the given token '%value%'");
        _('No token was provided to match against');
        _("The two given tokens do not match");
        _("'%value%' is not a valid URL.");
        _("'%value%' is not a valid date string.");
        
        _('code');
        _('organisationUnit');
        _('website');
        _('email');
        _('streetAddress');
        _('locality');
        _('postalCode');
        _('countryName');
        
        _('guest');
        _('administrator');
	}
}