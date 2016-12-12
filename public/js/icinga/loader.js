/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
         * @param {string}  url             URL to be loaded
         * @param {object}  target          Target jQuery element
         * @param {object}  data            Optional parameters, usually for POST requests
         * @param {string}  method          HTTP method, default is 'GET'
         * @param {string}  action          How to handle the response ('replace' or 'append'), default is 'replace'
         * @param {boolean} autorefresh     Whether the cause is a autorefresh or not
         * @param {object}  progressTimer   A timer to be stopped when the request is done
         */
        loadUrl: function (url, $target, data, method, action, autorefresh, progressTimer) {
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
                // ... ignore the new request if it is already pending with the same URL. Only abort GETs, as those
                // are the only methods that are guaranteed to return the same value
                if (this.requests[id].url === url && method === 'GET') {
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

            // This is jQuery's default content type
            var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';

            var isFormData = typeof window.FormData !== 'undefined' && data instanceof window.FormData;
            if (isFormData) {
                // Setting false is mandatory as the form's data
                // won't be recognized by the server otherwise
                contentType = false;
            }

            var _this = this;
            var req = $.ajax({
                type   : method,
                url    : url,
                data   : data,
                headers: headers,
                context: _this,
                contentType: contentType,
                processData: ! isFormData
            });

            req.$target = $target;
            req.url = url;
            req.done(this.onResponse);
            req.fail(this.onFailure);
            req.complete(this.onComplete);
            req.autorefresh = autorefresh;
            req.action = action;
            req.addToHistory = true;
            req.progressTimer = progressTimer;

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
         * Mimic XHR form submission by using an iframe
         *
         * @param {object} $form    The form being submitted
         * @param {string} action   The form's action URL
         * @param {object} $target  The target container
         */
        submitFormToIframe: function ($form, action, $target) {
            var _this = this;

            $form.prop('action', _this.icinga.utils.addUrlParams(action, {
                '_frameUpload': true
            }));
            $form.prop('target', 'fileupload-frame-target');
            $('#fileupload-frame-target').on('load', function (event) {
                var $frame = $(event.target);
                var $contents = $frame.contents();

                var $redirectMeta = $contents.find('meta[name="redirectUrl"]');
                if ($redirectMeta.length) {
                    _this.redirectToUrl($redirectMeta.attr('content'), $target);
                } else {
                    // Fetch the frame's new content and paste it into the target
                    _this.renderContentToContainer(
                        $contents.find('body').html(),
                        $target,
                        'replace'
                    );
                }

                $frame.prop('src', 'about:blank'); // Clear the frame's dom
                $frame.off('load'); // Unbind the event as it's set on demand
            });
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
            var _this = this;
            if (_this.autorefreshEnabled !== true) {
                return;
            }

            $('.container').filter(this.filterAutorefreshingContainers).each(function (idx, el) {
                var $el = $(el);
                var id = $el.attr('id');
                if (typeof _this.requests[id] !== 'undefined') {
                    _this.icinga.logger.debug('No refresh, request pending for ', id);
                    return;
                }

                var interval = $el.data('icingaRefresh');
                var lastUpdate = $el.data('lastUpdate');

                if (typeof interval === 'undefined' || ! interval) {
                    _this.icinga.logger.info('No interval, setting default', id);
                    interval = 10;
                }

                if (typeof lastUpdate === 'undefined' || ! lastUpdate) {
                    _this.icinga.logger.info('No lastUpdate, setting one', id);
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

                if (_this.loadUrl($el.data('icingaUrl'), $el, undefined, undefined, undefined, true) === false) {
                    _this.icinga.logger.debug(
                        'NOT autorefreshing ' + id + ', even if ' + interval + ' ms passed. Request pending?'
                    );
                } else {
                    _this.icinga.logger.debug(
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
            var _this = this;
            if (! header) return false;
            var list = header.split('&');
            $.each(list, function(idx, el) {
                var parts = decodeURIComponent(el).split(' ');
                _this.createNotice(parts.shift(), parts.join(' '));
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

            this.redirectToUrl(
                redirect, req.$target, req.url, req.getResponseHeader('X-Icinga-Rerender-Layout'), req.forceFocus,
                req.getResponseHeader('X-Icinga-Refresh')
            );
            return true;
        },

        /**
         * Redirect to the given url
         *
         * @param {string}  url
         * @param {object}  $target
         * @param {string}  origin
         * @param {boolean} rerenderLayout
         */
        redirectToUrl: function (url, $target, origin, rerenderLayout, forceFocus, autoRefreshInterval) {
            var icinga = this.icinga;

            if (typeof rerenderLayout === 'undefined') {
                rerenderLayout = false;
            }

            icinga.logger.debug(
                'Got redirect for ', $target, ', URL was ' + url
            );

            if (rerenderLayout) {
                var parts = url.split(/#!/);
                url = parts.shift();
                var redirectionUrl = this.addUrlFlag(url, 'renderLayout');
                var r = this.loadUrl(redirectionUrl, $('#layout'));
                r.historyUrl = url;
                if (parts.length) {
                    r.loadNext = parts;
                } else if (!! document.location.hash) {
                    // Retain detail URL if the layout is rerendered
                    parts = document.location.hash.split('#!').splice(1);
                    if (parts.length) {
                        r.loadNext = $.grep(parts, function (url) {
                            if (url !== origin) {
                                icinga.logger.debug('Retaining detail url ' + url);
                                return true;
                            }

                            icinga.logger.debug('Discarding detail url ' + url + ' as it\'s the origin of the redirect');
                            return false;
                        });
                    }
                }
            } else {
                if (url.match(/#!/)) {
                    var parts = url.split(/#!/);
                    icinga.ui.layout2col();
                    this.loadUrl(parts.shift(), $('#col1'));
                    this.loadUrl(parts.shift(), $('#col2'));
                } else {
                    if ($target.attr('id') === 'col2') { // TODO: multicol
                        if ($('#col1').data('icingaUrl').split('?')[0] === url.split('?')[0]) {
                            icinga.ui.layout1col();
                            $target = $('#col1');
                            delete(this.requests['col2']);
                        }
                    }

                    var req = this.loadUrl(url, $target);
                    req.forceFocus = url === origin ? forceFocus : null;
                    req.autoRefreshInterval = autoRefreshInterval;
                }
            }
        },

        cacheLoadedIcons: function($container) {
            // TODO: this is just a prototype, disabled for now
            return;

            var _this = this;
            $('img.icon', $container).each(function(idx, img) {
                var src = $(img).attr('src');
                if (typeof _this.iconCache[src] !== 'undefined') {
                    return;
                }
                var cache = new Image();
                cache.src = src
                _this.iconCache[src] = cache;
            });
        },

        /**
         * Handle successful XHR response
         */
        onResponse: function (data, textStatus, req) {
            var _this = this;
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

            if (req.getResponseHeader('X-Icinga-Redirect')) {
                return;
            }

            if (req.getResponseHeader('X-Icinga-Announcements') === 'refresh') {
                _this.loadUrl(_this.url('/layout/announcements'), $('#announcements'));
            }

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

            if (target !== 'layout') {
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

                var refresh = req.autoRefreshInterval || req.getResponseHeader('X-Icinga-Refresh');
                if (refresh) {
                    req.$target.data('icingaRefresh', refresh);
                } else {
                    req.$target.removeData('icingaRefresh');
                    if (req.$target.attr('data-icinga-refresh')) {
                        req.$target.removeAttr('data-icinga-refresh');
                    }
                }
            }

            var title = req.getResponseHeader('X-Icinga-Title');
            if (title && ! req.autorefresh && req.$target.closest('.dashboard').length === 0) {
                this.icinga.ui.setTitle(decodeURIComponent(title));
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

                    _this.loadUrl(url, targets[i]);
                    i++;
                });
                rendered = true;
            }

            req.$target.data('icingaUrl', req.url);

            this.icinga.ui.initializeTriStates($resp);

            if (rendered) {
                return;
            }

            if (typeof req.progressTimer !== 'undefined') {
                this.icinga.timer.unregister(req.progressTimer);
            }

            // .html() removes outer div we added above
            this.renderContentToContainer($resp.html(), req.$target, req.action, req.autorefresh, req.forceFocus);
            if (oldNotifications) {
                oldNotifications.appendTo($('#notifications'));
            }
            if (url.match(/#/)) {
                this.icinga.ui.focusElement(url.split(/#/)[1], req.$target);
            }
            if (newBody) {
                this.icinga.ui.fixDebugVisibility().triggerWindowResize();
            }
            _this.cacheLoadedIcons(req.$target);
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

                if (req.$target[0].id === 'col1') {
                    this.icinga.behaviors.navigation.trySetActiveByUrl(url);
                }

                var $forms = $('[action="' + this.icinga.utils.parseUrl(url).path + '"]');
                var $matches = $.merge($('[href="' + url + '"]'), $forms);
                $matches.each(function (idx, el) {
                    var $el = $(el);
                    if ($el.closest('#menu').length) {
                        if ($el.is('form')) {
                            $('input', $el).addClass('active');
                        }
                        // Interrupt .each, only one menu item shall be active
                        return false;
                    }
                });
            }

            // Update history when necessary
            if (! req.autorefresh && req.addToHistory) {
                if (req.$target.hasClass('container')) {
                    // We only want to care about top-level containers
                    if (req.$target.parent().closest('.container').length === 0) {
                        this.icinga.history.pushCurrentState();
                    }
                } else {
                    // Request wasn't for a container, so it's usually the body
                    // or the full layout. Push request URL to history:
                    var url = typeof req.historyUrl !== 'undefined' ? req.historyUrl : req.url;
                    this.icinga.history.pushUrl(url);
                }
            }

            req.$target.data('lastUpdate', (new Date()).getTime());
            delete this.requests[req.$target.attr('id')];
            this.icinga.ui.fadeNotificationsAway();

            this.processRedirectHeader(req);

            if (typeof req.loadNext !== 'undefined' && req.loadNext.length) {
                if ($('#col2').length) {
                    var r = this.loadUrl(req.loadNext[0], $('#col2'));
                    r.addToHistory = req.addToHistory;
                    this.icinga.ui.layout2col();
                } else {
                  this.icinga.logger.error('Failed to load URL for #col2', req.loadNext);
                }
            }

            req.$target.trigger('rendered');

            this.icinga.ui.refreshDebug();
        },

        /**
         * Handle failed XHR response
         */
        onFailure: function (req, textStatus, errorThrown) {
            var url = req.url;

            /*
             * Test if a manual actions comes in and autorefresh is active: Stop refreshing
             */
            if (req.addToHistory && ! req.autorefresh) {
                req.$target.data('icingaRefresh', 0);
                req.$target.data('icingaUrl', url);
                icinga.history.pushCurrentState();
            }

            if (typeof req.progressTimer !== 'undefined') {
                this.icinga.timer.unregister(req.progressTimer);
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
                        var now = new Date();
                        var padString = this.icinga.utils.padString;
                        this.failureNotice = this.createNotice(
                            'error',
                            'The connection to the Icinga web server was lost at '
                            + now.getFullYear()
                            + '-' + padString(now.getMonth() + 1, 0, 2)
                            + '-' + padString(now.getDate(), 0, 2)
                            + ' ' + padString(now.getHours(), 0, 2)
                            + ':' + padString(now.getMinutes(), 0, 2)
                            + '.',
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
                '<li class="' + c + '">' + this.icinga.utils.escape(message) + '</li>'
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
        renderContentToContainer: function (content, $container, action, autorefresh, forceFocus) {
            // Container update happens here
            var scrollPos = false;
            var _this = this;
            var containerId = $container.attr('id');

            var activeElementPath = false;
            var focusFallback = false;

            if (forceFocus && forceFocus.length) {
                activeElementPath = this.icinga.utils.getCSSPath($(forceFocus));
            } else if (document.activeElement && document.activeElement.id === 'search') {
                activeElementPath = '#search';
            } else if (document.activeElement
                && document.activeElement !== document.body
                && $.contains($container[0], document.activeElement)
            ) {
                // Active element in container
                var $activeElement = $(document.activeElement);
                var $pagination = $activeElement.closest('.pagination-control');
                if ($pagination.length) {
                    focusFallback = {
                        'parent': this.icinga.utils.getCSSPath($pagination),
                        'child': '.active > a'
                    };
                }
                activeElementPath = this.icinga.utils.getCSSPath($activeElement);
            }

            if (typeof containerId !== 'undefined') {
                if (autorefresh) {
                    scrollPos = $container.scrollTop();
                } else {
                    scrollPos = 0;
                }
            }

            $container.trigger('beforerender');

            var discard = false;
            $.each(_this.icinga.behaviors, function(name, behavior) {
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
                _this.stopPendingRequestsFor($(this));
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

            if (! activeElementPath) {
                // Active element was not in this container
                if (! autorefresh) {
                    setTimeout(function() {
                        if (typeof $container.attr('tabindex') === 'undefined') {
                            $container.attr('tabindex', -1);
                        }
                        // Do not touch focus in case a module or component already placed it
                        if ($(document.activeElement).closest('.container').attr('id') !== containerId) {
                            $container.focus();
                        }
                    }, 0);
                }
            } else {
                setTimeout(function() {
                    var $activeElement = $(activeElementPath);

                    if ($activeElement.length && $activeElement.is(':visible')) {
                        $activeElement.focus();
                        if ($activeElement.is('input[type=text]')) {
                            if (typeof $activeElement[0].setSelectionRange === 'function') {
                                // Place focus after the last character. Could be extended to other
                                // input types, would require some \r\n "magic" to work around issues
                                // with some browsers
                                var len = $activeElement.val().length;
                                $activeElement[0].setSelectionRange(len, len);
                            }
                        }
                    } else if (! autorefresh) {
                        if (focusFallback) {
                            $(focusFallback.parent).find(focusFallback.child).focus();
                        } else if (typeof $container.attr('tabindex') === 'undefined') {
                            $container.attr('tabindex', -1);
                        }
                        $container.focus();
                    }
                }, 0);
            }

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
