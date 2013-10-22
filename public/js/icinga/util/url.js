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

define(['jquery', 'logging', 'URIjs/URI'], function($, log, URI, Container) {
    "use strict";

    var currentUrl = URI(window.location.href);

    /**
     * Utility class for Url handling
     *
     */
    var URLMgr = function() {
        /**
         * The current url of the main part
         * @type {string}
         */
        this.mainUrl = '';

        /**
         * The current main anchor
         * @type {string}
         */
        this.anchor = '';

        /**
         * The current detail url
         *
         * @type {string}
         */
        this.detailUrl = '';

        /**
         * The current anchor of the detail url
         *
         * @type {string}
         */
        this.detailAnchor = '';

        /**
         * Extract the anchor of the main url part from the given url
         *
         * @param   {String|URI} url    An URL object to extract the information from
         * @returns {*}
         */
        this.getMainAnchor = function(url) {
            url = url || URI(window.location.href);
            if (typeof url === 'string') {
                url = URI(url);
            }
            var fragment = url.fragment();
            if (fragment.length === 0) {
                return '';
            }
            var parts = fragment.split('!');
            if (parts.length > 0) {
                return parts[0];
            } else {
                return '';
            }
        };

        /**
         * Extract the detail url a the given url. Returns a [URL, ANCHOR] Tupel
         *
         * @param   String url      An optional url to parse (otherwise window.location.href is used)
         * @returns {Array}         A [{String} Url, {String} anchor] tupel
         */
        this.getDetailUrl = function(url) {
            url = url || URI(window.location.href);
            if (typeof url === 'string') {
                url = URI(url);
            }

            var fragment = url.fragment();
            if (fragment.length === 0) {
                return '';
            }
            var parts = fragment.split('!', 2);

            if (parts.length === 2) {
                var result = /detail=(.*)$/.exec(parts[1]);
                if (!result || result.length < 2) {
                    return '';
                }
                return result[1].replace('%23', '#').split('#');
            } else {
                return '';
            }
        };

        /**
         * Overwrite the detail Url and update the hash
         *
         * @param   String url      The url to use for the detail part
         */
        this.setDetailUrl = function(url) {
            if (typeof url === 'string') {
                url = URI(url);
            }
            if( !url.fragment() || url.href() !== '#' + url.fragment()) {
                this.detailUrl = url.clone().fragment('').href();
            }
            this.detailAnchor = this.getMainAnchor(url);
            window.location.hash = this.getUrlHash();
        };

        /**
         * Get the hash of the current detail url and anchor i
         *
         * @returns {string}
         */
        this.getUrlHash = function() {
            var anchor = '#' + this.anchor +
                '!' + ($.trim(this.detailUrl) ? 'detail=' : '') + this.detailUrl +
                (this.detailAnchor  ? '%23' : '') + this.detailAnchor;
            anchor = $.trim(anchor);
            if (anchor === '#!' || anchor === '#') {
                anchor = '';
            }
            return anchor;
        };

        /**
         * Set the main url to be used
         *
         * This triggers the pushstate event or causes a page reload if the history api is
         * not available
         *
         * @param url
         */
        this.setMainUrl = function(url) {
            this.anchor = this.getMainAnchor(url);
            this.mainUrl = URI(url).clone().fragment('').href();
            if (!Modernizr.history) {
                window.location.href = this.mainUrl + this.getUrlHash();
            } else {
                window.history.pushState({}, document.title, this.mainUrl + this.getUrlHash());
                $(window).trigger('pushstate');
            }
        };

        /**
         * Return the href (main path + hash)
         *
         * @returns {string}
         */
        this.getUrl = function() {
            return this.mainUrl + this.getUrlHash();
        };

        /**
         * Take the current url and sync the internal state of this url manager with it
         */
        this.syncWithUrl = function() {
            this.mainUrl =  URI(window.location.href).clone().fragment('').href();
            this.anchor = this.getMainAnchor();
            var urlAnchorTupel = this.getDetailUrl();
            this.detailUrl = urlAnchorTupel[0] || '';
            this.detailAnchor = urlAnchorTupel[1] || '';
        };


        this.syncWithUrl();
    };
    var urlMgr = new URLMgr();

    return urlMgr;
});
