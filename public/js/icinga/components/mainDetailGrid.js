/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
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
define(['components/app/container', 'jquery', 'logging', 'URIjs/URI', 'URIjs/URITemplate', 'icinga/util/url', 'icinga/selection/selectable', 'icinga/selection/multiSelection'],
function(Container, $, logger, URI, tpl, urlMgr, Selectable, TableMultiSelection) {
    "use strict";

    /**
     * Master/Detail grid component handling history, link behaviour, selection (@TODO 3788) and updates of
     * grids
     *
     * @param {HTMLElement} The outer element to apply the behaviour on
     */
    return function(gridDomNode) {

        /**
         * Reference to the outer container of this component
         *
         * @type {*|HTMLElement}
         */
        gridDomNode = $(gridDomNode);

        /**
         * A container component to use for updating URLs and content
         *
         * @type {Container}
         */
        this.container = null;

        /**
         * The node wrapping the table and pagination
         *
         * @type {jQuery}
         */
        var contentNode;

        /**
         * jQuery matcher result of the form components wrapping the controls
         *
         * @type {jQuery}
         */
        var controlForms;

        /**
         * Handles multi-selection
         *
         * @type {TableMultiSelection}
         */
        var selection;

        /**
         * Defines how row clicks are handled. Can either be 'none', 'single' or 'multi'
         *
         * @type {string}
         */
        var selectionMode;

        /**
         * Detect and select control forms for this table and return them
         *
         * Form controls are either all forms underneath the of the component, but not underneath the table
         * or in a dom node explicitly tagged with the 'data-icinga-actiongrid-controls' attribute
         *
         * @param   {jQuery|null} domContext            The context to use as the root node for matching, if null
         *                                              the component node given in the constructor is used
         *
         * @returns {jQuery}                            A selector result with all forms modifying this grid
         */
        var determineControlForms = function(domContext) {
            domContext = domContext || gridDomNode;
            var controls = $('[data-icinga-grid-controls]', domContext);
            if (controls.length > 0) {
                return $('form', controls);
            } else {
                return $('form', domContext).filter(function () {
                    return $(this).parentsUntil(domContext, 'table').length === 0;
                });
            }
        };

        /**
         * Detect and select the dom of all tables displaying content for this mainDetailGrid component
         *
         * The table can either explicitly tagged with the 'data-icinga-grid-content' attribute, if not every table
         * underneath the components root dom will be used
         *
         * @param   {jQuery|null} domContext            The context to use as the root node for matching, if null
         *                                              the component node given in the constructor is used
         *
         * @returns {jQuery}                            A selector result with all tables displaying information in the
         *                                              grid
         */
        var determineContentTable = function(domContext) {
            domContext = domContext || gridDomNode;
            var maindetail = $('[data-icinga-grid-content]', domContext);
            if (maindetail.length > 0) {
                return maindetail;
            } else {
                return $('table', domContext);
            }
        };

		/**
		 * Show a 'hand' to indicate that the row is selectable,
		 * when hovering.
		 */
		this.showMousePointerOnRow = function(domContext) {
			domContext = domContext || contentNode;
			$('tbody tr', domContext).css('cursor' ,'pointer');
		};

        /**
         * Activate a hover effect on all table rows, to indicate that
         * this table row is clickable.
         *
         * @param domContext
         */
        this.activateRowHovering = function(domContext) {
            domContext = domContext || contentNode;
            //$(domContext).addClass('table-hover');
            $('tbody tr', domContext).hover(
                function(e) {
                    $(this).addClass('hover');
                    e.preventDefault();
                    e.stopPropagation();
                },
                function(e) {
                    $(this).removeClass('hover');
                    e.preventDefault();
                    e.stopPropagation();
                }
            );
        };

        /**
         * Register the row links of tables using the first link found in the table (no matter if visible or not)
         *
         * Row level links can only be realized via JavaScript, so every row should provide additional links for
         * Users that don't have javascript enabled
         *
         * @param {jQuery|null} domContext          The rootnode to use for selecting rows or null to use contentNode
         */
        this.registerTableLinks = function(domContext) {
            domContext = domContext || contentNode;

            $('tbody tr', domContext).click(function(ev) {
                var targetEl = ev.target || ev.toElement || ev.relatedTarget,
                    a = $(targetEl).closest('a');
                ev.preventDefault();
                ev.stopPropagation();

                var nodeNames = [];
                nodeNames.push($(targetEl).prop('nodeName').toLowerCase());
                nodeNames.push($(targetEl).parent().prop('nodeName').toLowerCase());

                if (a.length) {
                    // test if the URL is on the current server, if not open it directly
                    if (Container.isExternalLink(a.attr('href'))) {
                        return true;
                    }
                } else if ($.inArray('input', nodeNames) > -1 || $.inArray('button', nodeNames) > -1) {
                    var type = $(targetEl).attr('type') || $(targetEl).parent().attr('type');
                    if (type === 'submit') {
                        return true;
                    }
                }

                var selected = new Selectable(this);
                switch (selectionMode) {
                    case 'multi':
                        if (ev.ctrlKey || ev.metaKey) {
                            selection.toggle(selected);
                        } else if (ev.shiftKey) {
                            selection.add(selected);
                        } else {
                            var oldState = selected.isActive();
                            selection.clear();
                            if (!oldState) {
                                selection.add(selected);
                            }
                        }
                        break;

                    case 'single':
                        oldState = selected.isActive();
                        selection.clear();
                        if (!oldState) {
                            selection.add(selected);
                        }
                        break;

                    default:
                        // don't open the link
                        return;
                }
                var url = URI($('a', this).attr('href'));
                if (targetEl.tagName.toLowerCase() === 'a') {
                    url = URI($(targetEl).attr('href'));
                }
                var segments = url.segment();
                if (selection.size() === 0) {
                    // don't open anything
                    urlMgr.setDetailUrl('');
                    return false;
                } else if (selection.size() > 1 && segments.length > 3) {
                    // open detail view for multiple objects
                    segments[2] = 'multi';
                    url.pathname('/' + segments.join('/'));
                    url.search('?');
                    url.setSearch(selection.toQuery());
                }
                urlMgr.setDetailUrl(url);
                return false;
            });

            /*
             * Clear the URL when deselected or when a wildcard is used
             */
            $(window).on('hashchange', function(){
                if (
                    !location.hash ||
                    location.hash.match(/(host=%2A)|(service=%2A)/)
                ) {
                    selection.clear();
                }
            });
        };

        /**
         * Register submit handler for the form controls (sorting, filtering, etc). Reloading happens in the
         * current container
         */
        this.registerControls = function() {
            controlForms.on('submit', function(evt) {
                var container = (new Container(this));
                var form = $(this);
                var url = container.getUrl();

                if (url.indexOf('?') >= 0) {
                    url += '&';
                } else {
                    url += '?';
                }
                url += form.serialize();
                container.setUrl(url);

                evt.preventDefault();
                evt.stopPropagation();
                return false;

            });
            $('.pagination li a, a.filter-badge', contentNode.parent()).on('click', function(ev) {

                var container = (new Container(this));

                // Detail will be removed when main pagination changes
                if (container.containerType === 'icingamain') {
                    urlMgr.setMainUrl(URI($(this).attr('href')));
                    urlMgr.setDetailUrl('');
                } else {
                    urlMgr.setDetailUrl(URI($(this).attr('href')));
                }

                ev.preventDefault();
                ev.stopPropagation();
                return false;
            });
        };

        /**
         * Create a new TableMultiSelection, attach it to the content node, and use the
         * current detail url to restore the selection state
         */
        this.initSelection = function() {
            var detail = urlMgr.getDetailUrl();
            if (typeof detail !== 'string') {
                detail = detail[0] || '';
            }
            selection = new TableMultiSelection(contentNode,new URI(detail));
        };

		/**
		 * Init all objects responsible for selection handling
		 *
		 * - Indicate selection by showing active and hovered rows
		 * - Handle click-events according to the selection mode
		 * - Create and follow links according to the row content
		 */
		this.initRowSelection = function() {
            selectionMode = gridDomNode.data('icinga-grid-selection-type');
            if (selectionMode === 'multi' || selectionMode === 'single') {
				// indicate selectable rows
				this.showMousePointerOnRow();
                this.activateRowHovering();
                this.initSelection();
            }
            this.registerTableLinks();
		};

        /**
         * Create this component, setup listeners and behaviour
         */
        this.construct = function(target) {
            this.container = new Container(target);
            this.container.removeDefaultLoadIndicator();
            controlForms = determineControlForms();
            contentNode = determineContentTable();
			this.initRowSelection();
            this.registerControls();
        };
        if (typeof $(gridDomNode).attr('id') === 'undefined') {
            this.construct(gridDomNode);
        }
    };
});
