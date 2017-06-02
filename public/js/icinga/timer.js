/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Timer
 *
 * Timer events are triggered once a second. Runs all reegistered callback
 * functions and is able to preserve a desired scope.
 */
(function(Icinga, $) {

    'use strict';

    Icinga.Timer = function (icinga) {

        /**
         * We keep a reference to the Icinga instance even if we don't need it
         */
        this.icinga = icinga;

        /**
         * The Interval object
         */
        this.ticker = null;

        /**
         * Fixed default interval is 250ms
         */
        this.interval = 250;

        /**
         * Our registerd observers
         */
        this.observers = [];

        /**
         * Counter
         */
        this.stepCounter = 0;

        this.start = (new Date()).getTime();


        this.lastRuntime = [];

        this.isRunning = false;
    };

    Icinga.Timer.prototype = {

        /**
         * The initialization function starts our ticker
         */
        initialize: function () {
            this.isRunning = true;

            var _this = this;
            var f = function () {
                if (_this.isRunning) {
                    _this.tick();
                    setTimeout(f, _this.interval);
                }
            };
            f();
        },

        /**
         * We will trigger our tick function once a second. It will call each
         * registered observer.
         */
        tick: function () {

            var icinga = this.icinga;

            $.each(this.observers, function (idx, observer) {
                if (observer.isDue()) {
                    observer.run();
                } else {
                    // Not due
                }
            });
            icinga = null;
        },

        /**
         * Register a given callback function to be run within an optional scope.
         */
        register: function (callback, scope, interval) {

            var observer;

            try {

                if (typeof scope === 'undefined') {
                    observer = new Icinga.Timer.Interval(callback, interval);
                } else {
                    observer = new Icinga.Timer.Interval(
                        callback.bind(scope),
                        interval
                    );
                }

                this.observers.push(observer);

            } catch(err) {
                this.icinga.logger.error(err);
            }

            return observer;
        },

        unregister: function (observer) {

            var idx = $.inArray(observer, this.observers);
            if (idx > -1) {
                this.observers.splice(idx, 1);
            }

            return this;
        },

        /**
         * Our destroy function will clean up everything. Unused right now.
         */
        destroy: function () {
            this.isRunning = false;

            this.icinga = null;
            $.each(this.observers, function (idx, observer) {
                observer.destroy();
            });

            this.observers = [];
        }
    };

    Icinga.Timer.Interval = function (callback, interval) {

        if ('undefined' === typeof interval) {
            throw 'Timer interval is required';
        }

        if (interval < 100) {
            throw 'Timer interval cannot be less than 100ms, got ' + interval;
        }

        this.lastRun = (new Date()).getTime();

        this.interval = interval;

        this.scheduledNextRun = this.lastRun + interval;

        this.callback = callback;
    };

    Icinga.Timer.Interval.prototype = {

        isDue: function () {
            return this.scheduledNextRun < (new Date()).getTime();
        },

        run: function () {
            this.lastRun = (new Date()).getTime();

            while (this.scheduledNextRun < this.lastRun) {
              this.scheduledNextRun += this.interval;
            }

            this.callback();
        },

        destroy: function () {
            this.callback = null;
        }
    };

}(Icinga, jQuery));
