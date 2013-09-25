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
/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

/**
 * Ensures that our date/time controls will work on every browser (natively or javascript based)
 */
define(['jquery', 'logging', 'URIjs/URI'], function($, log, URI) {
    'use strict';

    return function(inputDOM) {
        this.inputDom = $(inputDOM);
        this.form = this.inputDom.parents('form').first();
        this.formUrl = URI(this.form.attr('action'));
        this.lastTokens = [];
        this.lastQueuedEvent = null;
        this.pendingRequest = null;

        this.construct = function() {
            this.registerControlListener();
        };

        this.getProposal = function() {
            var text = this.inputDom.val().trim();

            try {
                if (this.pendingRequest) {
                    this.pendingRequest.abort();
                }
                this.pendingRequest = $.ajax({
                    data: {
                        'cache' : (new Date()).getTime(),
                        'query' : text
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    url: this.formUrl
                }).done(this.showProposals.bind(this)).fail(function() {});
            } catch(exception) {
                console.log(exception);
            }
        };

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

        this.showProposals = function(tokens, state, args) {

            var jsonRep = args.responseText;


            if (tokens.length === 0) {
                return this.inputDom.popover('destroy');
            }
            this.lastTokens = jsonRep;

            var list = $('<ul>').addClass('nav nav-stacked nav-pills');
            $.each(tokens, (function(idx, token) {
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

        this.registerControlListener = function() {
            this.inputDom.on('blur', (function() {
                $(this).popover('hide');
            }));
            this.inputDom.on('focus', updateProposalList.bind(this));
            this.inputDom.on('keyup', updateProposalList.bind(this));
        };

        var updateProposalList = function() {
            if (this.lastQueuedEvent) {
                window.clearTimeout(this.lastQueuedEvent);
            }
            this.lastQueuedEvent = window.setTimeout(this.getProposal.bind(this), 200);
        };

        this.construct();
    };


});