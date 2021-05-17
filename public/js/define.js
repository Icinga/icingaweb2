/*! Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

(function(window) {

    'use strict';

    /**
     * Provide a reference to be later required by foreign code
     *
     * @param {string} name Optional, defaults to the name (and path) of the file
     * @param {string[]} requirements Optional, list of required references, may be relative if from the same package
     * @param {function} factory Required, function that accepts as many params as there are requirements and that
     *                           produces a value to be referenced
     */
    var define = function (name, requirements, factory) {
        define.defines[name] = {
            requirements: requirements,
            factory: factory,
            ref: null
        }

        define.resolve(name);
    }

    /**
     * Return whether the given name references a value
     *
     * @param {string} name The absolute name of the reference
     * @return {boolean}
     */
    define.has = function (name) {
        return name in define.defines && define.defines[name]['ref'] !== null;
    }

    /**
     * Get the value of a reference
     *
     * @param {string} name The absolute name of the reference
     * @return {*}
     */
    define.get = function (name) {
        return define.defines[name]['ref'];
    }

    /**
     * Set the value of a reference
     *
     * @param {string} name The absolute name of the reference
     * @param {*} ref The value to reference
     */
    define.set = function (name, ref) {
        define.defines[name]['ref'] = ref;
    }

    /**
     * Resolve a reference and, if successful, dependent references
     *
     * @param {string} name The absolute name of the reference
     * @return {boolean}
     */
    define.resolve = function (name) {
        var requirements = define.defines[name]['requirements'];

        var exports, ref;
        var requiredRefs = [];
        for (var i = 0; i < requirements.length; i++) {
            if (define.has(requirements[i])) {
                ref = define.get(requirements[i]);
            } else if (requirements[i] === 'exports') {
                exports = ref = {};
            } else {
                return false;
            }

            requiredRefs.push(ref);
        }

        var factory = define.defines[name]['factory'];
        var resolved = factory.apply(null, requiredRefs);

        if (typeof exports === 'object') {
            if (typeof resolved !== 'undefined') {
                throw new Error('Factory for ' + name + ' returned, although exports were populated');
            }

            resolved = exports;
        }

        define.set(name, resolved);

        for (var definedName in define.defines) {
            if (define.defines[definedName]['requirements'].indexOf(name) >= 0) {
                define.resolve(definedName);
            }
        }
    }

    /**
     * Require a reference
     *
     * @param {string} name The absolute name of the reference
     * @return {*}
     */
    var require = function(name) {
        if (define.has(name)) {
            return define.get(name);
        }

        throw new ReferenceError(name + ' is not defined');
    }

    define.icinga = true;
    define.defines = {};

    window.define = define;
    window.require = require;

})(window);
