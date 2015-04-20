/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Loader
 *
 * This is where we take care of XHR requests, responses and failures.
 */
(function(Icinga, $) {

    'use strict';

    Icinga.Loader = function (icinga) {

        /**
         * YES, we need Icinga
         */
        this.icinga = icinga;

        /**
         * Our base url
         */
        this.baseUrl = icinga.config.baseUrl;

        this.failureNotice = null;

        /**
         * Pending requests
         */
        this.requests = {};

        this.iconCache = {};

        this.autorefreshEnabled = true;
    };

    Icinga.Loader.prototype = {

        initialize: function () {
            this.icinga.timer.register(this.autorefresh, this, 500);
        },

        /**
         * Load the given URL to the given target
         *
         * @param {string} url     URL to be loaded
         * @param {object} target  Target jQuery element
         * @param {object} data    Optional parameters, usually for POST requests
         * @param {string} method  HTTP method, default is 'GET'
         * @param {string} action  How to handle the response ('replace' or 'append'), default is 'replace'
         */
        loadUrl: function (url, $target, data, method, action, autorefresh) {
            var id = null;

            // Default method is GET
            if ('undefined' === typeof method) {
                method = 'GET';
            }
            if ('undefined' === typeof action) {
                action = 'replace';
            }
            if ('undefined' === typeof autorefresh) {
                autorefresh = false;
            }

            this.icinga.logger.debug('Loading ', url, ' to ', $target);

            // We should do better and ignore requests without target and/or id
            if (typeof $target !== 'undefined' && $target.attr('id')) {
                id = $target.attr('id');
            }

            // If we have a pending request for the same target...
            if (typeof this.requests[id] !== 'undefined') {
                if (autorefresh) {
                    return false;
                }
                // ...ignore the new request if it is already pending with the same URL
                if (this.requests[id].url === url) {
                    this.icinga.logger.debug('Request to ', url, ' is already running for ', $target);
                    return this.requests[id];
                }
                // ...or abort the former request otherwise
                this.icinga.logger.debug(
                    'Aborting pending request loading ',
                    url,
                    ' to ',
                    $target
                );

                this.requests[id].abort();
            }

            // Not sure whether we need this Accept-header
            var headers = { 'X-Icinga-Accept': 'text/html' };

            // Ask for a new window id in case we don't already have one
            if (this.icinga.ui.hasWindowId()) {
                headers['X-Icinga-WindowId'] = this.icinga.ui.getWindowId();
            } else {
                headers['X-Icinga-WindowId'] = 'undefined';
            }

            var self = this;
            var req = $.ajax({
                type   : method,
                url    : url,
                data   : data,
                headers: headers,
                context: self
            });

            req.$target = $target;
            req.url = url;
            req.done(this.onResponse);
            req.fail(this.onFailure);
            req.complete(this.onComplete);
            req.autorefresh = autorefresh;
            req.action = action;
            req.failure = false;
            req.addToHistory = true;

            if (id) {
                this.requests[id] = req;
            }
            if (! autorefresh) {
                req.$target.addClass('impact');
            }
            this.icinga.ui.refreshDebug();
            return req;
        },

        /**
         * Create an URL relative to the Icinga base Url, still unused
         *
         * @param {string} url Relative url
         */
        url: function (url) {
            if (typeof url === 'undefined') {
                return this.baseUrl;
            }
            return this.baseUrl + url;
        },

        stopPendingRequestsFor: function ($el) {
            var id;
            if (typeof $el === 'undefined' || ! (id = $el.attr('id'))) {
                return;
            }

            if (typeof this.requests[id] !== 'undefined') {
                this.requests[id].abort();
            }
        },

        filterAutorefreshingContainers: function () {
            return $(this).data('icingaRefresh') > 0;
        },

        autorefresh: function () {
            var self = this;
            if (self.autorefreshEnabled !== true) {
                return;
            }

            $('.container').filter(this.filterAutorefreshingContainers).each(function (idx, el) {
                var $el = $(el);
                var id = $el.attr('id');
                if (typeof self.requests[id] !== 'undefined') {
                    self.icinga.logger.debug('No refresh, request pending for ', id);
                    return;
                }

                var interval = $el.data('icingaRefresh');
                var lastUpdate = $el.data('lastUpdate');

                if (typeof interval === 'undefined' || ! interval) {
                    self.icinga.logger.info('No interval, setting default', id);
                    interval = 10;
                }

                if (typeof lastUpdate === 'undefined' || ! lastUpdate) {
                    self.icinga.logger.info('No lastUpdate, setting one', id);
                    $el.data('lastUpdate',(new Date()).getTime());
                    return;
                }
                interval = interval * 1000;

                // TODO:
                if ((lastUpdate + interval) > (new Date()).getTime()) {
                    // self.icinga.logger.info(
                    //     'Skipping refresh',
                    //     id,
                    //     lastUpdate,
                    //     interval,
                    //     (new Date()).getTime()
                    // );
                    return;
                }

                if (self.loadUrl($el.data('icingaUrl'), $el, undefined, undefined, undefined, true) === false) {
                    self.icinga.logger.debug(
                        'NOT autorefreshing ' + id + ', even if ' + interval + ' ms passed. Request pending?'
                    );
                } else {
                    self.icinga.logger.debug(
                        'Autorefreshing ' + id + ' ' + interval + ' ms passed'
                    );
                }
                el = null;
            });
        },

        /**
         * Disable the autorefresh mechanism
         */
        disableAutorefresh: function () {
            this.autorefreshEnabled = false;
        },

        /**
         * Enable the autorefresh mechanism
         */
        enableAutorefresh: function () {
            this.autorefreshEnabled = true;
        },

        processNotificationHeader: function(req) {
            var header = req.getResponseHeader('X-Icinga-Notification');
            var self = this;
            if (! header) return false;
            var list = header.split('&');
            $.each(list, function(idx, el) {
                var parts = decodeURIComponent(el).split(' ');
                self.createNotice(parts.shift(), parts.join(' '));
            });
            return true;
        },

        addUrlFlag: function(url, flag)
        {
            if (url.match(/\?/)) {
                return url + '&' + flag;
            } else {
                return url + '?' + flag;
            }
        },

        /**
         * Process the X-Icinga-Redirect HTTP Response Header
         *
         * If the response includes the X-Icinga-Redirect header, redirects to the URL associated with the header.
         *
         * @param   {object}    req     Current request
         *
         * @returns {boolean}           Whether we're about to redirect
         */
        processRedirectHeader: function(req) {
            var icinga      = this.icinga,
                redirect    = req.getResponseHeader('X-Icinga-Redirect');

            if (! redirect) {
                return false;
            }

            redirect = decodeURIComponent(redirect);
            if (redirect.match(/__SELF__/)) {
                if (req.autorefresh) {
                    // Redirect to the current window's URL in case it's an auto-refresh request. If authenticated
                    // externally this ensures seamless re-login if the session's expired
                    redirect = redirect.replace(
                        /__SELF__/,
                        encodeURIComponent(
                            document.location.pathname + document.location.search + document.location.hash
                        )
                    );
                } else {
                    // Redirect to the URL which required authentication. When clicking a link this ensures that we
                    // redirect to the link's URL instead of the current window's URL (see above)
                    redirect = redirect.replace(/__SELF__/, req.url);
                }
            }

            icinga.logger.debug(
                'Got redirect for ', req.$target, ', URL was ' + redirect
            );

            if (req.getResponseHeader('X-Icinga-Rerender-Layout')) {
                var parts = redirect.split(/#!/);
                redirect = parts.shift();
                var redirectionUrl = this.addUrlFlag(redirect, 'renderLayout');
                var r = this.loadUrl(redirectionUrl, $('#layout'));
                r.url = redirect;
                if (parts.length) {
                    r.loadNext = parts;
                } else if (!! document.location.hash) {
                    // Retain detail URL if the layout is rerendered
                    parts = document.location.hash.split('#!').splice(1);
                    if (parts.length) {
                        r.loadNext = parts;
                    }
                }

            } else {

                if (redirect.match(/#!/)) {
                    var parts = redirect.split(/#!/);
                    icinga.ui.layout2col();
                    this.loadUrl(parts.shift(), $('#col1'));
                    this.loadUrl(parts.shift(), $('#col2'));
                } else {

                    if (req.$target.attr('id') === 'col2') { // TODO: multicol
                        if ($('#col1').data('icingaUrl') === redirect) {
                            icinga.ui.layout1col();
                            req.$target = $('#col1');
                            delete(this.requests['col2']);
                        }
                    }

                    this.loadUrl(redirect, req.$target);
                }
            }
            return true;
        },

        cacheLoadedIcons: function($container) {
            // TODO: this is just a prototype, disabled for now
            return;

            var self = this;
            $('img.icon', $container).each(function(idx, img) {
                var src = $(img).attr('src');
                if (typeof self.iconCache[src] !== 'undefined') {
                    return;
                }
                var cache = new Image();
                cache.src = src
                self.iconCache[src] = cache;
            });
        },

        /**
         * Handle successful XHR response
         */
        onResponse: function (data, textStatus, req) {
            var self = this;
            if (this.failureNotice !== null) {
                if (! this.failureNotice.hasClass('fading-out')) {
                    this.failureNotice.remove();
                }
                this.failureNotice = null;
            }

            var url = req.url;
            this.icinga.logger.debug(
                'Got response for ', req.$target, ', URL was ' + url
            );
            this.processNotificationHeader(req);

            var cssreload = req.getResponseHeader('X-Icinga-Reload-Css');
            if (cssreload) {
                this.icinga.ui.reloadCss();
            }

            if (req.getResponseHeader('X-Icinga-Redirect')) return;

            // div helps getting an XML tree
            var $resp = $('<div>' + req.responseText + '</div>');
            var active = false;
            var rendered = false;
            var classes;

            if (req.autorefresh) {
                // TODO: next container url
                active = $('[href].active', req.$target).attr('href');
            }

            var target = req.getResponseHeader('X-Icinga-Container');
            var newBody = false;
            var oldNotifications = false;
            if (target) {
                if (target === 'ignore') {
                    return;
                }
                // If we change the target, oncomplete will fail to clean up
                // This fixes the problem, not using req.$target would be better
                delete this.requests[req.$target.attr('id')];

                req.$target = $('#' + target);
                if (target === 'layout') {
                    oldNotifications = $('#notifications li').detach();
                }
                // We assume target === 'layout' right now. This might not be correct
                this.icinga.ui.layout1col();
                newBody = true;
            }

            var moduleName = req.getResponseHeader('X-Icinga-Module');
            classes = $.grep(req.$target.classes(), function (el) {
               if (el === 'icinga-module' || el.match(/^module\-/)) {
                   return false;
               }
               return true;
            });
            if (moduleName) {
                req.$target.data('icingaModule', moduleName);
                classes.push('icinga-module');
                classes.push('module-' + moduleName);
            } else {
                req.$target.removeData('icingaModule');
                if (req.$target.attr('data-icinga-module')) {
                    req.$target.removeAttr('data-icinga-module');
                }
            }
            req.$target.attr('class', classes.join(' '));

            var title = req.getResponseHeader('X-Icinga-Title');
            if (title && ! req.autorefresh && req.$target.closest('.dashboard').length === 0) {
                this.icinga.ui.setTitle(decodeURIComponent(title));
            }

            var refresh = req.getResponseHeader('X-Icinga-Refresh');
            if (refresh) {
                req.$target.data('icingaRefresh', refresh);
            } else {
                req.$target.removeData('icingaRefresh');
                if (req.$target.attr('data-icinga-refresh')) {
                    req.$target.removeAttr('data-icinga-refresh');
                }
            }

            // Set a window identifier if the server asks us to do so
            var windowId = req.getResponseHeader('X-Icinga-WindowId');
            if (windowId) {
                this.icinga.ui.setWindowId(windowId);
            }

            // Handle search requests, still hardcoded.
            if (req.url.match(/^\/search/) &&
                req.$target.data('icingaUrl').match(/^\/search/) &&
                $('.dashboard', $resp).length > 0 &&
                $('.dashboard .container', req.$target).length > 0)
            {
                // TODO: We need dashboard pane and container identifiers (not ids)
                var targets = [];
                $('.dashboard .container', req.$target).each(function (idx, el) {
                    targets.push($(el));
                });

                var i = 0;
                // Searching for '.dashboard .container' in $resp doesn't dork?!
                $('.dashboard .container', $resp).each(function (idx, el) {
                    var $el = $(el);
                    if ($el.hasClass('dashboard')) {
                        return;
                    }
                    var url = $el.data('icingaUrl');
                    targets[i].data('icingaUrl', url);
                    var title = $('h1', $el).first();
                    $('h1', targets[i]).first().replaceWith(title);

                    self.loadUrl(url, targets[i]);
                    i++;
                });
                rendered = true;
            }

            req.$target.data('icingaUrl', req.url);

            this.icinga.ui.initializeTriStates($resp);

            /* Should we try to fiddle with responses containing full HTML? */
            /*
            if ($('body', $resp).length) {
                req.responseText = $('script', $('body', $resp).html()).remove();
            }
            */
            /*

            var containers = [];

            $('.dashboard .container').each(function(idx, el) {
              urls.push($(el).data('icingaUrl'));
            });
            console.log(urls);
                  $('.container[data-icinga-refresh]').each(function(idx, el) {
                    var $el = $(el);
                    self.loadUrl($el.data('icingaUrl'), $el).autorefresh = true;
                    el = null;
                  });
            */

            if (rendered) return;

            // .html() removes outer div we added above
            this.renderContentToContainer($resp.html(), req.$target, req.action, req.autorefresh);
            if (oldNotifications) {
                oldNotifications.appendTo($('#notifications'));
            }
            if (url.match(/#/)) {
                this.icinga.ui.scrollContainerToAnchor(req.$target, url.split(/#/)[1]);
            }
            if (newBody) {
                this.icinga.ui.fixDebugVisibility().triggerWindowResize();
            }
            self.cacheLoadedIcons(req.$target);

            if (active) {
                var focusedUrl = this.icinga.ui.getFocusedContainerDataUrl();
                var oldSelectionData = this.icinga.ui.loadSelectionData();
                if (typeof oldSelectionData === 'string') {
                    $('[href="' + oldSelectionData + '"]', req.$target).addClass('active');

                } else if (oldSelectionData !== null) {
                    var $container;
                    if (!focusedUrl) {
                        $container = $('document').first();
                    } else {
                        $container = $('.container[data-icinga-url="' + focusedUrl + '"]');
                    }

                    var $table = $container.find('table.action').first();
                    var keys = self.icinga.ui.getSelectionKeys($table);

                    // build map of selected queries
                    var oldSelectionQueries = {};
                    $.each(oldSelectionData, function(i, query){
                        oldSelectionQueries[self.icinga.ui.selectionDataToQueryComp(query)] = true;
                    });

                    // set all new selections to active
                    $table.find('tr[href]').filter(function(){
                            var $tr = $(this);
                            var rowData = self.icinga.ui.getSelectionData($tr, keys, self.icinga);
                            var newSelectionQuery = self.icinga.ui.selectionDataToQueryComp(rowData);
                            if (oldSelectionQueries[newSelectionQuery]) {
                                return true;
                            }
                            return false;
                        }).addClass('active');
                }
            }
        },

        /**
         * Regardless of whether a request succeeded of failed, clean up
         */
        onComplete: function (req, textStatus) {
            // Remove 'impact' class if there was such
            if (req.$target.hasClass('impact')) {
                req.$target.removeClass('impact');
            }

            if (! req.autorefresh) {
                // TODO: Hook for response/url?
                var url = req.url;
                var $forms = $('[action="' + this.icinga.utils.parseUrl(url).path + '"]');
                var $matches = $.merge($('[href="' + url + '"]'), $forms);
                $matches.each(function (idx, el) {
                    if ($(el).closest('#menu').length) {
                        if (req.$target[0].id === 'col1') {
                            self.icinga.behaviors.navigation.resetActive();
                        }
                    } else if ($(el).closest('table.action').length) {
                        $(el).closest('table.action').find('.active').removeClass('active');
                    }
                });

                $matches.each(function (idx, el) {
                    var $el = $(el);
                    if ($el.closest('#menu').length) {
                        if ($el.is('form')) {
                            $('input', $el).addClass('active');
                        } else {
                            if (req.$target[0].id === 'col1') {
                                self.icinga.behaviors.navigation.setActive($el);
                            }
                        }
                        // Interrupt .each, only on menu item shall be active
                        return false;
                    } else if ($(el).closest('table.action').length) {
                        $el.addClass('active');
                    }
                });
            }

            // Update history when necessary. Don't do so for requests triggered
            // by history or autorefresh events
            if (! req.autorefresh && req.addToHistory) {
                if (req.$target.hasClass('container') && ! req.failure) {
                    // We only want to care about top-level containers
                    if (req.$target.parent().closest('.container').length === 0) {
                        this.icinga.history.pushCurrentState();
                    }
                } else {
                    // Request wasn't for a container, so it's usually the body
                    // or the full layout. Push request URL to history:
                    this.icinga.history.pushCurrentState();
                }
            }

            req.$target.data('lastUpdate', (new Date()).getTime());
            delete this.requests[req.$target.attr('id')];
            this.icinga.ui.fadeNotificationsAway();

            this.processRedirectHeader(req);

            if (typeof req.loadNext !== 'undefined') {
                if ($('#col2').length) {
                    this.loadUrl(req.loadNext[0], $('#col2'));
                    this.icinga.ui.layout2col();
                } else {
                  this.icinga.logger.error('Failed to load URL for #col2', req.loadNext);
                }
            }

            this.icinga.ui.refreshDebug();
        },

        /**
         * Handle failed XHR response
         */
        onFailure: function (req, textStatus, errorThrown) {
            var url = req.url;

            req.failure = true;

            req.$target.data('icingaUrl', req.url);

            /*
             * Test if a manual actions comes in and autorefresh is active: Stop refreshing
             */
            if (req.addToHistory && ! req.autorefresh && req.$target.data('icingaRefresh') > 0
            && req.$target.data('icingaUrl') !== url) {
                req.$target.data('icingaRefresh', 0);
                req.$target.data('icingaUrl', url);
            }

            if (req.status > 0) {
                this.icinga.logger.error(
                    req.status,
                    errorThrown + ':',
                    $(req.responseText).text().replace(/\s+/g, ' ').slice(0, 100)
                );
                this.renderContentToContainer(
                    req.responseText,
                    req.$target,
                    req.action,
                    req.autorefresh
                );
            } else {
                if (errorThrown === 'abort') {
                    this.icinga.logger.debug(
                        'Request to ' + url + ' has been aborted for ',
                        req.$target
                    );

                    // Aborted requests should not be added to browser history
                    req.addToHistory = false;
                } else {
                    if (this.failureNotice === null) {
                        this.failureNotice = this.createNotice(
                            'error',
                            'The connection to the Icinga web server was lost at ' +
                            this.icinga.utils.timeShort() +
                            '.',
                            true
                        );

                        this.icinga.ui.fixControls();
                    }

                    this.icinga.logger.error(
                        'Failed to contact web server loading ',
                        url,
                        ' for ',
                        req.$target
                    );
                }
            }
        },

        /**
         * Create a notification. Can be improved.
         */
        createNotice: function (severity, message, persist) {
            var c = severity;
            if (persist) {
                c += ' persist';
            }
            var $notice = $(
                '<li class="' + c + '">' + message + '</li>'
            ).appendTo($('#notifications'));

            this.icinga.ui.fixControls();

            if (!persist) {
                this.icinga.ui.fadeNotificationsAway();
            }

            return $notice;
        },

        /**
         * Smoothly render given HTML to given container
         */
        renderContentToContainer: function (content, $container, action, autorefresh) {
            // Container update happens here
            var scrollPos = false;
            var self = this;
            var containerId = $container.attr('id');
            if (typeof containerId !== 'undefined') {
                if (autorefresh) {
                    scrollPos = $container.scrollTop();
                } else {
                    scrollPos = 0;
                }
            }
            if (autorefresh && $.contains($container[0], document.activeElement)) {
                var origFocus = self.icinga.utils.getDomPath(document.activeElement);
            }

            $container.trigger('beforerender');

            var discard = false;
            $.each(self.icinga.behaviors, function(name, behavior) {
                if (behavior.renderHook) {
                    var changed = behavior.renderHook(content, $container, action, autorefresh);
                    if (!changed) {
                        discard = true;
                    } else {
                        content = changed;
                    }
                }
            });
            if (discard) {
                return;
            }

            // TODO: We do not want to wrap this twice...
            var $content = $('<div>' + content + '</div>');

            // Disable all click events while rendering
            // (Disabling disabled, was ways too slow)
            // $('*').click(function (event) {
            //     event.stopImmediatePropagation();
            //     event.stopPropagation();
            //     event.preventDefault();
            // });

            $('.container', $container).each(function() {
                self.stopPendingRequestsFor($(this));
            });

            if (false &&
                $('.dashboard', $content).length > 0 &&
                $('.dashboard', $container).length === 0
            ) {
                // $('.dashboard', $content)
                // $container.html(content);

            } else {
                if ($container.closest('.dashboard').length) {
                    var title = $('h1', $container).first().detach();
                    $container.html(title).append(content);
                } else if (action === 'replace') {
                    $container.html(content);
                } else {
                    $container.append(content);
                }
            }
            this.icinga.ui.assignUniqueContainerIds();

            if (origFocus && origFocus.length > 0 && origFocus[0] !== '') {
                setTimeout(function() {
                    $(self.icinga.utils.getElementByDomPath(origFocus)).focus();
                }, 0);
            }

            // TODO: this.icinga.events.refreshContainer(container);
            $container.trigger('rendered');

            if (scrollPos !== false) {
                $container.scrollTop(scrollPos);
            }
            var icinga = this.icinga;
            //icinga.events.applyHandlers($container);
            icinga.ui.initializeControls($container);
            icinga.ui.fixControls();

            // Re-enable all click events (disabled as of performance reasons)
            // $('*').off('click');
        },

        /**
         * On shutdown we kill all pending requests
         */
        destroy: function() {
            $.each(this.requests, function(id, request) {
                request.abort();
            });
            this.icinga = null;
            this.requests = {};
        }

    };

}(Icinga, jQuery));
