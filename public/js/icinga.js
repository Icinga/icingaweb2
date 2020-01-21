/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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

        this.initialized = false;

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
         * Additional site behavior
         */
        this.behaviors = {};

        /**
         * Loaded modules
         */
        this.modules = {};

        var _this = this;
        $(document).ready(function () {
            _this.initialize();
            _this = null;
        });
    };

    Icinga.prototype = {

        /**
         * Icinga startup, will be triggerd once the document is ready
         */
        initialize: function () {
            if (this.initialized) {
                return false;
            }

            this.timezone   = new Icinga.Timezone();
            this.utils      = new Icinga.Utils(this);
            this.logger     = new Icinga.Logger(this);
            this.timer      = new Icinga.Timer(this);
            this.ui         = new Icinga.UI(this);
            this.loader     = new Icinga.Loader(this);
            this.events     = new Icinga.Events(this);
            this.history    = new Icinga.History(this);
            var _this = this;
            $.each(Icinga.Behaviors, function(name, Behavior) {
                _this.behaviors[name.toLowerCase()] = new Behavior(_this);
            });

            this.timezone.initialize();
            this.timer.initialize();
            this.events.initialize();
            this.history.initialize();
            this.ui.initialize();
            this.loader.initialize();

            this.logger.info('Icinga is ready, running on jQuery ', $().jquery);
            this.initialized = true;

            // Trigger our own post-init event, `onLoad` is not reliable enough
            $(document).trigger('icinga-init');
        },

        /**
         * Load a given module by name
         *
         * @param   {string}    name
         *
         * @return  {boolean}
         */
        loadModule: function (name) {

            if (this.isLoadedModule(name)) {
                this.logger.error('Cannot load module ' + name + ' twice');
                return false;
            }

            if (! this.hasModule(name)) {
                this.logger.error('Cannot find module ' + name);
                return false;
            }

            this.modules[name] = new Icinga.Module(
                this,
                name,
                Icinga.availableModules[name]
            );
            return true;
        },

        /**
         * Whether a module matching the given name exists or is loaded
         *
         * @param   {string}    name
         *
         * @return  {boolean}
         */
        hasModule: function (name) {
            return this.isLoadedModule(name) ||
                'undefined' !== typeof Icinga.availableModules[name];
        },

        /**
         * Return whether the given module is loaded
         *
         * @param   {string}    name    The name of the module
         *
         * @returns {Boolean}
         */
        isLoadedModule: function (name) {
            return 'undefined' !==  typeof this.modules[name];
        },

        /**
         * Get a module by name
         *
         * @param   {string}    name
         *
         * @return  {object}
         */
        module: function (name) {

            if (this.hasModule(name) && !this.isLoadedModule(name)) {
                this.modules[name] = new Icinga.Module(
                    this,
                    name,
                    Icinga.availableModules[name]
                );
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

            this.timezone.destroy();
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
            this.initialized = false;
        },

        reload: function () {
            setTimeout(function () {
                var oldjQuery = window.jQuery;
                var oldConfig = window.icinga.config;
                var oldIcinga = window.Icinga;
                window.icinga.destroy();
                window.Icinga = undefined;
                window.$ = undefined;
                window.jQuery = undefined;
                jQuery = undefined;
                $ = undefined;

                oldjQuery.getScript(
                    oldConfig.baseUrl.replace(/\/$/, '') + '/js/icinga.min.js'
                ).done(function () {
                    var jQuery = window.jQuery;
                    window.icinga = new window.Icinga(oldConfig);
                    window.icinga.initialize();
                    window.icinga.ui.reloadCss();
                    oldjQuery = undefined;
                    oldConfig = undefined;
                    oldIcinga = undefined;
                }).fail(function () {
                    window.jQuery = oldjQuery;
                    window.$ = window.jQuery;
                    window.Icinga = oldIcinga;
                    window.icinga = new Icinga(oldConfig);
                    window.icinga.ui.reloadCss();
                });
            }, 0);
        }

    };

    window.Icinga = Icinga;

    Icinga.availableModules = {};

})(window, jQuery);
