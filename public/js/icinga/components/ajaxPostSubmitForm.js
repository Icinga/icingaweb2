// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 */
// {{{ICINGA_LICENSE_HEADER}}}
/*global Icinga:false define:false require:false base_url:false console:false */

/**
 * Icinga app/ajaxPostSubmitForm component.
 *
 * This component converts generic post forms into ajax
 * submit forms.
 */
define(['components/app/container', 'jquery'], function(Container, $) {
    "use strict";

    /**
     * Returns owner container
     *
     * @param {Element} targetElement
     * @returns {Container}
     */
    var getOwnerContainer = function(targetElement) {
        var me = new Container(targetElement);
        return new Container(me.findNearestContainer(targetElement));
    };

    /**
     * Handler for ajax post submit
     *
     * @param {Event} e
     */
    var submitHandler = function(e) {
        var form = $(this);
        var url = form.attr('action');
        var submit = form.find('input[type="submit"]');
        var data = form.serialize();

        e.preventDefault();

        // Submit name is missing for valid submission
        if (data) {
            data += '&';
        }
        data += submit.attr('name') + '=1';

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                submit.prop('disabled', true);
            }
        }).done(function() {
            var container = getOwnerContainer(form);
            container.replaceDomFromUrl(container.getContainerHref());
        }).error(function() {
            submit.removeProp('disabled');
        });
    };

    /**
     * The component bootstrap
     *
     * @param {Element} targetElement
     */
    return function(targetForm) {
        var form = $(targetForm);
        form.submit(submitHandler);
    };
});