/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Icinga.History
 *
 * This is where we care about the browser History API
 *
 */
(function (Icinga, $) {

    'use strict';

    Icinga.History = function (icinga) {

        /**
         * YES, we need Icinga
         */
        this.icinga = icinga;

        /**
         * Our base url
         */
        this.baseUrl = icinga.config.baseUrl;

        /**
         * Initial URL at load time
         */
        this.initialUrl = location.href;

        /**
         * Whether the History API is enabled
         */
        this.enabled = false;

        /**
         * Workaround for Chrome onload popstate event
         */
        this.pushedSomething = false;
    };

    Icinga.History.prototype = {

        /**
         * Icinga will call our initialize() function once it's ready
         */
        initialize: function () {

            // History API will not be enabled without browser support, no fallback
            if ('undefined' !== typeof window.history  &&
                typeof window.history.pushState === 'function'
            ) {
                this.enabled = true;
                this.icinga.logger.debug('History API enabled');
                this.applyLocationBar(true);
                $(window).on('popstate', { self: this }, this.onHistoryChange);
            }

        },

        /**
         * Detect active URLs and push combined URL to history
         *
         * TODO: How should we handle POST requests? e.g. search VS login
         */
        pushCurrentState: function () {

            var icinga = this.icinga;

            // No history API, no action
            if (!this.enabled) {
                return;
            }

            icinga.logger.debug('Pushing current state to history');
            var url = '';

            // We only store URLs of containers sitting directly under #main:
            $('#main > .container').each(function (idx, container) {
                var cUrl = $(container).data('icingaUrl');

                // TODO: I'd prefer to have the rightmost URL first
                if ('undefined' !== typeof cUrl) {
                    // TODO: solve this on server side cUrl = icinga.utils.removeUrlParams(cUrl, blacklist);
                    if (url === '') {
                        url = cUrl;
                    } else {
                        url = url + '#!' + cUrl;
                    }
                }
            });

            // TODO: update navigation
            // Did we find any URL? Then push it!
            if (url !== '') {
                this.push(url);
            }
        },

        pushUrl: function (url) {
            // No history API, no action
            if (!this.enabled) {
                return;
            }
            this.push(url);
        },

        push: function (url) {
            url = url.replace(/[\?&]?_(render|reload)=[a-z0-9]+/g, '');
            if (this.lastPushUrl === url) {
                return;
            }
            this.lastPushUrl = url;
            window.history.pushState({icinga: true}, null, url);
        },

        /**
         * Event handler for pop events
         *
         * TODO: Fix active selection, multiple cols
         */
        onHistoryChange: function (event) {

            var self   = event.data.self,
                icinga = self.icinga,
                onload;

            icinga.logger.debug('Got a history change');

            // Chrome workaround:
            onload = !self.pushedSomething && location.href === self.initialUrl;
            self.pushedSomething = true;
            // if (onload) { return; } // Temporarily disabled
            // End of Chrome workaround

            // We might find browsers showing strange behaviour, this log could help
            if (event.originalEvent.state === null) {
                icinga.logger.debug('No more history steps available');
            } else {
                icinga.logger.debug('History state', event.originalEvent.state);
            }

            self.applyLocationBar();

        },

        applyLocationBar: function (onload) {
            var icinga = this.icinga,
                main,
                parts;

            if (typeof onload === 'undefined') {
                onload = false;
            }

            // TODO: Still hardcoding col1/col2, shall be dynamic soon
            main = document.location.pathname + document.location.search;
            if (! onload && $('#col1').data('icingaUrl') !== main) {
                icinga.loader.loadUrl(
                    main,
                    $('#col1')
                ).addToHistory = false;
            }

            if (document.location.hash && document.location.hash.match(/^#!/)) {

                parts = document.location.hash.split(/#!/);

                if ($('#layout > #login').length) {
                    // We are on the login page
                    var redirect = $('#login form input[name=redirect]').first();
                    redirect.val(
                        redirect.val() + '#!' + parts[1]
                    );
                } else {
                    if ($('#col2').data('icingaUrl') !== main) {
                        icinga.loader.loadUrl(
                            parts[1],
                            $('#col2')
                        ).addToHistory = false;
                    }
                }

                // TODO: Replace with dynamic columns
                icinga.ui.layout2col();

            } else {
                // TODO: Replace with dynamic columns
                icinga.ui.layout1col();
            }
        },

        /**
         * Cleanup
         */
        destroy: function () {
            $(window).off('popstate', this.onHistoryChange);
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
