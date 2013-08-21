/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

/**
 * A module to load and manage frontend components
 *
 */
define(['jquery', 'logging', 'icinga/componentRegistry'], function ($, log, registry) {
        "use strict";

        var ComponentLoader = function() {

            /**
             * Load the component with the given type and attach it to the target
             *
             * @param {String}          cmpType         The component type to load '<module>/<component>'
             * @param {HTMLElement}     target          The targeted dom node
             * @param {function}        fin             The called when the component was successfully loaded
             * @param {function}        err             The error-callback
             */
            var loadComponent = function(cmpType, target, fin, err) {
                requirejs(
                    ['modules/' + cmpType],
                    function (Cmp) {
                        var cmp;
                        try {
                            cmp = new Cmp(target);
                        } catch (e) {
                            log.emergency(e);
                            err(e);
                            return;
                        }
                        if (fin) {
                            fin(cmp);
                        }
                    },
                    function (ex) {
                        if (!ex) {
                            return;
                        }
                        log.emergency('Component "' + cmpType + '" could not be loaded.', ex);
                        if (err) {
                            err(ex);
                        }
                    }
                );
            };

            /**
             * Load all new components and remove components that were removed from
             * the DOM from the internal registry
             *
             * @param {function}        fin         Called when the loading is completed
             */
            this.load = function(fin) {

                /*
                 * Count the amount of pending callbacks to make sure everything is loaded
                 * when calling the garbage collection.
                 */
                var pendingFns = 1;

                var finalize = function() {
                    pendingFns--;
                    /*
                     * Only return when all components are loaded
                     */
                    if (pendingFns === 0) {
                        registry.removeInactive();
                        if (fin) {
                            fin();
                        }
                    }
                };

                registry.markAllInactive();

                $('div[data-icinga-component]')
                    .each(function(index, el) {
                        var type = $(el).attr('data-icinga-component');
                        pendingFns++;

                        if (!el.id || !registry.getById(el.id)) {
                            loadComponent(
                                type,
                                el,
                                function(cmp) {
                                    var id = registry.add(cmp, el.id, type);
                                    registry.markActive(id);
                                    el.id = id;
                                    finalize();
                                },
                                finalize
                            );
                        } else {
                            registry.markActive(el.id);
                            finalize();
                        }
                    });
                finalize();
            };

            /**
             * Get the id of the given component, if one is assigned
             *
             * @param   {*}             component   The component of which the id should be retrieved
             *
             * @returns {String|null}               The id of the component, or null
             */
            this.getId = function(component) {
                return registry.getId(component);
            };

            /**
             * Get the component that is assigned to the given id
             *
             * @param   {String}        id          The id of the component
             *
             * @returns {*}                         The component or null
             */
            this.getById = function(id) {
                return registry.getById(id);
            };

            /**
             * Get all components that match the given type
             *
             * @param   {String}        type        The component type in the form '<module>/<component>'
             *
             * @returns {*|Array}                   The components or an empty array
             */
            this.getByType = function(type) {
                return registry.getByType(type);
            };

            /**
             * Get all components
             *
             * @returns {*|Array}                   The components or an empty array
             */
            this.getComponents = function() {
                return registry.getComponents();
            };
        };
        return new ComponentLoader();
});
