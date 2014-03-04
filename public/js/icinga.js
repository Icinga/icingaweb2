/**
 * Icinga starts here.
 *
 * Usage example:
 *
 * <code>
 * var icinga = new Icinga({
 *   baseUrl: '/icinga',
 * });
 * </code>
 */
(function(window, $) {

    'use strict';

    var Icinga = function (config) {

        /**
         * Our config object
         */
        this.config = config;

        /**
         * Icinga.Logger
         */
        this.logger = null;

        /**
         * Icinga.UI
         */
        this.ui = null;

        /**
         * Icinga.Loader
         */
        this.loader = null;

        /**
         * Icinga.Events
         */
        this.events = null;

        /**
         * Icinga.Timer
         */
        this.timer = null;

        /**
         * Icinga.History
         */
        this.history = null;

        /**
         * Icinga.Utils
         */
        this.utils = null;

        /**
         * Loaded modules
         */
        this.modules = {};

        var self = this;
        $(document).ready(function () {
            self.initialize();
            self = null;
        });
    };

    Icinga.prototype = {

        /**
         * Icinga startup, will be triggerd once the document is ready
         */
        initialize: function () {

            this.utils   = new Icinga.Utils(this);
            this.logger  = new Icinga.Logger(this);
            this.timer   = new Icinga.Timer(this);
            this.ui      = new Icinga.UI(this);
            this.loader  = new Icinga.Loader(this);
            this.events  = new Icinga.Events(this);
            this.history = new Icinga.History(this);

            this.timer.initialize();
            this.events.initialize();
            this.history.initialize();
            this.ui.initialize();
            this.loader.initialize();
            this.logger.info('Icinga is ready');
        },

        /**
         * Load a given module by name
         */
        loadModule: function (name) {

            if (this.hasModule(name)) {
                this.logger.error('Cannot load module ' + name + ' twice');
                return;
            }

            this.modules[name] = new Icinga.Module(this, name);
        },

        /**
         * Whether a module matching the given name exists
         */
        hasModule: function (name) {
            return 'undefined' !==  typeof this.modules[name] ||
                'undefined' !== typeof Icinga.availableModules[name];
        },

        /**
         * Get a module by name
         */
        module: function (name) {

            if ('undefined' === typeof this.modules[name]) {
                if ('undefined' !== typeof Icinga.availableModules[name]) {
                    this.modules[name] = new Icinga.Module(
                        this,
                        name,
                        Icinga.availableModules[name]
                    );
                }
            }

            return this.modules[name];
        },

        /**
         * Clean up and unload all Icinga components
         */
        destroy: function () {

            $.each(this.modules, function (name, module) {
                module.destroy();
            });

            this.timer.destroy();
            this.events.destroy();
            this.loader.destroy();
            this.ui.destroy();
            this.logger.debug('Icinga has been destroyed');
            this.logger.destroy();
            this.utils.destroy();

            this.modules = [];
            this.timer = this.events = this.loader = this.ui = this.logger =
                this.utils = null;
        }
    };

    window.Icinga = Icinga;

    Icinga.availableModules = {};

})(window, window.jQuery);
