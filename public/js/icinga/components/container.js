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

define(['jquery', 'logging', 'icinga/componentLoader', 'URIjs/URI', 'URIjs/URITemplate', 'icinga/util/url'],
    function($, logger, componentLoader, URI, Tpl, urlMgr) {
    "use strict";

    var Icinga;

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

    var pendingDetailRequest = null;
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
         * Return the container that is at the nearest location to this element, or the element itself if it is a container
         *
         * Containers are either the icingamain and icingadetail ids or components tagged as app/container
         *
         * @param {String, jQuery, HTMLElement} target      The node to use as the starting point
         *
         * @returns {HTMLElement|null}                      The nearest container found or null if target is no container
         *                                                  and no container is above target
         */
        this.findNearestContainer = function(target) {
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
            this.containerDom = $(this.findNearestContainer(target));
            this.containerType = CONTAINER_TYPES.GENERIC;

            if (this.containerDom.attr('id') === CONTAINER_TYPES.MAIN) {
                this.containerType = CONTAINER_TYPES.MAIN;
            } else if (this.containerDom.attr('id') === CONTAINER_TYPES.DETAIL) {
                this.containerType = CONTAINER_TYPES.DETAIL;
            } else {
                this.containerType = CONTAINER_TYPES.GENERIC;
            }

            if (this.containerDom.data('loadIndicator') !== true) {
                this.installDefaultLoadIndicator();
                this.containerDom.data('loadIndicator', true);
            }
        };


        /**
         * Returns the window without the hostname
         *
         * @returns {string}    path with query, search and hash
         */
        var getWindowLocationWithoutHost = function() {
            return window.location.pathname + window.location.search + window.location.hash;
        };

        /**
         * Create default load mask
         *
         * @private
         */
        var createDefaultLoadIndicator = function() {
            if (this.containerDom.find('div.load-indicator').length === 0) {
                var content = '<div class="load-indicator">' +
                    '<div class="mask"></div>' +
                    '<div class="label">Loading</div>' +
                    '</div>';
                $(this.containerDom).append(content);
            }
        };

        /**
         * Remove default load mask
         *
         * @private
         */
        var destroyDefaultLoadIndicator = function() {
            this.containerDom.find('div.load-indicator').remove();
        };


        /**
         * Load the provided url, stop all pending requests for this container and call replaceDom for the returned html
         *
         * This method relaods the page if a 401 (Authorization required) header is encountered
         *
         * @param {String, URI} url     The Url to load or and URI.js object encapsulating it
         */
        this.updateFromUrl = function(url) {

            if (this.containerType === CONTAINER_TYPES.DETAIL) {
                urlMgr.setDetailUrl(url);
            } else {
                urlMgr.setMainUrl(url);

            }
        };

        this.replaceDomAsync = function(url) {
            urlMgr.syncWithUrl();
            if (urlMgr.detailUrl === '') {
                this.hideDetail();
            }

            if (pendingDetailRequest) {
                pendingDetailRequest.abort();
            }
            this.containerDom.trigger('showLoadIndicator');
            pendingDetailRequest = $.ajax({
                'url'  : url,
                'data' : {
                    'render' : 'detail'
                }
            }).done(
                (function(response) {
                    this.replaceDom($(response));
                }).bind(this)
            ).fail(
                (function(response, reason) {
                    var errorReason;
                    if (response.statusCode.toString()[0] === '4') {
                        errorReason = 'The Requested View Couldn\'t Be Found<br/>';
                    } else {
                        errorReason = response.responseText;
                    }
                    this.replaceDom(
                        $('<div class="alert alert-danger">').text(errorReason)
                    );
                }).bind(this)
            ).always((function() {
                this.containerDom.trigger('hideLoadIndicator');
            }).bind(this));
        };

        this.getUrl = function() {
            if (this.containerType === CONTAINER_TYPES.DETAIL) {
                return urlMgr.detailUrl;
            } else {
                return urlMgr.mainUrl;
            }

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
            this.containerDom.trigger('showLoadIndicator');
            this.containerDom.empty().append(domNodes);
            this.containerDom.trigger('updated', [domNodes]);
            this.containerDom.trigger('hideLoadIndicator');
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

        /**
         * Register a method to show a load indicator
         *
         * @param {function} fn The function to register
         */
        this.registerOnShowLoadIndicator = function(fn) {
            this.containerDom.on('showLoadIndicator', fn);

        };

        /**
         * Register a method when load indicator should be removed
         *
         * @param {function} fn The function to register
         */
        this.registerOnHideLoadIndicator = function(fn) {
            this.containerDom.on('hideLoadIndicator', fn);
        };

        /**
         * Install default load indicator
         */
        this.installDefaultLoadIndicator = function() {
            this.registerOnShowLoadIndicator($.proxy(createDefaultLoadIndicator, this));
            this.registerOnHideLoadIndicator($.proxy(destroyDefaultLoadIndicator, this));
        };

        /**
         * Remove default load indicator
         */
        this.removeDefaultLoadIndicator = function() {
            this.containerDom.off('showLoadIndicator');
            this.containerDom.off('hideLoadIndicator');
        };

        this.onLinkClick = function(ev, target) {
            if ($.trim($(target).attr('href')) === '#') {
                return true;
            }
            var url = URI($(target).attr('href'));
            var explicitTarget = $(target).attr('data-icinga-target');

            var isHash = ('#' + url.fragment() === url.href());
            if (isHash) {

                explicitTarget = this.containerType === CONTAINER_TYPES.MAIN ? 'main' : 'detail';
            }
            if (explicitTarget) {

                urlMgr[{
                    'main'   : 'setMainUrl',
                    'detail' : 'setDetailUrl',
                    'self'   : 'setUrl'
                }[explicitTarget]](url.href());

            } else if (this.containerType === CONTAINER_TYPES.MAIN) {
                urlMgr.setDetailUrl(url.href());
            } else {
                urlMgr.setMainUrl(url.href());
            }


            ev.preventDefault();
            ev.stopPropagation();
            return false;

        };

        this.setUrl = function(url) {
            if (typeof url === 'string') {
                url = URI(url);
            }
            console.log(url);
            if (this.containerType === CONTAINER_TYPES.MAIN) {
                urlMgr.setMainUrl(url.href());
            } else {
                urlMgr.setDetailUrl(url.href());
            }
        };

        this.refresh = function() {
            if (this.containerType === CONTAINER_TYPES.MAIN) {
                Container.getMainContainer().replaceDomAsync(urlMgr.mainUrl);
            } else {
                Container.getDetailContainer().replaceDomAsync(urlMgr.detailUrl);
            }
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
        return (/^\/\//).test(URI(link).relativeTo(window.location.href).href());
    };



    /**
     * Return the page's detail container (which is always there)
     *
     * @returns {Container}     The detail container of the page
     */
    Container.getDetailContainer = function() {
        detailContainer = detailContainer || new Container('#icingadetail');
        if(!jQuery.contains(document.body, mainContainer)) {
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

        if (detailDom.find('*').length === 0) {
            var mainHeight = $(window).height();
            detailDom.append('<div style="height: ' + mainHeight + 'px;"></div>');
        }

        mainDom.removeClass();
        detailDom.removeClass();

        mainDom.addClass('col-xs-pull-12 col-sm-pull-12 col-md-pull-12 col-lg-7');
        detailDom.addClass('col-xs-push-12 col-sm-push-12 col-md-push-12 col-lg-5');
    };

    /**
     * Hide the detail container and expand the main container
     *
     * Also updates the Url by removing the detail part
     *
     * Available as a static method on the Container object or as an instance method
     */
    Container.prototype.hideDetail = Container.hideDetail = function() {
        urlMgr.setDetailUrl('');
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
    };
    /**
     * Injects the icinga object into the Container class
     *
     * This can't be done via requirejs as we would end up in circular references
     *
     * @param {Icinga} icingaObj        The Icinga object to use for reloading
     */
    Container.setIcinga = function(icingaObj) {
        Icinga = icingaObj;
    };

    $('body').on('click', '*[data-icinga-component="app/container"], #icingamain, #icingadetail', function(ev) {
        var targetEl = ev.target || ev.toElement || ev.relatedTarget;

        if (targetEl.tagName.toLowerCase() !== 'a') {
            targetEl = $(targetEl).parents('a')[0];
            if (!targetEl) {
                return true;
            }
        }
        return (new Container(targetEl)).onLinkClick(ev, targetEl);

    });

    $(window).on('hashchange', (function() {
        urlMgr.syncWithUrl();
        Container.getDetailContainer().replaceDomAsync(urlMgr.detailUrl);
    }));


    if (urlMgr.detailUrl) {
        Container.getDetailContainer().replaceDomAsync(urlMgr.detailUrl);
    }

    return Container;
});
