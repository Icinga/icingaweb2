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

        /**
         * Whether auto-refresh is enabled
         */
        this.autorefreshEnabled = true;

        /**
         * Whether auto-refresh is suspended due to visibility of page
         */
        this.autorefreshSuspended = false;
    };

    Icinga.Loader.prototype = {

        initialize: function () {
            this.icinga.timer.register(this.autorefresh, this, 500);
        },

        submitForm: function ($form, $autoSubmittedBy, $button) {
            var icinga = this.icinga;
            var url = $form.attr('action');
            var method = $form.attr('method');
            var encoding = $form.attr('enctype');
            var progressTimer;
            var $target;
            var data;

            if (typeof method === 'undefined') {
                method = 'POST';
            } else {
                method = method.toUpperCase();
            }

            if (typeof encoding === 'undefined') {
                encoding = 'application/x-www-form-urlencoded';
            }

            if (typeof $autoSubmittedBy === 'undefined') {
                $autoSubmittedBy = false;
            }

            if (typeof $button === 'undefined') {
                $button = $('input[type=submit]:focus', $form).add('button[type=submit]:focus', $form);
            }

            if ($button.length === 0) {
                $button = $('input[type=submit]', $form).add('button[type=submit]', $form).first();
            }

            if ($button.length) {
                // Activate spinner
                if ($button.hasClass('spinner')) {
                    $button.addClass('active');
                }

                $target = this.getLinkTargetFor($button);
            } else {
                $target = this.getLinkTargetFor($form);
            }

            if (! url) {
                // Use the URL of the target container if the form's action is not set
                url = $target.closest('.container').data('icinga-url');
            }

            icinga.logger.debug('Submitting form: ' + method + ' ' + url, method);

            if (method === 'GET') {
                var dataObj = $form.serializeObject();

                if (! $autoSubmittedBy) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        dataObj[$button.attr('name')] = $button.attr('value');
                    }
                }

                url = icinga.utils.addUrlParams(url, dataObj);
            } else {
                if (encoding === 'multipart/form-data') {
                    data = new window.FormData($form[0]);
                } else {
                    data = $form.serializeArray();
                }

                if (! $autoSubmittedBy) {
                    if ($button.length && $button.attr('name') !== 'undefined') {
                        if (encoding === 'multipart/form-data') {
                            data.append($button.attr('name'), $button.attr('value'));
                        } else {
                            data.push({
                                name: $button.attr('name'),
                                value: $button.attr('value')
                            });
                        }
                    }
                }
            }

            // Disable all form controls to prevent resubmission except for our search input
            // Note that disabled form inputs will not be enabled via JavaScript again
            if ($target.attr('id') === $form.closest('.container').attr('id')) {
                $form.find(':input:not(#search):not(:disabled)').prop('disabled', true);
            }

            // Show a spinner depending on how the form is being submitted
            if ($autoSubmittedBy && $autoSubmittedBy.siblings('.spinner').length) {
                $autoSubmittedBy.siblings('.spinner').first().addClass('active');
            } else if ($button.length && $button.is('button') && $button.hasClass('animated')) {
                $button.addClass('active');
            } else if ($button.length && $button.attr('data-progress-label')) {
                var isInput = $button.is('input');
                if (isInput) {
                    $button.prop('value', $button.attr('data-progress-label') + '...');
                } else {
                    $button.html($button.attr('data-progress-label') + '...');
                }

                // Use a fixed width to prevent the button from wobbling
                $button.css('width', $button.css('width'));

                progressTimer = icinga.timer.register(function () {
                    var label = isInput ? $button.prop('value') : $button.html();
                    var dots = label.substr(-3);

                    // Using empty spaces here to prevent centered labels from wobbling
                    if (dots === '...') {
                        label = label.slice(0, -2) + '  ';
                    } else if (dots === '.. ') {
                        label = label.slice(0, -1) + '.';
                    } else if (dots === '.  ') {
                        label = label.slice(0, -2) + '. ';
                    }

                    if (isInput) {
                        $button.prop('value', label);
                    } else {
                        $button.html(label);
                    }
                }, null, 100);
            } else if ($button.length && $button.next().hasClass('spinner')) {
                $('i', $button.next()).addClass('active');
            } else if ($form.attr('data-progress-element')) {
                var $progressElement = $('#' + $form.attr('data-progress-element'));
                if ($progressElement.length) {
                    if ($progressElement.hasClass('spinner')) {
                        $('i', $progressElement).addClass('active');
                    } else {
                        $('i.spinner', $progressElement).addClass('active');
                    }
                }
            }

            var req = this.loadUrl(url, $target, data, method);
            req.forceFocus = $autoSubmittedBy ? $autoSubmittedBy : $button.length ? $button : null;
            req.progressTimer = progressTimer;
            return req;
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

            if (autorefresh) {
                headers['X-Icinga-Autorefresh'] = '1';
            }

            // Ask for a new window id in case we don't already have one
            if (this.icinga.ui.hasWindowId()) {
                var windowId = this.icinga.ui.getWindowId();
                var containerId = this.icinga.ui.getUniqueContainerId($target);
                if (containerId) {
                    windowId = windowId + '_' + containerId;
                }
                headers['X-Icinga-WindowId'] = windowId;
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
            req.$redirectTarget = $target;
            req.url = url;
            req.done(this.onResponse);
            req.fail(this.onFailure);
            req.always(this.onComplete);
            req.autorefresh = autorefresh;
            req.scripted = false;
            req.method = method;
            req.action = action;
            req.addToHistory = true;
            req.progressTimer = progressTimer;

            if (url.match(/#/)) {
                req.forceFocus = url.split(/#/)[1];
            }

            if (id) {
                this.requests[id] = req;
            }
            if (! autorefresh) {
                setTimeout(function () {
                    // The column may have not been shown before. To make the transition
                    // delay working we have to wait for the column getting rendered
                    if (req.state() === 'pending') {
                        req.$target.addClass('impact');
                    }
                }, 0);
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
            var _this = this;

            $('.container').filter(this.filterAutorefreshingContainers).each(function (idx, el) {
                var $el = $(el);
                var id = $el.attr('id');

                // Always request application-state
                if (id !== 'application-state' && (! _this.autorefreshEnabled || _this.autorefreshSuspended)) {
                    // Continue
                    return true;
                }

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

        /**
         * Add the specified flag to the given URL
         *
         * @param {string} url
         * @param {string} flag
         *
         * @returns {string}
         *
         * @deprecated since version 2.8.0. Use {@link Icinga.Utils.addUrlFlag()} instead
         */
        addUrlFlag: function(url, flag)
        {
            return this.icinga.utils.addUrlFlag(url, flag);
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
            } else if (redirect.match(/__BACK__/)) {
                if (req.$redirectTarget.is('#col1')) {
                    icinga.logger.warn('Cannot navigate back. Redirect target is #col1');
                    return false;
                }

                var originUrl = req.$target.data('icingaUrl');

                // We may just close the right column, refresh the left one in this case
                $(window).on('popstate.__back__', { self: this }, function (event) {
                    var _this = event.data.self;
                    var $refreshTarget = $('#col2');
                    var refreshUrl;

                    var hash = icinga.history.getCol2State();
                    if (hash && hash.match(/^#!/)) {
                        // TODO: These three lines are copied from history.js, I don't like this
                        var parts = hash.split(/#!/);

                        if (parts[1] === originUrl) {
                            // After a page load a call to back() seems not to have an effect
                            icinga.ui.layout1col();
                        } else {
                            refreshUrl = parts[1];
                        }
                    }

                    if (typeof refreshUrl === 'undefined' && icinga.ui.isOneColLayout()) {
                        refreshUrl = icinga.history.getCol1State();
                        $refreshTarget = $('#col1');
                    }

                    _this.loadUrl(refreshUrl, $refreshTarget).autorefresh = true;

                    setTimeout(function () {
                        // TODO: Find a better solution than a hardcoded one
                        _this.loadUrl(refreshUrl, $refreshTarget).autorefresh = true;
                    }, 1000);

                    $(window).off('popstate.__back__');
                });

                // Navigate back, no redirect desired
                window.history.back();

                return true;
            }

            var useHttp = req.getResponseHeader('X-Icinga-Redirect-Http');
            if (useHttp === 'yes') {
                window.location.replace(redirect);
                return true;
            }

            this.redirectToUrl(redirect, req.$redirectTarget, req);
            return true;
        },

        /**
         * Redirect to the given url
         *
         * @param {string}  url
         * @param {object}  $target
         * @param {XMLHttpRequest} referrer
         */
        redirectToUrl: function (url, $target, referrer) {
            var icinga = this.icinga,
                rerenderLayout,
                autoRefreshInterval,
                forceFocus,
                origin;

            if (typeof referrer !== 'undefined') {
                rerenderLayout = referrer.getResponseHeader('X-Icinga-Rerender-Layout');
                autoRefreshInterval = referrer.autoRefreshInterval;
                forceFocus = referrer.forceFocus;
                origin = referrer.url;
            }

            icinga.logger.debug(
                'Got redirect for ', $target, ', URL was ' + url
            );

            if (rerenderLayout) {
                var parts = url.split(/#!/);
                url = parts.shift();
                var redirectionUrl = icinga.utils.addUrlFlag(url, 'renderLayout');
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
                    req.referrer = referrer;
                }
            }
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

            this.icinga.logger.debug(
                'Got response for ', req.$target, ', URL was ' + req.url
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
                    // Lazy load module javascript (Applies only to module.js code)
                    if (_this.icinga.hasModule(moduleName) && ! _this.icinga.isLoadedModule(moduleName)) {
                        _this.icinga.loadModule(moduleName);
                    }

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
            if (title && (target === 'layout' || req.$target.is('#layout'))) {
                this.icinga.ui.setTitle(decodeURIComponent(title));
            } else if (title && ! req.autorefresh && req.$target.closest('.dashboard').length === 0) {
                req.$target.data('icingaTitle', decodeURIComponent(title));
            }

            // Set a window identifier if the server asks us to do so
            var windowId = req.getResponseHeader('X-Icinga-WindowId');
            if (windowId) {
                this.icinga.ui.setWindowId(windowId);
            }

            // Handle search requests, still hardcoded.
            if (req.url.match(/^\/search/) && req.$target.data('icingaUrl').match(/^\/search/)) {
                var $resp = $('<div>' + req.responseText + '</div>'); // div helps getting an XML tree
                if ($('.dashboard', $resp).length > 0 && $('.dashboard .container', req.$target).length > 0) {
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
            }

            var referrer = req.referrer;
            if (typeof referrer === 'undefined') {
                referrer = req;
            }

            var autoSubmit = false;
            if (referrer.method === 'POST') {
                var newUrl = this.icinga.utils.parseUrl(req.url);
                var currentUrl = this.icinga.utils.parseUrl(req.$target.data('icingaUrl'));
                if (newUrl.path === currentUrl.path && this.icinga.utils.arraysEqual(newUrl.params, currentUrl.params)) {
                    autoSubmit = true;
                }
            }

            req.$target.data('icingaUrl', req.url);

            if (rendered) {
                return;
            }

            if (typeof req.progressTimer !== 'undefined') {
                this.icinga.timer.unregister(req.progressTimer);
            }

            var contentSeparator = req.getResponseHeader('X-Icinga-Multipart-Content');
            if (!! contentSeparator) {
                $.each(req.responseText.split(contentSeparator), function (idx, el) {
                    var match = el.match(/for=(\S+)\s+(.*)/m);
                    if (!! match) {
                        var $target = $('#' + match[1]);
                        if ($target.length) {
                            _this.renderContentToContainer(
                                match[2],
                                $target,
                                'replace',
                                req.autorefresh,
                                req.forceFocus,
                                autoSubmit,
                                req.scripted
                            );
                        } else {
                            _this.icinga.logger.warn(
                                'Invalid target ID. Cannot render multipart to #' + match[1]);
                        }
                    } else {
                        _this.icinga.logger.error('Ill-formed multipart', el);
                    }
                })
            } else {
                this.renderContentToContainer(
                    req.responseText,
                    req.$target,
                    req.action,
                    req.autorefresh,
                    req.forceFocus,
                    autoSubmit,
                    req.scripted
                );
            }

            if (oldNotifications) {
                oldNotifications.appendTo($('#notifications'));
            }
            if (newBody) {
                this.icinga.ui.fixDebugVisibility().triggerWindowResize();
            }
        },

        /**
         * Regardless of whether a request succeeded of failed, clean up
         */
        onComplete: function (dataOrReq, textStatus, reqOrError) {
            var _this = this;
            var req;

            if (typeof dataOrReq === 'object') {
                req = dataOrReq;
            } else {
                req = reqOrError;
            }

            // Remove 'impact' class if there was such
            if (req.$target.hasClass('impact')) {
                req.$target.removeClass('impact');
            }

            if (! req.autorefresh) {
                // TODO: Hook for response/url?
                var url = req.url;

                if (req.$target[0].id === 'col1') {
                    this.icinga.behaviors.navigation.trySetActiveAndSelectedByUrl(url);
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

            var extraUpdates = req.getResponseHeader('X-Icinga-Extra-Updates');
            if (!! extraUpdates && req.getResponseHeader('X-Icinga-Redirect-Http') !== 'yes') {
                $.each(extraUpdates.split(','), function (idx, el) {
                    var parts = el.trim().split(';');
                    var $target;
                    var url;
                    if (parts.length === 2) {
                        $target = $('#' + parts[0]);
                        if (! $target.length) {
                            _this.icinga.logger.warn('Invalid target ID. Cannot load extra URL', el);
                            return;
                        }

                        url = parts[1];
                    } else if (parts.length === 1) {
                        $target = $(parts[0]).closest(".container").not(req.$target);
                        if (! $target.length) {
                            _this.icinga.logger.warn('Invalid target ID. Cannot load extra URL', el);
                            return;
                        }

                        url = $target.data('icingaUrl');
                    } else {
                        _this.icinga.logger.error('Invalid extra update', el);
                        return;
                    }

                    _this.loadUrl(url, $target).addToHistory = false;
                });
            }

            if (this.processRedirectHeader(req)) {
                return;
            }

            if (typeof req.loadNext !== 'undefined' && req.loadNext.length) {
                if ($('#col2').length) {
                    var r = this.loadUrl(req.loadNext[0], $('#col2'));
                    r.addToHistory = req.addToHistory;
                    this.icinga.ui.layout2col();
                } else {
                  this.icinga.logger.error('Failed to load URL for #col2', req.loadNext);
                }
            }

            req.$target.find('.container').each(function () {
                // Lazy load module javascript (Applies only to module.js code)
                var moduleName = $(this).data('icingaModule');
                if (_this.icinga.hasModule(moduleName) && ! _this.icinga.isLoadedModule(moduleName)) {
                    _this.icinga.loadModule(moduleName);
                }

                $(this).trigger('rendered');
            });
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

            if (req.status > 0 && req.status < 501) {
                this.icinga.logger.error(
                    req.status,
                    errorThrown + ':',
                    $(req.responseText).text().replace(/\s+/g, ' ').slice(0, 100)
                );
                this.renderContentToContainer(
                    req.responseText,
                    req.$target,
                    req.action,
                    req.autorefresh,
                    req.scripted
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

            if (!persist) {
                this.icinga.ui.fadeNotificationsAway();
            }

            return $notice;
        },

        /**
         * Detect the link/form target for a given element (link, form, whatever)
         */
        getLinkTargetFor: function($el)
        {
            // If everything else fails, our target is the first column...
            var $col1 = $('#col1');
            var $target = $col1;

            // ...but usually we will use our own container...
            var $container = $el.closest('.container');
            if ($container.length) {
                $target = $container;
            }

            // You can of course override the default behaviour:
            if ($el.closest('[data-base-target]').length) {
                var targetId = $el.closest('[data-base-target]').data('baseTarget');

                // Simulate _next to prepare migration to dynamic column layout
                // YES, there are duplicate lines right now.
                if (targetId === '_next') {
                    if (this.icinga.ui.hasOnlyOneColumn()) {
                        $target = $col1;
                    } else {
                        if ($el.closest('#col2').length) {
                            this.icinga.ui.moveToLeft();
                        }

                        $target = $('#col2');
                    }
                } else if (targetId === '_self') {
                    $target = $el.closest('.container');
                } else if (targetId === '_main') {
                    $target = $col1;
                    this.icinga.ui.layout1col();
                } else {
                    $target = $('#' + targetId);
                    if (! $target.length) {
                        this.icinga.logger.warn('Link target "#' + targetId + '" does not exist in DOM.');
                    }
                }
            }

            // Hardcoded layout switch unless columns are dynamic
            if ($target.attr('id') === 'col2') {
                this.icinga.ui.layout2col();
            }

            return $target;
        },

        /**
         * Smoothly render given HTML to given container
         */
        renderContentToContainer: function (content, $container, action, autorefresh, forceFocus, autoSubmit, scripted) {
            // Container update happens here
            var scrollPos = false;
            var _this = this;
            var containerId = $container.attr('id');

            var activeElementPath = false;
            var navigationAnchor = false;
            var focusFallback = false;

            if (forceFocus && forceFocus.length) {
                if (typeof forceFocus === 'string') {
                    navigationAnchor = forceFocus;
                } else {
                    activeElementPath = this.icinga.utils.getCSSPath($(forceFocus));
                }
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

            var scrollTarget = $container;
            if (typeof containerId !== 'undefined') {
                if (autorefresh || autoSubmit) {
                    if ($container.css('display') === 'flex' && $container.is('.container')) {
                        var $scrollableContent = $container.children('.content');
                        scrollPos = $scrollableContent.scrollTop();
                        scrollTarget = _this.icinga.utils.getCSSPath($scrollableContent);
                    } else {
                        scrollPos = $container.scrollTop();
                    }
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

            if (navigationAnchor) {
                setTimeout(this.icinga.ui.focusElement.bind(this.icinga.ui), 0, navigationAnchor, $container);
            } else if (! activeElementPath) {
                // Active element was not in this container
                if (! autorefresh && ! scripted) {
                    setTimeout(function() {
                        if (typeof $container.attr('tabindex') === 'undefined') {
                            $container.attr('tabindex', -1);
                        }
                        // Do not touch focus in case a module or component already placed it
                        if ($(document.activeElement).closest('.container').attr('id') !== containerId) {
                            _this.icinga.ui.focusElement($container);
                        }
                    }, 0);
                }
            } else {
                setTimeout(function() {
                    var $activeElement = $(activeElementPath);

                    if ($activeElement.length && $activeElement.is(':visible')) {
                        $activeElement[0].focus({preventScroll: autorefresh});
                    } else if (! autorefresh && ! scripted) {
                        if (focusFallback) {
                            _this.icinga.ui.focusElement($(focusFallback.parent).find(focusFallback.child));
                        } else if (typeof $container.attr('tabindex') === 'undefined') {
                            $container.attr('tabindex', -1);
                        }
                        _this.icinga.ui.focusElement($container);
                    }
                }, 0);
            }

            if (scrollPos !== false) {
                var $scrollTarget = $(scrollTarget);
                $scrollTarget.scrollTop(scrollPos);

                // Fallback for browsers without support for focus({preventScroll: true})
                setTimeout(function () {
                    if ($scrollTarget.scrollTop() !== scrollPos) {
                        $scrollTarget.scrollTop(scrollPos);
                    }
                }, 0);
            }

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
