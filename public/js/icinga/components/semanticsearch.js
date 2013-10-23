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

/**
 * Ensures that our date/time controls will work on every browser (natively or javascript based)
 */
define(['jquery', 'logging', 'URIjs/URI', 'components/app/container'], function($, log, URI, Container) {
    'use strict';

    return function(inputDOM) {
        this.inputDom = $(inputDOM);
        this.domain = this.inputDom.attr('data-icinga-filter-domain');
        this.module = this.inputDom.attr('data-icinga-filter-module');
        this.form = $(this.inputDom.parents('form').first());
        this.formUrl = URI(this.form.attr('action'));

        this.lastQueuedEvent = null;
        this.pendingRequest = null;

        /**
         * Register the input listener
         */
        this.construct = function() {
            this.registerControlListener();
        };

        /**
         * Request new proposals for the given input box
         */
        this.getProposal = function() {
            var text = $.trim(this.inputDom.val());

            if (this.pendingRequest) {
                this.pendingRequest.abort();
            }
            this.pendingRequest = $.ajax(this.getRequestParams(text))
                .done(this.showProposals.bind(this))
                .fail(this.showError.bind(this));
        };

        /**
         * Apply a selected proposal to the text box
         *
         * String parts encapsulated in {} are parts that already exist in the input
         *
         * @param token     The selected token
         */
        this.applySelectedProposal = function(token) {
            var currentText = $.trim(this.inputDom.val());

            var substr = token.match(/^(\{.*\})/);
            if (substr !== null) {
                token = token.substr(substr[0].length);
            } else {
                token = ' ' + token;
            }

            currentText += token;
            this.inputDom.val(currentText);
            this.inputDom.popover('hide');
            this.inputDom.focus();
        };

        /**
         * Display an error in the box if the request failed
         *
         * @param {Object} error    The error response
         * @param {String} state    The HTTP state as a string
         */
        this.showError = function(error, state) {
            if (!error.message || state === 'abort') {
                return;
            }
            this.inputDom.popover('destroy').popover({
                content: '<div class="alert alert-danger"> ' + error.message + ' </div>',
                html: true,
                trigger: 'manual'
            }).popover('show');
        };

        /**
         * Return an Object containing the request information for the given query
         *
         * @param query
         * @returns {{data: {cache: number, query: *, filter_domain: (*|Function|Function), filter_module: Function}, headers: {Accept: string}, url: *}}
         */
        this.getRequestParams = function(query) {
            return {
                data: {
                    'cache' : (new Date()).getTime(),
                    'query' : query,
                    'filter_domain' : this.domain,
                    'filter_module' : this.module
                },
                headers: {
                    'Accept': 'application/json'
                },
                url: this.formUrl
            };
        };

        /**
         * Callback that renders the proposal list after retrieving it from the server
         *
         * @param {Object} response      The jquery response object inheritn XHttpResponse Attributes
         */
        this.showProposals = function(response) {

            if (!response || !response.proposals || response.proposals.length === 0) {
                this.inputDom.popover('destroy');
                return;
            }

            if (response.valid) {
                this.inputDom.parent('div').removeClass('has-error').addClass('has-success');
            } else {
                this.inputDom.parent('div').removeClass('has-success').addClass('has-error');
            }
            var list = $('<ul>').addClass('nav nav-stacked');
            $.each(response.proposals, (function(idx, token) {
                var displayToken = token.replace(/(\{|\})/g, '');
                var proposal = $('<li>').
                    append($('<a href="#">').
                        text(displayToken)
                    ).appendTo(list);

                proposal.on('click', (function(ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    this.applySelectedProposal(token);
                    return false;
                }).bind(this));
            }).bind(this));

            this.inputDom.popover('destroy').popover({
                content: list,
                placement : 'bottom',
                html: true,
                trigger: 'manual'
            }).popover('show');
        };

        /**
         * Callback to update the current container with the entered url if it's valid
         */
        this.updateFilter = function() {
            var query = $.trim(this.inputDom.val());
            $.ajax(this.getRequestParams(query))
                .done((function(response) {
                    var domContainer = new Container(this.inputDom);
                    var url = response.urlParam;

                    if (url) {
                        domContainer.setUrl(url);
                    }
                }).bind(this));
        };

        /**
         * Register listeners for the searchbox
         *
         * This means:
         * - Activate/Deactivate the popover on focus and blur
         * - Add Url tokens and submit on enter
         */
        this.registerControlListener = function() {
            this.inputDom.on('blur', (function() {
                $(this).popover('hide');
            }));
            this.inputDom.on('focus', updateProposalList.bind(this));
            this.inputDom.on('keyup', updateProposalList.bind(this));
            this.inputDom.on('keydown', (function(keyEv) {
                if ((keyEv.keyCode || keyEv.which) === 13) {
                    this.updateFilter();
                    keyEv.stopPropagation();
                    keyEv.preventDefault();
                    return false;
                }
            }).bind(this));

            this.form.submit(function(ev) {
                ev.stopPropagation();
                ev.preventDefault();
                return false;
            });
        };

        /**
         * Callback to update the proposal list if a slight delay on keyPress
         *
         * Needs to be bound to the object scope
         *
         * @param {jQuery.Event} keyEv     The key Event to react on
         */
        var updateProposalList = function(keyEv) {

            if (this.lastQueuedEvent) {
                window.clearTimeout(this.lastQueuedEvent);
            }
            this.lastQueuedEvent = window.setTimeout(this.getProposal.bind(this), 500);
        };

        this.construct();
    };
});