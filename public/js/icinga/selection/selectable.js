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
define(['jquery', 'URIjs/URI'], function($, URI) {
    "use strict";

    /**
     * Wrapper around a selectable table row. Searches the first href and to identify the associated
     * query and use this query as an Identifier.
     *
     * @param {HtmlElement} The table row.
     */
    return function Selectable(tableRow) {

        /**
         * The href that is called when this row clicked.
         *
         * @type {*}
         */
        var href = URI($(tableRow).find('a').first().attr('href'));

        /*
         * Sort queries alphabetically to ensure non-ambiguous ids.
         */
        var query = href.query();
        var parts = query.split('&');
        parts.sort(function(a, b){
            a = a.toUpperCase();
            b = b.toUpperCase();
            return (a < b ? -1 : (a > b) ? 1 : 0);
        });
        href.query(parts.join('&'));

        /**
         * Return an ID for this selectable.
         *
         * @returns {String}    The id.
         */
        this.getId = function () {
            return href.query();
        };

        /**
         * Return the query object associated with this selectable.
         *
         * @returns {String}    The id.
         */
        this.getQuery = function() {
            return href.query(true);
        };

        this.setActive = function(value) {
            if (value) {
                $(tableRow).addClass('active');
            } else {
                $(tableRow).removeClass('active');
            }
        };
    };
});