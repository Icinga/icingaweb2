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

define([
    'jquery',
    'logging',
    'icinga/componentLoader',
    'components/app/container',
    'URIjs/URI'
], function ($, log, components, Container, URI) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {

        var ignoreHistoryChanges = false;

        var initialize = function () {
            components.load();
            ignoreHistoryChanges = true;
            registerGenericHistoryHandler();
            ignoreHistoryChanges = false;
            log.debug("Initialization finished");

        };

        /**
         * Register handler for handling the history state generically
         *
         */
        var registerGenericHistoryHandler = function() {
            var lastUrl = URI(window.location.href);
            History.Adapter.bind(window, 'popstate', function() {
                if (ignoreHistoryChanges) {
                    return;
                }

                gotoUrl(History.getState().url);
                lastUrl = URI(window.location.href);
            });
        };


        var gotoUrl = function(href) {
            if (typeof document.body.pending !== 'undefined') {
                document.body.pending.abort();
            }
            if (typeof href === 'string') {
                href = URI(href);
            }
            document.body.pending = $.ajax({
                url: href.href()
            }).done(function(domNodes) {
                $('body').empty().append(jQuery.parseHTML(domNodes));
                ignoreHistoryChanges = true;
                History.pushState({}, document.title, href.href());
                ignoreHistoryChanges = false;
                components.load();
            }).error(function(xhr, textStatus, errorThrown) {
                    if (xhr.responseText) {
                        $('body').empty().append(jQuery.parseHTML(xhr.responseText));
                    } else if (textStatus !== 'abort') {
                        logging.emergency('Could not load URL', xhr.href, textStatus, errorThrown);
                    }
            });

            return false;
        };

        if (Modernizr.history) {
            $(document.body).on('click', '#icinganavigation', function(ev) {
                var targetEl = ev.target || ev.toElement || ev.relatedTarget;
                if (targetEl.tagName.toLowerCase() !== 'a') {
                    return true;
                }

                var href = $(targetEl).attr('href');
                if (Container.isExternalLink(href)) {
                    return true;
                }
                ev.preventDefault();
                ev.stopPropagation();
                gotoUrl(href);
                return false;
            });
        }
        $(document).ready(initialize.bind(this));
        Container.setIcinga(this);
        this.components = components;
        this.replaceBodyFromUrl = gotoUrl;
    };


    return new Icinga();
});
