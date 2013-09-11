/*global Icinga:false, Modernizr: false, document: false, History: false, define:false require:false base_url:false console:false */
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

define(['jquery', 'logging', 'icinga/componentLoader', 'URIjs/URI', 'URIjs/URITemplate'], function($, logger, componentLoader, URI) {
    "use strict";

    /**
     * Static reference to the main container, populated on the first 'getMainContainer' call
     *
     * @type {Container}
     */
    var mainContainer = null;

    /**
     * Static reference to the detail container, populated on the first getDetailContainer call
     *
     * @type {Container}
     */
    var detailContainer = null;

    /**
     * A handler for accessing icinga containers, i.e. the #icingamain, #icingadetail containers and specific 'app/container'
     * components.
     *
     * This component can be constructed with every object as the parameter and will provide access to the nearest
     * container (which could be the applied object itself, if it is a container) wrapping this object.
     *
     * The windows url should always be modified with this implementation, so an objects context should point to a
     * new URL, call new Container('#myObject').updateContainerHref('/my/url')
     *
     * This requirejs module also registers a global handler catching all links of the main container and rendering
     * their content to the main container, in case you don't want to extend the container with additional handlers.
     *
     * @param {HTMLElement, jQuery, String} target      A jQuery resultset, dom element or matcher string
     */
    var Container = function(target) {

        /**
         * Enumeration of possible container types
         *
         * @type {{GENERIC: string, MAIN: string, DETAIL: string}}
         */
        var CONTAINER_TYPES = {
            'GENERIC' : 'generic',
            'MAIN' :    'icingamain',
            'DETAIL':   'icingadetail'
        };

        /**
         * Set to true when no history changes should be made
         *
         * @type {boolean}      true to disable History.js calls, false to reenable them
         */
        this.freezeHistory = false;

        /**
         * Return the container that is at the nearest location to this element, or the element itself if it is a container
         *
         * Containers are either the icingamain and icingadetail ids or components tagged as app/container
         *
         * @param {String, jQuery, HTMLElement} target      The node to use as the starting point
         *
         * @returns {HTMLElement|null}                      The nearest container found or null if target is no container
         *                                                  and no container is above target
         */
        var findNearestContainer = function(target) {
            target = $(target);
            if (target.attr('data-icinga-component') === 'app/container' ||
                    target.attr('id') === 'icingamain' || target.attr('id') === 'icingadetail') {
                return target;
            }
            return target.parents('[data-icinga-component="app/container"], #icingamain, #icingadetail')[0];
        };

        /**
         * Find the container responsible for target and determine it's type
         *
         * @param {HTMLElement, jQuery, String} target      A jQuery resultset, dom element or matcher string
         */
        this.construct = function(target) {
            this.containerDom = $(findNearestContainer(target));
            this.containerType = CONTAINER_TYPES.GENERIC;

            if (this.containerDom.attr('id') === CONTAINER_TYPES.MAIN) {
                this.containerType = CONTAINER_TYPES.MAIN;
            } else if (this.containerDom.attr('id') === CONTAINER_TYPES.DETAIL) {
                this.containerType = CONTAINER_TYPES.DETAIL;
            } else {
                this.containerType = CONTAINER_TYPES.GENERIC;
            }
        };

        var getWindowLocationWithoutHost = function() {
            return window.location.pathname + window.location.search + window.location.hash;
        };

        /**
         * Extract and return the main container's location from the current Url
         *
         * This takes the window's Url and removes the detail part
         *
         * @returns {string}        The Url of the main container
         */
        var getMainContainerHrefFromUrl = function() {
            // main has the url without the icingadetail part
            var href = URI(getWindowLocationWithoutHost());
            href.removeQuery('detail');
            return href.href();
        };

        /**
         * Return the detail container's location from the current Url
         *
         * This takes the detail parameter of the url and returns it or
         * undefined if no location is given
         *
         * @returns {string|undefined}  The Url of the detail container or undefined if no detail container is active
         */
        var getDetailContainerHrefFromUrl = function() {
            var location = new URI(getWindowLocationWithoutHost());
            var href = URI.parseQuery(location.query()).detail;
            if (!href) {
                return;
            }
            // detail is a query param, so it is possible that (due to a bug or whatever) multiple
            // detail fields are declared and returned as arrays
            if (typeof href !== 'string') {
                href = href[0];
            }
            // transform the detail parmameter to an Url
            return URI(href).href();
        };

        /**
         * Return the Url of this container
         *
         * This is mostly determined by the Url of the window, but for generic containers we have to rely on the
         * "data-icinga-href" attribute of the container (which is also available for main and detail, but less
         * reliable during history changes)
         *
         * @returns {String|undefined}  The Url of the container or undefined if the container has no Url set
         */
        this.getContainerHref = function() {
            switch (this.containerType) {
                case CONTAINER_TYPES.MAIN:
                    return getMainContainerHrefFromUrl();
                case CONTAINER_TYPES.DETAIL:
                    return getDetailContainerHrefFromUrl();
                case CONTAINER_TYPES.GENERIC:
                    if (this.containerDom.attr('data-icinga-href')) {
                        return URI(this.containerDom.attr('data-icinga-href'));
                    } else {
                        return URI(getWindowLocationWithoutHost()).href();
                    }
            }
        };

        /**
         * Return a href with representing the current view, but url as the main container
         *
         * @param {URI} url     The main Url to use as an URI.js object
         *
         * @returns {URI}       The modified URI.js containing the new main and the current detail link
         */
        var setMainContainerHref = function(url) {
            var detail = getDetailContainerHrefFromUrl();
            if (detail) {
                url.addQuery('detail', detail);
            }
            return url;
        };

        /**
         * Return a complete Href string representing the current detail href and the provided main Url
         *
         * @param {URI} url         The detail Url to use as an URI.js object
         *
         * @returns {URI}           The modified URI.js containing the new detail and the current main link
         */
        var setDetailContainerHref = function(url) {
            var location = new URI(window.location.href);
            location.removeQuery('detail');
            if (typeof url !== 'undefined') { // no detail Url given
                location.addQuery('detail', url);
            }
            return location;
        };

        /**
         * Update the Url of this container and let the Url reflect the new changes, if required
         *
         * This updates the window Url and the data-icinga-href attribute of the container. The latter one is required
         * to see which url is the last one the container displayed (e.g. after History changes, the url has changed
         * but the containers data-icinga-href still points to the containers element).
         *
         * @param {String|URI} url     An Url string or a URI.js object representing the new Url for this container
         */
        this.updateContainerHref = function(url) {
            if (typeof url === "string") {
                url = URI(url);
            }
            var containerUrl, windowUrl;
            switch (this.containerType) {
                case CONTAINER_TYPES.MAIN:
                    windowUrl = setMainContainerHref(url);
                    containerUrl = windowUrl.clone().removeQuery('detail');
                    break;
                case CONTAINER_TYPES.DETAIL:
                    windowUrl = setDetailContainerHref(url);
                    containerUrl = url;
                    break;
                case CONTAINER_TYPES.GENERIC:
                    containerUrl = url;
                    break;
            }

            if (containerUrl) {
                this.containerDom.attr('data-icinga-href', containerUrl);
            } else {
                this.containerDom.removeAttr('data-icinga-href');
            }
            if (!this.freezeHistory) {
                History.pushState({container: this.containerDom.attr('id')}, document.title, windowUrl.href());
            }
            return windowUrl;
        };

        /**
         * Synchronize the container with the currently active window Url
         *
         * This is called mostly after changes in the history and makes sure the container contains the same content
         * as the Url refers to. If the Url is the same as the Url in the container (the one in the data-icinga-href
         * attribute), the container will be untouched, otherwise it's content and data-icinga-href attribute will be
         * updated with the Url from the window.
         */
        this.syncWithCurrentUrl = function() {
            if (this.containerType === CONTAINER_TYPES.GENERIC) {
                return; // generic containers would require this method to be specialised
            }
            // Catch initial page loading: Here no data-icinga-href is set and no url is given, so we're safe to ignore this
            if (typeof this.containerDom.attr('data-icinga-href') === 'undefined' &&
                    typeof this.getContainerHref() === 'undefined') {
                return;
            }
            // This is the case when an detail is removed on history back
            if (typeof this.getContainerHref() === 'undefined' && typeof this.containerDom.attr('data-icinga-href') !== 'undefined') {
                this.containerDom.removeAttr('data-icinga-href');
                this.containerDom.empty();
                this.hideDetail();
                logger.debug("Hiding detail panel on Url change");
                return;
            }

            if (!URI(this.getContainerHref()).equals(this.containerDom.attr('data-icinga-href'))) {
                logger.debug(
                    "Applying URL change for ", this.containerType,
                    "from", this.getContainerHref(),
                    "to", this.containerDom.attr('data-icinga-href')
                );

                if (typeof this.containerDom.attr('data-icinga-href') === 'undefined') { // container is empty now
                    //this.replaceDom('');
                } else {
                    this.freezeHistory = true;
                    this.replaceDomFromUrl(this.getContainerHref());
                    this.freezeHistory = false;
                }
            } else {
                logger.debug("No action performed on Url change, same Url for ", this.containerType);
            }
        };

        /**
         * Load the provided url, stop all pending requests for this container and call replaceDom for the returned html
         *
         * This method relaods the page if a 401 (Authorization required) header is encountered
         *
         * @param {String, URI} url     The Url to load or and URI.js object encapsulating it
         */
        this.replaceDomFromUrl = function(url) {
            if (!Modernizr.history) {
                window.location.href = this.updateContainerHref(url);
            }

            this.updateContainerHref(url);
            var scope = this;
            if (this.containerDom.pending) {
                this.containerDom.pending.abort();
            }
            this.containerDom.pending = $.ajax({
                url: url,
                success: function(domNodes) {
                    scope.replaceDom(domNodes);
                },
                error: function(response) {
                    if (response.status === 401) {
                        window.location.reload();
                    }
                }
            });
        };

        /**
         * Remove all dom nodes from this container and replace them with the ones from domNodes
         *
         * Triggers the custom "updated" event and causes a rescan for components on the DOM nodes
         *
         * If keepLayout is given, the detail panel won't be expanded if this is an update for the detail panel,
         * otherwise it will be automatically shown.
         *
         * @param {String, jQuery, HTMLElement, Array} domNodes     Any valid representation of the Dom nodes to insert
         * @param {boolean} keepLayout                              Whether to keep the layout untouched, even if detail
         *                                                          is updated end collapsed
         *
         * @see registerOnUpdate
         */
        this.replaceDom = function(domNodes, keepLayout) {
            var newDom = $('#icingamain', domNodes);
            this.containerDom.empty().append(newDom.children());
            this.containerDom.trigger('updated', [domNodes]);
            componentLoader.load();
            if (!keepLayout) {
                if (this.containerType === CONTAINER_TYPES.DETAIL) {
                    this.showDetail();
                }
            }
        };

        /**
         * Register a method to be called when this container is updated
         *
         * @param {function} fn         The function to call when the container is updated
         */
        this.registerOnUpdate = function(fn) {
            this.containerDom.on('updated', fn);
        };

        this.construct(target);
    };

    /**
     * Static method for detecting whether the given link is external or only browserside (hash links)
     *
     * @param {String} link     The link to test for being site-related
     *
     * @returns {boolean}       True when the link should be executed with the browsers normal behaviour, false
     *                          when the link should be catched and processed internally
     */
    Container.isExternalLink = function(link) {
        if (link[0] === '#') {
            return true;
        }
        return (/^\/\//).test(URI(link).relativeTo(window.location.href).href());
    };

    /**
     * Return the page's detail container (which is always there)
     *
     * @returns {Container}     The detail container of the page
     */
    Container.getDetailContainer = function() {
        detailContainer = detailContainer || new Container('#icingadetail');
        if(!jQuery.contains(document.body, detailContainer)) {
            detailContainer =  new Container('#icingadetail');
        }
        return detailContainer;
    };

    /**
     * Return the page's main container (which is always there)
     *
     * @returns {Container}     The main container of the page
     */
    Container.getMainContainer = function() {
        mainContainer = mainContainer || new Container('#icingamain');
        if(!jQuery.contains(document.body, mainContainer)) {
            mainContainer =  new Container('#icingamain');
        }
        return mainContainer;
    };

    /**
     * Expand the detail container and shrinken the main container
     *
     * Available as a static method on the Container object or as an instance method
     */
    Container.prototype.showDetail = Container.showDetail = function() {
        var mainDom = Container.getMainContainer().containerDom,
            detailDom = Container.getDetailContainer().containerDom;

        mainDom.removeClass();
        detailDom.removeClass();
        mainDom.addClass('hidden-md');
        detailDom.addClass('col-md-12');
        mainDom.addClass('col-lg-7');
        detailDom.addClass('col-lg-5');
        mainDom.addClass('hidden-xs');
        detailDom.addClass('col-xs-12');
        mainDom.addClass('hidden-sm');
        detailDom.addClass('col-sm-12');
    };

    /**
     * Hide the detail container and expand the main container
     *
     * Also updates the Url by removing the detail part
     *
     * Available as a static method on the Container object or as an instance method
     */
    Container.prototype.hideDetail = Container.hideDetail = function() {
        var mainDom = Container.getMainContainer().containerDom,
            detailDom = Container.getDetailContainer().containerDom;

        mainDom.removeClass();
        detailDom.removeClass();
        mainDom.addClass('col-md-12');
        detailDom.addClass('hidden-md');
        mainDom.addClass('col-lg-12');
        detailDom.addClass('hidden-lg');
        mainDom.addClass('col-xs-12');
        detailDom.addClass('hidden-xs');
        mainDom.addClass('col-sm-12');
        detailDom.addClass('hidden-sm');
        detailDom.removeAttr('data-icinga-href');
        if (typeof this.freezeHistory === 'undefined' || !this.freezeHistory) {
            History.replaceState(
                {},
                document.title,
                URI(window.location.href).removeQuery('detail').href()
            );
        }
    };
    if (Modernizr.history) {
        /**
         * Register the click behaviour of the main container, which means that every link, if not catched in a
         * more specific handler, causes an update of the main container if it's not external or a browser behaviour link
         * (those starting with '#').
         */
         $('body').on('click', '#icingamain', function(ev) {
            var targetEl = ev.target || ev.toElement || ev.relatedTarget;
            if (targetEl.tagName.toLowerCase() !== 'a') {
                return true;
            }

            if (Container.isExternalLink($(targetEl).attr('href'))) {
                return true;
            } else {
                Container.getMainContainer().replaceDomFromUrl(URI($(targetEl).attr('href')).href());
                ev.preventDefault();
                ev.stopPropagation();
                return false;
            }
        });
    }
    return Container;
});