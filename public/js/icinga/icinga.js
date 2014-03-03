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
/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

define([
    'jquery',
    'logging',
    'icinga/componentLoader',
    'components/app/container',
    'URIjs/URI',
    'icinga/util/url'
], function ($, log, components, Container, URI, urlMgr) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {
        var pendingRequest = null;

        /**
         * Initia
         */
        var initialize = function () {
            components.load();

            // qd, wip, wrong, the nastiest piece of JS code you've ever seen..
            if (window.name === '') {
                window.name = request_id; // The request id should survive page reloads..
            }
            $(document).on('click', 'a', function() {
                // TODO: The intention of these lines is sending the "request_id" every
                //       time a request is made, though this approach does not work for
                //       XHR requests and it is also hijacking external links.
                //       An alternative is required, once someone reworked the Javascript
                //       implementation so we have some sort of centralized place to handle
                //       stuff like that properly.
                var href = $(this).attr('href');
                window.location.href = URI(href).addSearch('request_id', window.name);
                return false;
            });
            // qd, wip, wrong, the nastiest piece of JS code you've ever seen..

            log.debug("Initialization finished");
        };

        /**
         * Globally open the given url and reload the main/detail box to represent it
         *
         * @param url The url to load
         */
        this.openUrl = function(url) {
            if (pendingRequest) {
                pendingRequest.abort();
            }
            pendingRequest = $.ajax({
                "url": url
            }).done(function(response) {
                var dom = $(response);
                var detailDom = null;
                if (urlMgr.detailUrl) {
                    detailDom = $('#icingadetail');
                }
                $(document.body).empty().append(dom);
                if (detailDom && detailDom.length) {
                    $('#icingadetail').replaceWith(detailDom);
                    Container.showDetail();
                }
                components.load();
                Container.getMainContainer();
            }).fail(function(response, reason) {
                if (reason === 'abort') {
                    return;
                }
                log.error("Request failed: ", response.message);
            });
        };


        if (Modernizr.history) {
            /**
             * Event handler that will be called when the url change
             */
            urlMgr.syncWithUrl();
            var lastMain = urlMgr.mainUrl;
            $(window).on('pushstate', (function() {
                urlMgr.syncWithUrl();
                if (urlMgr.mainUrl !== lastMain) {
                    this.openUrl(urlMgr.getUrl());
                    lastMain = urlMgr.mainUrl;
                }
                // If an anchor is set, scroll to it's position
                if ($('#' + urlMgr.anchor).length) {
                    $(document.body).scrollTo($('#' + urlMgr.anchor));
                }
            }).bind(this));

            /**
             * Event handler for browser back/forward events
             */
            $(window).on('popstate', (function() {
                var lastMain = urlMgr.mainUrl;
                urlMgr.syncWithUrl();
                if (urlMgr.mainUrl !== lastMain) {
                    this.openUrl(urlMgr.getUrl());
                }

            }).bind(this));
        }

        $(document).ready(initialize.bind(this));
        Container.setIcinga(this);

        this.components = components;

    };


    return new Icinga();
});
