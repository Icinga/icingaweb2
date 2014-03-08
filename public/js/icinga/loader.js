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

        this.exception = null;

        /**
         * Pending requests
         */
        this.requests = {};

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
         */
        loadUrl: function (url, $target, data, method) {
            var id = null;

            // Default method is GET
            if ('undefined' === typeof method) {
                method = 'GET';
            }

            this.icinga.logger.debug('Loading ', url, ' to ', $target);

            // We should do better and ignore requests without target and/or id
            if (typeof $target !== 'undefined' && $target.attr('id')) {
                id = $target.attr('id');
            }

            if (typeof $target !== 'undefined') {
                // TODO: We shouldn't use data but keep this information somewhere else.
                if ($target.data('icingaUrl') !== url) {
                    $target.removeAttr('data-icinga-url');
                    $target.removeAttr('data-icinga-refresh');
                    $target.removeData('icingaUrl');
                    $target.removeData('icingaRefresh');
                }
            }

            // If we have a pending request for the same target...
            if (id in this.requests) {
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
            req.historyTriggered = false;
            req.autorefresh = false;
            if (id) {
                this.requests[id] = req;
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
            if (typeof $el !== 'undefined' || ! (id = $el.attr('id'))) {
                return;
            }

            if (id in this.requests) {
                this.requests[id].abort();
            }
        },

        autorefresh: function () {
            var self = this;
            if (self.autorefreshEnabled !== true) {
                return;
            }

            $('.container[data-icinga-refresh]').each(function (idx, el) {
                var $el = $(el);
                var id = $el.attr('id');
                if (id in self.requests) {
                    self.icinga.logger.debug('No refresh, request pending', id);
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

                self.icinga.logger.info(
                    'Autorefreshing ' + id + ' ' + interval + ' ms passed'
                );
                self.loadUrl($el.data('icingaUrl'), $el).autorefresh = true;
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

        /**
         * Handle successful XHR response
         */
        onResponse: function (data, textStatus, req) {
            var self = this;
            if (this.failureNotice !== null) {
                this.failureNotice.remove();
                this.failureNotice = null;
            }

            if (this.exception !== null) {
                this.exception.remove();
                this.exception = null;
                req.$target.removeClass('impact');
            }

            var url = req.url;
            this.icinga.logger.debug(
                'Got response for ', req.$target, ', URL was ' + url
            );

            var $resp = $(req.responseText);
            var active = false;

            if (! req.autorefresh) {
                // TODO: Hook for response/url?
                var $forms = $('[action="' + url + '"]');
                var $matches = $.merge($('[href="' + url + '"]'), $forms);
                $matches.each(function (idx, el) {
                    if ($(el).closest('#menu').length) {
                        $('#menu .active').removeClass('active');
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
                            $el.closest('li').addClass('active');
                            $el.parents('li').addClass('active');
                        }
                    } else if ($(el).closest('table.action').length) {
                        $el.addClass('active');
                    }
                });
            } else {
                // TODO: next container url
                active = $('[href].active', req.$target).attr('href');
            }

            var notifications = req.getResponseHeader('X-Icinga-Notification');
            if (notifications) {
                var parts = notifications.split(' ');
                this.createNotice(
                    parts.shift(),
                    parts.join(' ')
                );
            }

            //
            var target = req.getResponseHeader('X-Icinga-Container');
            var newBody = false;
            if (target) {
                if (target === 'ignore') {
                    return;
                }
                req.$target = $('#' + target);
                newBody = true;
            }

            var title = req.getResponseHeader('X-Icinga-Title');
            if (title && req.$target.closest('.dashboard').length === 0) {
                this.icinga.ui.setTitle(title);
            }

            var refresh = req.getResponseHeader('X-Icinga-Refresh');
            if (refresh) {
                // Hmmmm... .data() doesn't work here?
                req.$target.attr('data-icinga-refresh', refresh);
                req.$target.attr('data-last-update', (new Date()).getTime());
                req.$target.data('lastUpdate', (new Date()).getTime());
                req.$target.data('icingaRefresh', refresh);
            } else {
                req.$target.removeAttr('data-icinga-refresh');
                req.$target.removeAttr('data-last-update');
                req.$target.removeData('icingaRefresh');
                req.$target.removeData('lastUpdate');
            }

            // Set a window identifier if the server asks us to do so
            var windowId = req.getResponseHeader('X-Icinga-WindowId');
            if (windowId) {
                this.icinga.ui.setWindowId(windowId);
            }

            // Remove 'impact' class if there was such
            if (req.$target.hasClass('impact')) {
                req.$target.removeClass('impact');
            }

            // Handle search requests, still hardcoded
            if (req.url === '/search' &&
                req.$target.data('icingaUrl') === '/search')
            {
                // TODO: We need dashboard pane and container identifiers (not ids)
                var targets = [];
                $('.dashboard .container').each(function (idx, el) {
                    targets.push($(el));
                });

                var i = 0;
                $('.dashboard .container', $resp).each(function (idx, el) {
                    var $el = $(el);
                    var url = $el.data('icingaUrl');
                    targets[i].data('icingaUrl', url);

                    var title = $('h1', $el).first();
                    $('h1', targets[i]).first().replaceWith(title);

                    self.loadUrl(url, targets[i]);
                    i++;
                });
                return;
            }

            req.$target.attr('data-icinga-url', req.url);
            req.$target.data('icingaUrl', req.url);

            // Update history when necessary. Don't do so for requests triggered
            // by history or autorefresh events
            if (! req.historyTriggered && ! req.autorefresh) {

                // We only want to care about top-level containers
                if (req.$target.parent().closest('.container').length === 0) {
                    this.icinga.history.pushCurrentState();
                }
            }

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

            this.renderContentToContainer($resp, req.$target);
            if (newBody) {
                this.icinga.ui.fixDebugVisibility().triggerWindowResize();
            }

            if (active) {
                $('[href="' + active + '"]', req.$target).addClass('active');
            }
        },

        /**
         * Regardless of whether a request succeeded of failed, clean up
         */
        onComplete: function (req, textStatus) {
            delete this.requests[req.$target.attr('id')];
            this.icinga.ui.fadeNotificationsAway();
            this.icinga.ui.refreshDebug();
        },

        /**
         * Handle failed XHR response
         */
        onFailure: function (req, textStatus, errorThrown) {
            var url = req.url;

            if (req.status === 500) {
                if (this.exception === null) {
                    req.$target.addClass('impact');

                    this.exception = this.createNotice(
                        'error',
                        $('h1', $(req.responseText)).first().html(),
                        true
                    );
                    this.icinga.ui.fixControls();
                }
            } else if (req.status > 0) {
                this.icinga.logger.debug(req.responseText.slice(0, 100));
                this.renderContentToContainer(
                    '<h1>' + req.status + ' ' + errorThrown + '</h1> ' +
                        req.responseText,
                    req.$target
                );

                // Header example:
                // Icinga.debug(req.getResponseHeader('X-Icinga-Redirect'));
            } else {
                if (errorThrown === 'abort') {
                    this.icinga.logger.info(
                        'Request to ' + url + ' has been aborted for ',
                        req.$target
                    );
                } else {
                    if (this.failureNotice === null) {
                        this.failureNotice = this.createNotice(
                            'error',
                            'The connection to the Icinga web server has been lost at ' +
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
            return $notice;
        },

        /**
         * Smoothly render given HTML to given container
         */
        renderContentToContainer: function (content, $container) {
            // Disable all click events while rendering
            $('*').click(function (event) {
                event.stopImmediatePropagation();
                event.stopPropagation();
                event.preventDefault();
            });

            // Container update happens here
            var scrollPos = false;
            var containerId = $container.attr('id');
            if (typeof containerId !== 'undefined') {
                scrollPos = $container.scrollTop();
            }

            var origFocus = document.activeElement;
            var $content = $(content);
            if (false &&
                $('.dashboard', $content).length > 0 &&
                $('.dashboard', $container).length === 0
            ) {
                // $('.dashboard', $content)
                // $container.html(content);

            } else {
                if ($container.closest('.dashboard').length &&
                    ! $('h1', $content).length
                ) {
                    var title = $('h1', $container).first().detach();
                    $('h1', $content).first().detach();
                    $container.html(title).append(content);
                } else {
                    $container.html(content);
                }
            }
            if (scrollPos !== false) {
                $container.scrollTop(scrollPos);
            }
            if (origFocus) {
                origFocus.focus();
            }

            // TODO: this.icinga.events.refreshContainer(container);
            var icinga = this.icinga;
            icinga.events.applyHandlers($container);
            icinga.ui.initializeControls($container);
            icinga.ui.fixControls();

            // Re-enable all click events
            $('*').off('click');
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
