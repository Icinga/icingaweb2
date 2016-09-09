/*! Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * This is how we bootstrap JS code in our modules
 */
(function(Icinga, $) {

    'use strict';

    Icinga.Module = function (icinga, name, prototyp) {

        // The Icinga instance
        this.icinga = icinga;

        // Applied event handlers
        this.handlers = [];

        // Event handlers registered by this module
        this.registeredHandlers = [];

        // The module name
        this.name = name;

        // The JS prototype for this module
        this.prototyp = prototyp;

        // Once initialized, this will be an instance of the modules prototype
        this.object = {};

        // Initialize this module
        this.initialize();
    };

    Icinga.Module.prototype = {

        initialize: function () {

            if (typeof this.prototyp !== 'function') {
                this.icinga.logger.error(
                    'Unable to load module "' + this.name + '", constructor is missing'
                );
                return false;
            }

            try {

                // The constructor of the modules prototype must be prepared to get an
                // instance of Icinga.Module
                this.object = new this.prototyp(this);
                this.applyHandlers();
            } catch(e) {
                this.icinga.logger.error(
                    'Failed to load module ' + this.name + ': ',
                    e
                );

                return false;
            }

            // That's all, the module is ready
            this.icinga.logger.debug(
                'Module ' + this.name + ' has been initialized'
            );

            return true;
        },

        /**
         * Register this modules event handlers
         */
        on: function (event, filter, handler) {
            if (typeof handler === 'undefined') {
                handler = filter;
                filter = '.module-' + this.name;
            } else {
                filter = '.module-' + this.name + ' ' + filter;
            }
            this.registeredHandlers.push({event: event, filter: filter, handler: handler});

        },

        applyHandlers: function () {
            var _this = this;

            $.each(this.registeredHandlers, function (key, on) {
                _this.bindEventHandler(
                    on.event,
                    on.filter,
                    on.handler
                );
            });
            _this = null;

            return this;
        },

        /**
         * Effectively bind the given event handler
         */
        bindEventHandler: function (event, filter, handler) {
            var _this = this;
            this.icinga.logger.debug('Bound ' + filter + ' .' + event + '()');
            this.handlers.push([event, filter, handler]);
            $(document).on(event, filter, handler.bind(_this.object));
        },

        /**
         * Unbind all event handlers bound by this module
         */
        unbindEventHandlers: function () {
            $.each(this.handlers, function (idx, handler) {
                $(document).off(handler[0], handler[1], handler[2]);
            });
        },

        /**
         * Allow to destroy and clean up this module
         */
        destroy: function () {

            this.unbindEventHandlers();

            if (typeof this.object.destroy === 'function') {
              this.object.destroy();
            }

            this.object = null;
            this.icinga = null;
            this.prototyp = null;
        }

    };

}(Icinga, jQuery));
