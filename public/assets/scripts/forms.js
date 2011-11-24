/**
 * Universal Zend_Form styling
 * Javascript fixes, mootools required!
 * Here is everything needed for proper Zend_Form rendering that I couldn't achieve in CSS...
 * Based on JQuery script by Wojtek Iskra wojtek@domeq.net
 */
 
window.addEvent('load', function() {
    /**
     * First we check what kind of Zend_Form we're dealing with...
     * It can either be:
     * - Zend_Form with Zend_Form_Elements in it ('elementsOnly' class added),
     * - Zend_Form with Zend_Form_Subform(s), Zend_Form_Elements in it,
     *   but all elements are groupped in DisplayGroups ('allInDisplayGroups' class added),
     * - Zend_Form with Zend_Form_Subform(s) and Zend_Form_Elements in it, can also contain DisplayGroups
     *   ('subformAndDisplayGroups' class added).
     * We add classes to each form.
     * If you want to force certain type of styling despite form contents,
     * set one of the classes in Zend_Form, it will override jquery.
     */
    $$('form').each(function(element) {
        if (!element.hasClass('allInDisplayGroups') && 
            !element.hasClass('subformAndDisplayGroups') && 
            !element.hasClass('elementsOnly')
           ) {
            if ($$('fieldset fieldset', element).length > 0) {
                element.addClass('allInDisplayGroups');
            }
            else if ($$('dl.zend_form>dd>fieldset>dl>dd>fieldset', element).length > 0) {
                element.addClass('subformAndDisplayGroups');
            }
            else if ($$('dl.zend_form>dd>fieldset>dl>dd>input', element).length > 0) {
                element.addClass('subformsOnly');
            }
            else {
                element.addClass('elementsOnly');
            }
        }
    });
 
    /**
     * Hiding empty dt elements rendered by Zend_Form only for validation purposes.
     */
    $$('form dt').each(function(element) {
        if(element.get('html') == ' ') {
            element.hide();
        }
    });
     
    /**
     * Adding 'error' class to form element which did not pass validation.
     * (standard Zend_Form decorators do not do that...)
     */
    $$('ul.errors').each(function(element) {
        element.getPrevious().addClass('error');
      });
     
    /**
     * Removing 'error' class after leaving the element (not sure if it's reasonable though...)
     */
    $$('.error').each(function(element){
    	element.addEvent('blur', function(){this.removeClass('error');});
    });
     
    $$('form').each(function(form) {
    	/**
         * Hiding dt element with label for hidden input fields - rather useless
         */
    	form.getElements('input[type=hidden]').each(function(el){
//    		el.getParent().getPrevious().setStyle('display', 'none');
    	});
    });
     
});
