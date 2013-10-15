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
define(
    ['jquery', 'URIjs/URI', 'icinga/selection/selectable'],
function($, URI, Selectable) {
    "use strict";

    /**
     * Handle the multi-selection of table rows and generate the query string
     * that can be used to open the selected items.
     *
     * NOTE: After each site reload, the state (the current selection) of this object will be
     * restored automatically. The selectable items are determined by finding all TR elements
     * in the targeted table. The active selection is determined by checking the query elements
     * of the given url.
     *
     * @param {HtmlElement}     The table that contains the selectable rows.
     *
     * @param {Object}          The query that contains the selected rows.
     */
    return function MultiSelection(table, detailUrl) {
        var self = this;
        /**
         * Contains all selected selectables
         *
         * @type {Object}
         */
        var selection = {};

        /**
         * If the selectable was already added, remove it, otherwise add it.
         *
         * @param {Selectable}  The selectable to use.
         */
        this.toggle = function(selectable) {
            if (selection[selectable.getId()]) {
                self.remove(selectable);
            } else {
                self.add(selectable);
            }
        };

        /**
         * Add the selectable to the current selection.
         *
         * @param {Selectable}  The selectable to use.
         */
        this.add = function(selectable) {
            selectable.setActive(true);
            selection[selectable.getId()] = selectable;
        };

        /**
         * Remove the selectable from the current selection.
         *
         * @param {Selectable}  The selectable to use.
         */
        this.remove = function(selectable) {
            selectable.setActive(false);
            delete selection[selectable.getId()];
        };

        /**
         * Clear the current selection
         */
        this.clear = function() {
            $.each(selection, function(index, selectable){
                selectable.setActive(false);
            });
            selection = {};
        };

        /**
         * Convert the current selection to its query-representation.
         *
         * @returns {String}    The query
         */
        this.toQuery = function() {
            var query = {};
            var i = 0;
            $.each(selection, function(id, selectable) {
                $.each(selectable.getQuery(), function(key, value) {
                    query[key + '[' + i + ']'] = value;
                });
                i++;
            });
            return query;
        };

        this.size = function() {
            var size = 0;
            $.each(selection, function(){ size++; });
            return size;
        };

        /**
         * Fetch the selections from a query containing multiple selections
         */
        var selectionFromMultiQuery = function(query) {
            var selections = [];
            $.each(query, function(key, value) {
                // Fetch the index from the key
                var id = key.match(/\[([0-9]+)\]/);
                if (id) {
                    // Remove the index from the key
                    key = key.replace(/\[[0-9]+\]/,'');
                    // Create an object that contains the queries for each index.
                    var i = id[1];
                    if (!selections[i]) {
                        selections[i] = [];
                    }
                    selections[i].push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            });
            return selections;
        };

        /**
         * Fetch the selections from a default query.
         */
        var selectionFromQuery = function(query) {
            var selection = [];
            $.each(query, function(key, value){
                key = encodeURIComponent(key);
                value = encodeURIComponent(value);
                selection.push(key + '=' + value);
            });
            return [ selection ];
        };

        /**
         * Restore the selected ids from the given URL.
         *
         * @param {URI}     The used URL
         *
         * @returns {Array} The selected ids
         */
        var restoreSelectionStateUrl = function(url) {
            if (!url) {
                return [];
            }
            if (!url.query) {
                url = new URI(url);
            }
            var segments = url.segment();
            var parts;
            // TODO: Handle it for cases when there is no /icinga-web2/ in the path
            if (segments.length > 2 && segments[2].toLowerCase() === 'multi') {
                parts = selectionFromMultiQuery(url.query(true));
            } else {
                parts = selectionFromQuery(url.query(true));
            }
            return $.map(parts, function(part) {
                part.sort(function(a, b){
                    a = a.toUpperCase();
                    b = b.toUpperCase();
                    return (a < b ? -1 : (a > b) ? 1 : 0);
                });
                return part.join('&');
            });
        };

        /**
         * Create the selectables from the given table-Html
         */
        var createSelectables = function(table) {
            var selectables = {};
            $(table).find('tr').each(function(i, row) {
                var selectable = new Selectable(row);
                selectables[selectable.getId()] = selectable;
            });
            return selectables;
        };

        /**
         * Restore the selectables from the given table and the given url
         */
        var restoreSelection = function() {
            var selectables = createSelectables(table);
            var selected = restoreSelectionStateUrl(detailUrl);
            var selection = {};
            $.each(selected, function(i, selectionId) {
                var restored = selectables[selectionId];
                if (restored) {
                    selection[selectionId] = restored;
                    restored.setActive(true);
                }
            });
            return selection;
        };

        selection = restoreSelection();
    };
});
