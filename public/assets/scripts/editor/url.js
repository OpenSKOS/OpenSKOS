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

EditorUrl = new Class({	
	_params: {},
	_parsedUrl: '',
	lastHashChangeIsManual: false,
	initialize: function () {},
	read: function () {
		
		this._params = this.parseUrl();
		
		// Concept view
		if (this._params.concept) {
			Editor.Control.loadConcept(this._params.concept);
		}
		
		// Search
		if (Editor.Search) {
			var doSearch = false;
			if (this._params.search !== undefined) {
				Editor.Search.searchForm.getElement('[name=searchText]').set('value', this._params.search);
				doSearch = true;
			}
			if (this._params.user !== undefined) {
				Editor.Search.searchForm.getElement('[name=user]').set('value', this._params.user);
				doSearch = true;
			}		
			if (doSearch) {
				Editor.Search.searchFromUrl = true;
				Editor.Search.search();
			}
		}
		
		// Concept Scheme
		if (this._params.addConceptScheme !== undefined && this._params.addConceptScheme) {
			Editor.Control.addConceptScheme();
		}
	},
	setParam: function (name, value) {
		
		this._params[name] = value;
		
		name = encodeURIComponent(name);
		value = encodeURIComponent(value);
		
		var url = window.location.href;
		
		// Ensure that we have anchor symbol
		if (url.indexOf('#') < 0) {
			url += '#';
		}
		
		// Set the param
		if (url.indexOf(name) >= 0) {
			var regex = new RegExp(name + '\/' + '[^\\/]*' + '\/');
			url = url.replace(regex, name + '/' + value + '/');
		} else {
			url += name + '/' + value + '/';
		}
		
		// Sets the url
		window.location.href = url;
		this.lastHashChangeIsManual = true;
	},
	getParam: function (name) {
		return this._params[name];
	},
	parseUrl: function () {
		
		var url = window.location.href;
		var result = new Array();
		
		var posOfAnchorSymbol = url.indexOf('#');
		if (posOfAnchorSymbol >= 0) {
			
			// Separates the anchor string from the url and the query string.			
			var anchor = url.substring(posOfAnchorSymbol + 1);
			
			// Gets the params from the anchor string
			var params = anchor.split('/');
			for (i = 0; i < params.length; i ++) {
				var name = decodeURIComponent(params[i]);
				var value = '';
				if (params[i+1] !== undefined) {
					value = decodeURIComponent(params[i+1]);
					i ++;
				}
				result[name] = value;
			}
		}
		
		return result;
	}
});