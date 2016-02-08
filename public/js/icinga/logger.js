/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Logger
 *
 * Well, log output. Rocket science.
 */
(function (Icinga) {

    'use strict';

    Icinga.Logger = function (icinga) {

        this.icinga = icinga;

        this.logLevel = 'info';

        this.logLevels = {
            'debug': 0,
            'info' : 1,
            'warn' : 2,
            'error': 3
        };

    };

    Icinga.Logger.prototype = {

        /**
         * Whether the browser has a console object
         */
        hasConsole: function () {
            return 'undefined' !== typeof console;
        },

        /**
         * Raise or lower current log level
         *
         * Messages below this threshold will be silently discarded
         */
        setLevel: function (level) {
            if ('undefined' !== typeof this.numericLevel(level)) {
                this.logLevel = level;
            }
            return this;
        },

        /**
         * Log a debug message
         */
        debug: function () {
            return this.writeToConsole('debug', arguments);
        },

        /**
         * Log an informational message
         */
        info: function () {
            return this.writeToConsole('info', arguments);
        },

        /**
         * Log a warning message
         */
        warn: function () {
            return this.writeToConsole('warn', arguments);
        },

        /**
         * Log an error message
         */
        error: function () {
            return this.writeToConsole('error', arguments);
        },

        /**
         * Write a log message with the given level to the console
         */
        writeToConsole: function (level, args) {

            args = Array.prototype.slice.call(args);

            // We want our log messages to carry precise timestamps
            args.unshift(this.icinga.utils.timeWithMs());

            if (this.hasConsole() && this.hasLogLevel(level)) {
                if (typeof console[level] !== 'undefined') {
                    if (typeof console[level].apply === 'function') {
                        console[level].apply(console, args);
                    } else {
                        args.unshift('[' + level + ']');
                        console[level](args.join(' '));
                    }
                } else if ('undefined' !== typeof console.log) {
                    args.unshift('[' + level + ']');
                    console.log(args.join(' '));
                }
            }
            return this;
        },

        /**
         * Return the numeric identifier for a given log level
         */
        numericLevel: function (level) {
            var ret = this.logLevels[level];
            if ('undefined' === typeof ret) {
                throw 'Got invalid log level ' + level;
            }
            return ret;
        },

        /**
         * Whether a given log level exists
         */
        hasLogLevel: function (level) {
            return this.numericLevel(level) >= this.numericLevel(this.logLevel);
        },

        /**
         * There isn't much to clean up here
         */
        destroy: function () {
            this.enabled = false;
            this.icinga = null;
        }
    };

}(Icinga));
