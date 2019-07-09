/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

(function (Icinga, $) {

    'use strict';

    /**
     * Icinga.Storage
     *
     * localStorage access
     *
     * @param   {string}    prefix
     */
    Icinga.Storage = function(prefix) {

        /**
         * Prefix to use for keys
         *
         * @type {string}
         */
        this.prefix = prefix;

        /**
         * Callbacks for storage events on particular keys
         *
         * @type {{function}}
         */
        this.subscribers = {};

        this.setup();
    };

    /**
     * Create a new storage with `behavior.<name>` as prefix
     *
     * @param   {string}    name
     *
     * @returns {Icinga.Storage}
     */
    Icinga.Storage.BehaviorStorage = function(name) {
        return new Icinga.Storage('behavior.' + name);
    };

    Icinga.Storage.prototype = {

        /**
         * Prefix the given key
         *
         * @param   {string}    key
         *
         * @returns {string}
         */
        prefixKey: function(key) {
            if (typeof this.prefix !== 'undefined') {
                return this.prefix + '.' + key;
            }

            return key;
        },

        /**
         * Store the given key-value pair
         *
         * @param   {string}    key
         * @param   {*}         value
         *
         * @returns {void}
         */
        set: function(key, value) {
            window.localStorage.setItem(this.prefixKey(key), JSON.stringify(value));
        },

        /**
         * Get value for the given key
         *
         * @param   {string}    key
         *
         * @returns {*}
         */
        get: function(key) {
            return JSON.parse(window.localStorage.getItem(this.prefixKey(key)));
        },

        /**
         * Remove given key from storage
         *
         * @param   {string}    key
         *
         * @returns {void}
         */
        remove: function(key) {
            window.localStorage.removeItem(this.prefixKey(key));
        },

        /**
         * Subscribe with a callback for events on a particular key
         *
         * @param   {string}    key
         * @param   {function}  callback
         * @param   {object}    context
         *
         * @returns {void}
         */
        subscribe: function(key, callback, context) {
            this.subscribers[this.prefixKey(key)] = [callback, context];
        },

        /**
         * Pass storage events to subscribers
         *
         * @param   {StorageEvent}  event
         */
        onStorage: function(event) {
            if (typeof this.subscribers[event.key] !== 'undefined') {
                var subscriber = this.subscribers[event.key];
                subscriber[0].call(subscriber[1], JSON.parse(event.newValue), JSON.parse(event.oldValue), event);
            }
        },

        /**
         * Add the event listener
         *
         * @returns {void}
         */
        setup: function() {
            window.addEventListener('storage', this.onStorage.bind(this));
        },

        /**
         * Remove the event listener
         *
         * @returns {void}
         */
        destroy: function() {
            window.removeEventListener('storage', this.onStorage.bind(this));
        }
    };

    /**
     * Icinga.Storage.StorageAwareMap
     *
     * Emits events `StorageAwareMapDelete` and `StorageAwareMapAdd` in case an update occurs in the storage.
     *
     * @param   {object} items
     * @constructor
     */
    Icinga.Storage.StorageAwareMap = function(items) {

        /**
         * Storage object
         *
         * @type {Icinga.Storage}
         */
        this.storage = undefined;

        /**
         * Storage key
         *
         * @type {string}
         */
        this.key = undefined;

        /**
         * The internal (real) map
         *
         * @type {Map<*>}
         */
        this.data = new Map();

        // items is not passed directly because IE11 doesn't support constructor arguments
        if (typeof items !== 'undefined' && !! items) {
            Object.keys(items).forEach(function(key) {
                this.data.set(key, items[key]);
            }, this);
        }
    };

    /**
     * Create a new StorageAwareMap for the given storage and key
     *
     * @param   {Icinga.Storage}    storage
     * @param   {string}            key
     *
     * @returns {Icinga.Storage.StorageAwareMap}
     */
    Icinga.Storage.StorageAwareMap.withStorage = function(storage, key) {
        return (new Icinga.Storage.StorageAwareMap(storage.get(key)).setStorage(storage, key));
    };

    Icinga.Storage.StorageAwareMap.prototype = {

        /**
         * Bind this map to the given storage and key
         *
         * @param   {Icinga.Storage}    storage
         * @param   {string}            key
         *
         * @returns {this}
         */
        setStorage: function(storage, key) {
            this.storage = storage;
            this.key = key;

            storage.subscribe(key, this.onChange, this);
            return this;
        },

        /**
         * Return a boolean indicating this map got a storage
         *
         * @returns {boolean}
         */
        hasStorage: function() {
            return typeof this.storage !== 'undefined' && typeof this.key !== 'undefined';
        },

        /**
         * Update the map
         *
         * @param   {object} newValue
         */
        onChange: function(newValue) {
            // Check for deletions first
            this.keys().forEach(function (key) {
                if (typeof newValue[key] === 'undefined') {
                    this.data.delete(key);
                    $(window).trigger('StorageAwareMapDelete', key);
                }
            }, this);

            // Now check for new entries
            Object.keys(newValue).forEach(function(key) {
                if (! this.data.has(key)) {
                    this.data.set(key, newValue[key]);
                    $(window).trigger('StorageAwareMapAdd', key);
                }
            }, this);
        },

        /**
         * Register an event handler to handle storage updates
         *
         * Available events are: add, delete
         *
         * @param   {string}    event
         * @param   {object}    data
         * @param   {function}  handler
         *
         * @returns {this}
         */
        on: function(event, data, handler) {
            $(window).on(
                'StorageAwareMap' + event.charAt(0).toUpperCase() + event.slice(1),
                data,
                handler
            );

            return this;
        },

        /**
         * Return the number of key/value pairs in the map
         *
         * @returns {number}
         */
        get size() {
            return this.data.size;
        },

        /**
         * Set the value for the key in the map
         *
         * @param   {string}    key
         * @param   {*}         value
         *
         * @returns {this}
         */
        set: function(key, value) {
            this.data.set(key, value);

            if (this.hasStorage()) {
                this.storage.set(this.key, this.toObject());
            }

            return this;
        },

        /**
         * Remove all key/value pairs from the map
         *
         * @returns {void}
         */
        clear: function() {
            if (this.hasStorage()) {
                this.storage.remove(this.key);
            }

            return this.data.clear();
        },

        /**
         * Remove the given key from the map
         *
         * @param   {string}    key
         *
         * @returns {boolean}
         */
        delete: function(key) {
            var retVal = this.data.delete(key);

            if (this.hasStorage()) {
                this.storage.set(this.key, this.toObject());
            }

            return retVal;
        },

        /**
         * Return a list of [key, value] pairs for every item in the map
         *
         * @returns {Array}
         */
        entries: function() {
            var list = [];

            if (this.size > 0) {
                this.forEach(function (value, key) {
                    list.push([key, value]);
                });
            }

            return list;
        },

        /**
         * Execute a provided function once for each item in the map, in insertion order
         *
         * @param   {function}  callback
         *
         * @returns {void}
         */
        forEach: function(callback) {
            return this.data.forEach(callback);
        },

        /**
         * Return the value associated to the key, or undefined if there is none
         *
         * @param   {string}    key
         *
         * @returns {*}
         */
        get: function(key) {
            return this.data.get(key);
        },

        /**
         * Return a boolean asserting whether a value has been associated to the key in the map
         *
         * @param   {string}    key
         *
         * @returns {boolean}
         */
        has: function(key) {
            return this.data.has(key);
        },

        /**
         * Return an array of keys in the map
         *
         * @returns {Array}
         */
        keys: function() {
            var list = [];

            if (this.size > 0) {
                // .forEach() is used because IE11 doesn't support .keys()
                this.forEach(function(_, key) {
                    list.push(key);
                });
            }

            return list;
        },

        /**
         * Return an array of values in the map
         *
         * @returns {Array}
         */
        values: function() {
            var list = [];

            if (this.size > 0) {
                // .forEach() is used because IE11 doesn't support .values()
                this.forEach(function(value) {
                    list.push(value);
                });
            }

            return list;
        },

        /**
         * Return this map as simple object
         *
         * @returns {object}
         */
        toObject: function() {
            var obj = {};

            if (this.size > 0) {
                this.forEach(function (value, key) {
                    obj[key] = value;
                });
            }

            return obj;
        }
    };

}(Icinga, jQuery));
