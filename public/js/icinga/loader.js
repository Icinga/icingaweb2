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

            // Overwrite the URL only if the form is not auto submitted
            if (! $autoSubmittedBy && $button.hasAttr('formaction')) {
                url = $button.attr('formaction');
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
            if (! $autoSubmittedBy
                && ! $form.is('[role="search"]')
                && $target.attr('id') === $form.closest('.container').attr('id')
            ) {
                $form.find('input[type=submit],button[type=submit],button:not([type])').prop('disabled', true);
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

            let extraHeaders = {};
            if ($autoSubmittedBy) {
                let id;
                if (($autoSubmittedBy.attr('name') || $autoSubmittedBy.attr('id'))) {
                    id = $autoSubmittedBy.attr('name') || $autoSubmittedBy.attr('id');
                } else {
                    let formSelector = icinga.utils.getCSSPath($form);
                    let nearestKnownParent = $autoSubmittedBy.closest(
                        formSelector + ' [name],' + formSelector + ' [id]'
                    );
                    if (nearestKnownParent) {
                        id = nearestKnownParent.attr('name') || nearestKnownParent.attr('id');
                    }
                }

                if (id) {
                    extraHeaders['X-Icinga-AutoSubmittedBy'] = id;
                }
            }

            var req = this.loadUrl(url, $target, data, method, undefined, undefined, undefined, extraHeaders);
            req.forceFocus = $autoSubmittedBy ? $autoSubmittedBy : $button.length ? $button : null;
            req.autosubmit = !! $autoSubmittedBy;
            req.addToHistory = method === 'GET';
            req.progressTimer = progressTimer;

            if ($autoSubmittedBy) {
                if ($autoSubmittedBy.closest('.controls').length) {
                    $('.content', req.$target).addClass('impact');
                } else {
                    req.$target.addClass('impact');
                }
            }

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
         * @param {object}  extraHeaders    Extra header entries
         */
        loadUrl: function (url, $target, data, method, action, autorefresh, progressTimer, extraHeaders) {
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
                // ... ignore the new request if it is already pending with the same URL. Only abort GETs, as those
                // are the only methods that are guaranteed to return the same value
                if (this.requests[id].url === url && method === 'GET') {
                    if (autorefresh) {
                        return false;
                    }

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

            if (!! id) {
                headers['X-Icinga-Container'] = id;
            }

            if (autorefresh) {
                headers['X-Icinga-Autorefresh'] = '1';
            }

            if ($target.is('#col2')) {
                headers['X-Icinga-Col1-State'] = this.icinga.history.getCol1State();
                headers['X-Icinga-Col2-State'] = this.icinga.history.getCol2State().replace(/^#!/, '');
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

            if (typeof extraHeaders !== 'undefined') {
                headers = $.extend(headers, extraHeaders);
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
            req.autosubmit = false;
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
                    if (! req.autosubmit && req.state() === 'pending') {
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
            return $(this).data('icingaRefresh') > 0 && ! $(this).is('[data-suspend-autorefresh]');
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
                $redirectTarget  = req.$redirectTarget,
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

                $(window).on('popstate.__back__', { self: this }, function (event) {
                    const _this = event.data.self;
                    let $refreshTarget = $('#col2');
                    let refreshUrl;

                    const hash = icinga.history.getCol2State();
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

                    const refreshReq = _this.loadUrl(refreshUrl, $refreshTarget);
                    refreshReq.autoRefreshInterval = req.getResponseHeader('X-Icinga-Refresh');
                    refreshReq.autorefresh = true;
                    refreshReq.scripted = true;

                    $(window).off('popstate.__back__');
                });

                // Navigate back, no redirect desired
                window.history.back();

                return true;
            } else if (redirect.match(/__CLOSE__/)) {
                if (req.$target.is('#col1') && req.$redirectTarget.is('#col1')) {
                    icinga.logger.warn('Cannot close #col1');
                    return false;
                }

                if (req.$redirectTarget.is('.container') && ! req.$redirectTarget.is('#main > :scope')) {
                    // If it is a container that is not a top level container, we just empty it
                    req.$redirectTarget.empty();
                    return true;
                }

                if (! req.$redirectTarget.is('#col2')) {
                    icinga.logger.debug('Cannot close container', req.$redirectTarget);
                    return false;
                }

                // Close right column as requested
                icinga.ui.layout1col();

                if (!! req.getResponseHeader('X-Icinga-Extra-Updates')) {
                    icinga.logger.debug('Not refreshing #col1 due to outstanding extra updates');
                    return true;
                }

                $redirectTarget = $('#col1');
                redirect = icinga.history.getCol1State();
            } else if (redirect.match(/__REFRESH__/)) {
                if (req.$redirectTarget.is('#col1')) {
                    redirect = icinga.history.getCol1State();
                } else if (req.$redirectTarget.is('#col2')) {
                    redirect = icinga.history.getCol2State().replace(/^#!/, '');
                } else {
                    icinga.logger.error('Unable to refresh. Not a primary column: ', req.$redirectTarget);
                    return false;
                }
            }

            var useHttp = req.getResponseHeader('X-Icinga-Redirect-Http');
            if (useHttp === 'yes') {
                window.location.replace(redirect);
                return true;
            }

            this.redirectToUrl(redirect, $redirectTarget, req);
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
                autoRefreshInterval = referrer.getResponseHeader('X-Icinga-Refresh');
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
                r.referrer = referrer;
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

            var target = req.getResponseHeader('X-Icinga-Container');
            var newBody = false;
            var oldNotifications = false;
            var isRedirect = !! req.getResponseHeader('X-Icinga-Redirect');
            if (target) {
                if (target === 'ignore') {
                    return;
                }

                var $newTarget = this.identifyLinkTarget(target, req.$target);
                if ($newTarget.length) {
                    if (isRedirect) {
                        req.$redirectTarget = $newTarget;
                    } else {
                        // If we change the target, oncomplete will fail to clean up.
                        // This fixes the problem, not using req.$target would be better
                        delete this.requests[req.$target.attr('id')];

                        req.$target = $newTarget;
                    }

                    if (target === 'layout') {
                        oldNotifications = $('#notifications li').detach();
                        this.icinga.ui.layout1col();
                        newBody = true;
                    } else if ($newTarget.attr('id') === 'col2') {
                        if (_this.icinga.ui.isOneColLayout()) {
                            _this.icinga.ui.layout2col();
                        } else if (target === '_next') {
                            _this.icinga.ui.moveToLeft();
                        }
                    }
                }
            }

            if (req.autorefresh && req.$target.is('[data-suspend-autorefresh]')) {
                return;
            }

            this.icinga.logger.debug(
                'Got response for ', req.$target, ', URL was ' + req.url
            );
            this.processNotificationHeader(req);

            var cssreload = req.getResponseHeader('X-Icinga-Reload-Css');
            if (cssreload) {
                this.icinga.ui.reloadCss();
            }

            if (isRedirect) {
                return;
            }

            if (req.getResponseHeader('X-Icinga-Announcements') === 'refresh') {
                var announceReq = _this.loadUrl(_this.url('/layout/announcements'), $('#announcements'));
                announceReq.addToHistory = false;
                announceReq.scripted = true;
            }

            var classes;

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
                    _this.icinga.ensureModule(moduleName);

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

            var autoSubmit = false;
            var currentUrl = this.icinga.utils.parseUrl(req.$target.data('icingaUrl'));
            if (req.method === 'POST') {
                var newUrl = this.icinga.utils.parseUrl(req.url);
                if (newUrl.path === currentUrl.path && this.icinga.utils.arraysEqual(newUrl.params, currentUrl.params)) {
                    autoSubmit = true;
                }
            }

            req.$target.data('icingaUrl', req.url);

            if (typeof req.progressTimer !== 'undefined') {
                this.icinga.timer.unregister(req.progressTimer);
            }

            var contentSeparator = req.getResponseHeader('X-Icinga-Multipart-Content');
            if (!! contentSeparator) {
                var locationQuery = req.getResponseHeader('X-Icinga-Location-Query');
                if (locationQuery !== null) {
                    let url = currentUrl.path + (locationQuery ? '?' + locationQuery : '');
                    if (req.autosubmit || autoSubmit) {
                        // Also update a form's action if it doesn't differ from the container's url
                        var $form = $(req.forceFocus).closest('form');
                        var formAction = $form.attr('action');
                        if (!! formAction) {
                            formAction = this.icinga.utils.parseUrl(formAction);
                            if (formAction.path === currentUrl.path
                                && this.icinga.utils.arraysEqual(formAction.params, currentUrl.params)
                            ) {
                                $form.attr('action', url);
                            }
                        }
                    }

                    req.$target.data('icingaUrl', url);
                    this.icinga.history.replaceCurrentState();
                }

                $.each(req.responseText.split(contentSeparator), function (idx, el) {
                    var match = el.match(/for=(Behavior:)?(\S+)\s+([^]*)/m);
                    if (!! match) {
                        if (match[1]) {
                            var behavior = _this.icinga.behaviors[match[2].toLowerCase()];
                            if (typeof behavior !== 'undefined' && typeof behavior.update === 'function') {
                                behavior.update(JSON.parse(match[3]));
                            } else {
                                _this.icinga.logger.warn(
                                    'Invalid behavior. Cannot update behavior "' + match[2] + '"');
                            }
                        } else {
                            var $target = $('#' + match[2]);
                            if ($target.length) {
                                var forceFocus;
                                if (req.forceFocus
                                    && typeof req.forceFocus.jquery !== 'undefined'
                                    && $.contains($target[0], req.forceFocus[0])
                                ) {
                                    forceFocus = req.forceFocus;
                                }

                                _this.renderContentToContainer(
                                    match[3],
                                    $target,
                                    'replace',
                                    req.autorefresh,
                                    forceFocus,
                                    req.autosubmit || autoSubmit,
                                    req.scripted
                                );
                            } else {
                                _this.icinga.logger.warn(
                                    'Invalid target ID. Cannot render multipart to #' + match[2]);
                            }
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
                    req.autosubmit || autoSubmit,
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

            if (req.getResponseHeader('X-Icinga-Reload-Window') === 'yes') {
                window.location.reload();
                return;
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
                        $target = $(parts[0].startsWith('#') ? parts[0] : '#' + parts[0]);
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
                        if (! url) {
                            _this.icinga.logger.debug(
                                'Superfluous extra update. The target\'s container has no url', el);
                            return;
                        }
                    } else {
                        _this.icinga.logger.error('Invalid extra update', el);
                        return;
                    }

                    if (url === '__CLOSE__') {
                        if ($target.is('#col2')) {
                            _this.icinga.ui.layout1col();
                        } else if ($target.is('#main > :scope')) {
                            _this.icinga.logger.warn('Invalid target ID. Cannot close ', $target);
                        } else if ($target.is('.container')) {
                            // If it is a container that is not a top level container, we just empty it
                            $target.empty();
                        }
                    } else {
                        _this.loadUrl(url, $target).addToHistory = false;
                    }
                });
            }

            if ((textStatus === 'abort' && typeof req.referrer !== 'undefined') || this.processRedirectHeader(req)) {
                return;
            }

            // Remove 'impact' class if there was such
            if (req.$target.hasClass('impact')) {
                req.$target.removeClass('impact');
            } else {
                var $impact = req.$target.find('.impact').first();
                if ($impact.length) {
                    $impact.removeClass('impact');
                }
            }

            if (! req.autorefresh && ! req.autosubmit) {
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

            if (typeof req.loadNext !== 'undefined' && req.loadNext.length) {
                if ($('#col2').length) {
                    var r = this.loadUrl(req.loadNext[0], $('#col2'));
                    r.addToHistory = req.addToHistory;
                    this.icinga.ui.layout2col();
                } else {
                  this.icinga.logger.error('Failed to load URL for #col2', req.loadNext);
                }
            }

            // Lazy load module javascript (Applies only to module.js code)
            this.icinga.ensureSubModules(req.$target);

            req.$target.find('.container').each(function () {
                $(this).trigger('rendered', [req.autorefresh, req.scripted, req.autosubmit]);
            });
            req.$target.trigger('rendered', [req.autorefresh, req.scripted, req.autosubmit]);

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
                    undefined,
                    req.autosubmit,
                    req.scripted
                );
            } else {
                if (errorThrown === 'abort') {
                    this.icinga.logger.debug(
                        'Request to ' + url + ' has been aborted for ',
                        req.$target
                    );

                    if (req.scripted) {
                        req.addToHistory = false;
                    }
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
            var c = severity,
                icon;
            if (persist) {
                c += ' persist';
            }

            switch (severity) {
                case 'success':
                    icon = 'check-circle';
                    break;
                case 'error':
                    icon = 'times';
                    break;
                case 'warning':
                    icon = 'exclamation-triangle';
                    break;
                case 'info':
                    icon = 'info-circle';
                    break;
            }

            var $notice = $(
                '<li class="' + c + '">' +
                '<i class="icon fa fa-' + icon + '"></i>' +
                this.icinga.utils.escape(message) + '</li>'
            ).appendTo($('#notifications'));

            if (!persist) {
                this.icinga.ui.fadeNotificationsAway();
            }

            return $notice;
        },

        /**
         * Detect the link/form target for a given element (link, form, whatever)
         *
         * @param {object} $el jQuery set with the element
         * @param {boolean} prepare Pass `false` to disable column preparation
         */
        getLinkTargetFor: function($el, prepare)
        {
            if (typeof prepare === 'undefined') {
                prepare = true;
            }

            // If everything else fails, our target is the first column...
            var $target = $('#col1');

            // ...but usually we will use our own container...
            var $container = $el.closest('.container');
            if ($container.length) {
                $target = $container;
            }

            // You can of course override the default behaviour:
            if ($el.closest('[data-base-target]').length) {
                var targetId = $el.closest('[data-base-target]').data('baseTarget');

                $target = this.identifyLinkTarget(targetId, $el);
                if (! $target.length) {
                    this.icinga.logger.warn('Link target "#' + targetId + '" does not exist in DOM.');
                }
            }

            if (prepare) {
                this.icinga.ui.prepareColumnFor($el, $target);
            }

            return $target;
        },

        /**
         * Identify link target by the given id
         *
         * The id may also be one of the column aliases: `_next`, `_self` and `_main`
         *
         * @param {string} id
         * @param {object} $of
         * @return {object}
         */
        identifyLinkTarget: function (id, $of) {
            var $target;

            if (id === '_next') {
                if (this.icinga.ui.hasOnlyOneColumn()) {
                    $target = $('#col1');
                } else {
                    $target = $('#col2');
                }
            } else if (id === '_self') {
                $target = $of.closest('.container');
            } else if (id === '_main') {
                $target = $('#col1');
            } else {
                $target = $('#' + id);
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
                        scrollPos = {
                            x: $scrollableContent.scrollTop(),
                            y: $scrollableContent.scrollLeft()
                        };
                        scrollTarget = _this.icinga.utils.getCSSPath($scrollableContent);
                    } else {
                        scrollPos = {
                            x: $container.scrollTop(),
                            y: $container.scrollLeft()
                        };
                    }
                } else {
                    scrollPos = {
                        x: 0,
                        y: 0
                    }
                }
            }

            $container.trigger('beforerender', [content, action, autorefresh, scripted, autoSubmit]);

            let discard = false;
            for (const hook of _this.icinga.renderHooks) {
                const changed = hook.renderHook(content, $container, action, autorefresh, autoSubmit);
                if (changed === null) {
                    discard = true;
                    break;
                } else {
                    content = changed;
                }
            }

            $('.container', $container).each(function() {
                _this.stopPendingRequestsFor($(this));
            });

            if (! discard) {
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

            if (! discard && navigationAnchor) {
                var $element = $container.find('#' + navigationAnchor);
                if ($element.length) {
                    // data-icinga-no-scroll-on-focus is NOT designed to avoid scrolling for non-XHR requests
                    setTimeout(this.icinga.ui.focusElement.bind(this.icinga.ui), 0,
                        $element, $container, ! $element.is('[data-icinga-no-scroll-on-focus]'));
                }
            } else if (! activeElementPath) {
                // Active element was not in this container
                if (! autorefresh && ! autoSubmit && ! scripted) {
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
                        $activeElement[0].focus({preventScroll: autorefresh || autoSubmit});
                    } else if (! autorefresh && ! autoSubmit && ! scripted) {
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

                // Fallback for browsers without support for focus({preventScroll: true})
                requestAnimationFrame(() => {
                    if ($scrollTarget.scrollTop() !== scrollPos.x) {
                        $scrollTarget.scrollTop(scrollPos.x);
                    }
                    if ($scrollTarget.scrollLeft() !== scrollPos.y) {
                        $scrollTarget.scrollLeft(scrollPos.y);
                    }
                });
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
