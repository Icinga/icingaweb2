/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

/**
 * A component registry that maps components to unique IDs and keeps track
 * of component types to allow easy querying
 *
 */
define(['jquery'], function($) {
    "use strict";

    var ComponentRegistry = function() {
        var self = this;

        /**
         * Map ids to components
         */
        var components = {};

        /**
         * Generate a new component id
         */
        var createId = (function() {
            var id = 0;
            return function() {
                return 'icinga-component-' + id++;
            };
        })();

        /**
         * Get the id of the given component, if one is assigned
         *
         * @param   {*}             component   The component of which the id should be retrieved
         *
         * @returns {String|null}               The id of the component, or null
         */
        this.getId = function(cmp) {
            var id = null;
            $.each(components, function(key, value) {
                if (value && value.cmp === cmp) {
                    id = key;
                }
            });
            return id;
        };

        /**
         * Get the component that is assigned to the given id
         *
         * @param   {String}        id          The id of the component
         *
         * @returns {*}                         The component or null
         */
        this.getById = function(id) {
            return components[id] && components[id].cmp;
        };

        /**
         * Get all components that match the given type
         *
         * @param   {String}        type        The component type in the form '<module>/<component>'
         *
         * @returns {*|Array}                   The components or an empty array
         */
        this.getByType = function(type) {
            return $.map(components, function(entry) {
                return entry.type === type ? entry.cmp : null;
            });
        };

        /**
         * Get all components
         *
         * @returns {*|Array}                   The components or an empty array
         */
        this.getComponents = function() {
            return $.map(components, function(entry) {
                return entry.cmp;
            });
        };

        /**
         * Add the given component to the registry and return the assigned id
         *
         * @param {*}               cmp         The component to add
         * @param {String}          id          The optional id that should be assigned to that component
         * @param {String}          type        The component type to load '<module>/<component>'
         *
         * @returns {*|Array}
         */
        this.add = function(cmp, id, type) {
            if (!id){
                id = self.getId(cmp) || createId();
            }
            components[id] = {
                cmp:    cmp,
                type:   type,
                active: true
            };
            return id;
        };

        /**
         * Mark all components inactive
         */
        this.markAllInactive = function() {
            $.each(components,function(index, el){
                if (el && el.active) {
                    el.active = false;
                }
            });
        };

        /**
         * Mark the component with the given id as active
         */
        this.markActive = function(id) {
            if (components[id]) {
                components[id].active = true;
            }
        };

        /**
         * Let the garbage collection remove all inactive components
         */
        this.removeInactive = function() {
            $.each(components, function(key,value) {
                if (!value || !value.active) {
                    delete components[key];
                }
            });
        };
    };
    return new ComponentRegistry();
});
