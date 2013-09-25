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

/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

var URI = require('URIjs');

(function() {
    GLOBAL.window = {
        location: {
            href: 'http://localhost/icinga2-web/testcase',
            pathname: '/icinga2-web/testcase',
            query: '',
            hash: '',
            host: 'localhost',
            protocol: 'http'
        }
    };
    "use strict";

    var states = [];


    /**
     * Api for setting the window URL
     *
     * @param {string} url      The new url to use for window.location
     */
    window.setWindowUrl = function(url) {
        var url = URI(url);
        window.location.protocol = url.protocol();
        window.location.pathname = url.pathname();
        window.location.query = url.query();
        window.location.search = url.search();
        window.location.hash = url.hash();
        window.location.href = url.href();
    };

    /**
     * Mock for the History API
     *
     * @type {{pushState: Function, popState: Function, replaceState: Function, clear: Function}}
     */
    module.exports = {
        pushState: function(state, title, url) {
            window.setWindowUrl(url);
            states.push(arguments);
        },
        popState: function() {
            return states.pop();
        },
        replaceState: function(state, title, url) {
            states.pop();
            window.setWindowUrl(url);
            states.push(arguments);
        },
        clearState: function() {
            states = [];
        },
        getState: function() {
            return states;
        }
    };
})();