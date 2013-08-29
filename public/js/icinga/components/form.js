/*global Icinga:false define:false require:false base_url:false console:false */
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga app/form component.
 *
 * This component makes sure a user has to confirm when trying to discard unsaved changes
 * by leaving the current page. It also implements the code for autosubmitting fields having the
 * 'data-icinga-form-autosubmit' component.
 */
define(['jquery'], function($) {
    "use strict";

    /**
     * The attribute name marking forms as being modified
     *
     * @type {string}
     */
    var ATTR_MODIFIED = 'data-icinga-form-modified';

    /**
     * Return true when the input element is a autosubmit field
     *
     * @param {string|DOMElement|jQuery} el         The element to test for autosubmission
     *
     * @returns {boolean}                           True when the element should be automatically submitted
     */
    var isAutoSubmitInput = function(el) {
        return $(el).attr('data-icinga-form-autosubmit') === 'true' ||
            $(el).attr('data-icinga-form-autosubmit') === '1';
    };

    /**
     * Takes a form and returns an overloaded jQuery object
     *
     * The returned object is the jQuery mathcer with the following additional methods:
     *
     * - isModified                 : Return true when the form is marked as modified
     * - setModificationFlag        : Mark this form as being modified
     * - clearModificationFlag      : Clear the modification mark
     *
     * @param targetForm
     * @returns {JQuery}
     */
    var getFormObject = function(targetForm) {
        var form = $(targetForm);
        /**
         * Return true when the form is marked as modified
         *
         * @returns {boolean}   True when the form has the @see ATTR_MODIFIED attribute set to 'true', otherwise false
         */
        form.isModified = function() {
            return form.attr(ATTR_MODIFIED) === 'true' ||
                form.attr(ATTR_MODIFIED) === '1';
        };

        /**
         * Mark this form as being modified
         */
        form.setModificationFlag = function() {
            form.attr(ATTR_MODIFIED, true);
        };

        /**
         * Clear the modification flag on this form
         */
        form.clearModificationFlag = function() {
            form.attr(ATTR_MODIFIED, false);
        };
        return form;
    };

    /**
     * Register event handler for detecting form modifications.
     *
     * This handler takes care of autosubmit form fields causing submissions on change and
     * makes sure the modification flag on the form is set when changes occur.
     *
     * @param {jQuery} form     A form object returned from @see getFormObject()
     */
    var registerFormEventHandler = function(form) {
        form.change(function(changed) {
            if (isAutoSubmitInput(changed.target)) {
                form.clearModificationFlag();
                form.submit();
            } else {
                form.setModificationFlag();
            }

        });
        // submissions should clear the modification flag
        form.submit(form.clearModificationFlag);
    };

    /**
     * Register an eventhandler that triggers a confirmation message when the user tries to leave a modified form
     *
     * @param {jQuery} form     A form object returned from @see getFormObject()
     */
    var registerLeaveConfirmationHandler = function(form) {

        $(window).bind('beforeunload', function() {
            if (form.isModified()) {
                return 'All unsaved changes will be lost when leaving this page';
            }
        });
    };


    /**
     * The component bootstrap
     */
    return function(targetForm) {
        var form = getFormObject(targetForm);
        registerFormEventHandler(form);
        registerLeaveConfirmationHandler(form);
    };
});