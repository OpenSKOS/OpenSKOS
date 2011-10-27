<?php
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
	}
}