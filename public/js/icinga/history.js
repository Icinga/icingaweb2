/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Icinga.History
 *
 * This is where we care about the browser History API
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
         * Get the current state (url and title) as object
         *
         * @returns {object}
         */
        getCurrentState: function () {
            if (! this.enabled) {
                return null;
            }

            var title = null;
            var url = null;

            // We only store URLs of containers sitting directly under #main:
            $('#main > .container').each(function (idx, container) {
                var $container = $(container),
                    cUrl = $container.data('icingaUrl'),
                    cTitle = $container.data('icingaTitle');

                // TODO: I'd prefer to have the rightmost URL first
                if ('undefined' !== typeof cUrl) {
                    // TODO: solve this on server side cUrl = icinga.utils.removeUrlParams(cUrl, blacklist);
                    if (! url) {
                        url = cUrl;
                    } else {
                        url = url + '#!' + cUrl;
                    }
                }

                if (typeof cTitle !== 'undefined') {
                    title = cTitle; // Only uses the rightmost title
                }
            });

            return {
                title: title,
                url: url,
            };
        },

        /**
         * Detect active URLs and push combined URL to history
         *
         * TODO: How should we handle POST requests? e.g. search VS login
         */
        pushCurrentState: function () {
            // No history API, no action
            if (! this.enabled) {
                return;
            }

            var state = this.getCurrentState();

            // Did we find any URL? Then push it!
            if (state.url) {
                this.icinga.logger.debug('Pushing current state to history');
                this.push(state.url);
            }
            if (state.title) {
                this.icinga.ui.setTitle(state.title);
            }
        },

        /**
         * Replace the current history entry with the current state
         */
        replaceCurrentState: function () {
            if (! this.enabled) {
                return;
            }

            var state = this.getCurrentState();

            if (state.url) {
                this.icinga.logger.debug('Replacing current history state');
                this.lastPushUrl = state.url;
                window.history.replaceState(
                    this.getBehaviorState(),
                    null,
                    state.url
                );
            }
        },

        /**
         * Push the given url as the new history state, unless the history is disabled
         *
         * @param   {string}    url     The full url path, including anchor
         */
        pushUrl: function (url) {
            // No history API, no action
            if (!this.enabled) {
                return;
            }
            this.push(url);
        },

        /**
         * Execute the history state, preserving the current state of behaviors
         *
         * Used internally by the history and should not be called externally, instead use {@link pushUrl}.
         *
         * @param   {string}    url
         */
        push: function (url) {
            url = url.replace(/[\?&]?_(render|reload)=[a-z0-9]+/g, '');
            if (this.lastPushUrl === url) {
                this.icinga.logger.debug(
                    'Ignoring history state push for url ' + url + ' as it\' currently on top of the stack'
                );
                return;
            }
            this.lastPushUrl = url;
            window.history.pushState(
                this.getBehaviorState(),
                null,
                url
            );
        },

        /**
         * Fetch the current state of all JS behaviors that need history support
         *
         * @return {Object} A key-value map, mapping behavior names to state
         */
        getBehaviorState: function () {
            var data = {};
            $.each(this.icinga.behaviors, function (i, behavior) {
                if (behavior.onPushState instanceof Function) {
                    data[i] = behavior.onPushState();
                }
            });
            return data;
        },

        /**
         * Event handler for pop events
         *
         * TODO: Fix active selection, multiple cols
         */
        onHistoryChange: function (event) {

            var _this   = event.data.self,
                icinga = _this.icinga;

            icinga.logger.debug('Got a history change');

            // We might find browsers showing strange behaviour, this log could help
            if (event.originalEvent.state === null) {
                icinga.logger.debug('No more history steps available');
            } else {
                icinga.logger.debug('History state', event.originalEvent.state);
            }

            // keep the last pushed url in sync with history changes
            _this.lastPushUrl = location.href;

            _this.applyLocationBar();

            // notify behaviors of the state change
            $.each(this.icinga.behaviors, function (i, behavior) {
                if (behavior.onPopState instanceof Function && history.state) {
                    behavior.onPopState(location.href, history.state[i]);
                }
            });
        },

        /**
         * Update the application containers to match the current url
         *
         * Read the pane url from the current URL and load the corresponding panes into containers to
         * match the current history state.
         *
         * @param   {Boolean}  onload  Set to true when the main pane should not be updated, defaults to false
         */
        applyLocationBar: function (onload = false) {
            let col2State = this.getCol2State();

            if (onload && document.querySelector('#layout > #login')) {
                // The user landed on the login
                let redirectInput = document.querySelector('#login form input[name=redirect]');
                redirectInput.value = redirectInput.value + col2State;
                return;
            }

            let col1 = document.getElementById('col1'),
                col2 = document.getElementById('col2'),
                col1Url = document.location.pathname + document.location.search;

            let col2Url;
            if (col2State && col2State.match(/^#!/)) {
                col2Url = col2State.split(/#!/)[1];
            }

            // This uses jQuery only because of its internal data attribute cache -.-
            let currentCol1Url = $(col1).data('icingaUrl'),
                currentCol2Url = $(col2).data('icingaUrl');

            let loadCol1 = ! onload,
                loadCol2 = !! col2Url;
            if (currentCol2Url === col1Url) {
                // User navigated forward
                this.icinga.ui.moveToLeft();
                loadCol1 = false;
            } else if (currentCol1Url === col2Url) {
                // User navigated back
                this.icinga.ui.moveToRight();
                loadCol2 = false;
            }

            if (loadCol1 && currentCol1Url !== col1Url) {
                let anchor = this.getPaneAnchor(0);
                if (anchor) {
                    col1Url += '#' + anchor;
                }

                this.icinga.loader.loadUrl(col1Url, $(col1)).addToHistory = false;
            }

            if (loadCol2 && currentCol2Url !== col2Url) {
                let col2Req = this.icinga.loader.loadUrl(col2Url, $(col2));
                col2Req.addToHistory = false;
                col2Req.scripted = onload;

                this.icinga.ui.layout2col();
            } else if (! loadCol2 && ! col2Url) {
                this.icinga.ui.layout1col();
            }
        },

        /**
         * Get the state of the selected pane
         *
         * @param   col {int}       The column index 0 or 1
         *
         * @returns     {String}    The string representing the state
         */
        getPaneAnchor: function (col) {
            if (col !== 1 && col !== 0) {
                throw 'Trying to get anchor for non-existing column: ' + col;
            }
            var panes = document.location.toString().split('#!')[col];
            return panes && panes.split('#')[1] || '';
        },

        /**
         * Get the side pane state after (and including) the #!
         *
         * @returns {string}    The pane url
         */
        getCol2State: function () {
            var hash = document.location.hash;
            if (hash) {
                if (hash.match(/^#[^!]/)) {
                    var hashs = hash.split('#');
                    hashs.shift();
                    hashs.shift();
                    hash = '#' + hashs.join('#');
                }
            }
            return hash || '';
        },

        /**
         * Return the main pane state fragment
         *
         * @returns {string}    The main url including anchors, without #!
         */
        getCol1State: function () {
            var anchor = this.getPaneAnchor(0);
            var hash = window.location.pathname + window.location.search +
                (anchor.length ? ('#' + anchor) : '');
            return hash || '';
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
