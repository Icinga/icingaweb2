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
 * Provides infinite scrolling functionality for the monitoring timeline
 */
define(['jquery', 'logging'], function($, log) {
    'use strict';

    return function() {
        this.scrolled = false;
        this.ignoreScroll = false;

        /**
         * Scroll-event to register that the user has scrolled
         */
        this.registerScroll = function() {
            if (!this.ignoreScroll) {
                this.scrolled = true;
            }
        };

        /**
         * Check whether the user has scrolled to the timeline's end
         * and initiate the infinite scrolling if this is the case
         */
        this.checkScroll = function() {
            if (this.scrolled) {
                if (this.isScrolledIntoView('#TimelineEnd')) {
                    this.ignoreScroll = true;
                    this.loadTimeline.bind(this)();
                }
                this.scrolled = false;
            }

            if ($('[name="Timeline"]').length > 0) {
                setTimeout(this.checkScroll.bind(this), 1000);
            }
        };

        /**
         * Return whether the given element is visible in the users view
         *
         * Borrowed from: http://stackoverflow.com/q/487073
         *
         * @param   {selector}  element     The element to check
         * @returns {Boolean}
         */
        this.isScrolledIntoView = function(element) {
            var docViewTop = $(window).scrollTop();
            var docViewBottom = docViewTop + $(window).height();

            var elemTop = $(element).offset().top;
            var elemBottom = elemTop + $(element).height();

            return ((elemBottom >= docViewTop) && (elemTop <= docViewBottom)
                && (elemBottom <= docViewBottom) && (elemTop >= docViewTop));
        };

        /**
         * Return the current base url
         *
         * @returns {String}
         */
        this.getBaseUrl = function() {
            var protocol = window.location.protocol;
            var host = window.location.host;
            var base = window.base_url;

            return protocol + "//" + host + base;
        }

        /**
         * Initiate the AJAX timeline request
         */
        this.loadTimeline = function() {
            var query = {};
            var base_url = this.getBaseUrl();
            $.get(base_url + '/monitoring/timeline/extend', query, this.extendTimeline.bind(this));
        };

        /**
         * Process the AJAX timeline response
         *
         * @param {string} html
         */
        this.extendTimeline = function(html) {
            this.ignoreScroll = false;
            // TODO: Implement the processing logic
        }

        // The scroll-logic is split into two functions to avoid wasting too much
        // performance by checking whether the user reached the end of the timeline
        $(window).scroll(this.registerScroll.bind(this));
        setTimeout(this.checkScroll.bind(this), 1000);
    };
});
