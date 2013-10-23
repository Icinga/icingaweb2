// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}
/*global Icinga:false define:false require:false base_url:false console:false */

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
     * Takes a form and returns an overloaded jQuery object
     *
     * The returned object is the jQuery matcher with the following additional methods:
     *
     * - isModified:            Return true when the form is marked as modified
     * - setModificationFlag:   Mark this form as being modified
     * - clearModificationFlag: Clear the modification mark
     *
     * @param targetForm
     * @returns {jQuery}
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
            if ($(changed.target).attr('data-icinga-form-autosubmit')) {
                form.clearModificationFlag();
                form.submit();
            } else {
                form.setModificationFlag();
            }
        });
        // submissions should clear the modification flag
        form.submit(function() {
            form.clearModificationFlag();
        });
    };

    /**
     * Register an eventhandler that triggers a confirmation message when the user tries to leave a modified form
     *
     * @param {jQuery} form     A form object returned from @see getFormObject()
     */
    var registerLeaveConfirmationHandler = function(form) {

        $(window).on('beforeunload', function() {
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

        // Remove DOM level onchange, we registered proper jQuery listeners for them
        $('[data-icinga-form-autosubmit]').removeAttr('onchange');
    };
});