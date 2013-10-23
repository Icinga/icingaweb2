// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * A module to load and manage frontend components
 *
 */
define(['jquery', 'logging', 'icinga/componentRegistry'], function ($, log, registry) {
    'use strict';

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
                ['components/' + cmpType],
                function (Cmp) {
                    var cmp;
                    try {
                        cmp = new Cmp(target);

                    } catch (e) {
                        log.emergency('Error in component "' + cmpType + '" : "' + e + '"');
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

            $('[data-icinga-component]')
                .each(function(index, el) {
                    var type = $(el).attr('data-icinga-component');
                    pendingFns++;

                    if (!el.id || !registry.getById(el.id)) {
                        loadComponent(
                            type,
                            el,
                            function(cmp) {
                                var id = registry.add(cmp, type);
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
