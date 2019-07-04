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
            localStorage.setItem(this.prefixKey(key), JSON.stringify(value));
        },

        /**
         * Get value for the given key
         *
         * @param   {string}    key
         *
         * @returns {*}
         */
        get: function(key) {
            return JSON.parse(localStorage.getItem(this.prefixKey(key)));
        },

        /**
         * Remove given key from storage
         *
         * @param   {string}    key
         *
         * @returns {void}
         */
        remove: function(key) {
            localStorage.removeItem(this.prefixKey(key));
        },

        /**
         * Subscribe with a callback for events on a particular key
         *
         * @param   {string}    key
         * @param   {function}  callback
         *
         * @returns {void}
         */
        subscribe: function(key, callback) {
            this.subscribers[this.prefixKey(key)] = callback;
        },

        /**
         * Pass storage events to subscribers
         *
         * @param   {StorageEvent}  event
         */
        onStorage: function(event) {
            if (typeof this.subscribers[event.key] !== 'undefined') {
                this.subscribers[event.key](JSON.parse(event.oldValue), JSON.parse(event.newValue));
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
     * Icinga.Storage.StorageAwareSet
     *
     * Emits events `StorageAwareSetDelete` and `StorageAwareSetAdd` in case an update occurs in the storage.
     *
     * @param   {Array} values
     * @constructor
     */
    Icinga.Storage.StorageAwareSet = function(values) {

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
         * The internal (real) set
         *
         * @type {Set<*>}
         */
        this.data = new Set();

        // items is not passed directly because IE11 doesn't support constructor arguments
        if (typeof values !== 'undefined' && !! values && values.length) {
            values.forEach(function(value) {
                this.data.add(value);
            }, this);
        }
    };

    /**
     * Create a new StorageAwareSet for the given storage and key
     *
     * @param   {Icinga.Storage}    storage
     * @param   {string}            key
     *
     * @returns {Icinga.Storage.StorageAwareSet}
     */
    Icinga.Storage.StorageAwareSet.withStorage = function(storage, key) {
        return (new Icinga.Storage.StorageAwareSet(storage.get(key)).setStorage(storage, key));
    };

    Icinga.Storage.StorageAwareSet.prototype = {

        /**
         * Bind this set to the given storage and key
         *
         * @param   {Icinga.Storage}    storage
         * @param   {string}            key
         *
         * @returns {this}
         */
        setStorage: function(storage, key) {
            this.storage = storage;
            this.key = key;

            storage.subscribe(key, this.onChange.bind(this));
            return this;
        },

        /**
         * Return a boolean indicating this set got a storage
         *
         * @returns {boolean}
         */
        hasStorage: function() {
            return typeof this.storage !== 'undefined' && typeof this.key !== 'undefined';
        },

        /**
         * Update the set
         *
         * @param   {Array} oldValue
         * @param   {Array} newValue
         */
        onChange: function(oldValue, newValue) {
            // Check for deletions first
            this.values().forEach(function (value) {
                if (newValue.indexOf(value) < 0) {
                    this.data.delete(value);
                    $(window).trigger('StorageAwareSetDelete', value);
                }
            }, this);

            // Now check for new entries
            newValue.forEach(function(value) {
                if (! this.data.has(value)) {
                    this.data.add(value);
                    $(window).trigger('StorageAwareSetAdd', value);
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
                'StorageAwareSet' + event.charAt(0).toUpperCase() + event.slice(1),
                data,
                handler
            );

            return this;
        },

        /**
         * Return the number of (unique) elements in the set
         *
         * @returns {number}
         */
        get size() {
            return this.data.size;
        },

        /**
         * Append the given value to the end of the set
         *
         * @param value
         *
         * @returns {this}
         */
        add: function(value) {
            this.data.add(value);

            if (this.hasStorage()) {
                this.storage.set(this.key, this.values());
            }

            return this;
        },

        /**
         * Remove all elements from the set
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
         * Remove the given value from the set
         *
         * @param value
         *
         * @returns {boolean}
         */
        delete: function(value) {
            var retVal = this.data.delete(value);

            if (this.hasStorage()) {
                this.storage.set(this.key, this.values());
            }

            return retVal;
        },

        /**
         * Returns an iterable of [v,v] pairs for every value v in the set.
         *
         * @returns {IterableIterator<[*, *]>}
         */
        entries: function() {
            return this.data.entries();
        },

        /**
         * Execute a provided function once for each value in the Set object, in insertion order.
         *
         * @param callback
         *
         * @returns {void}
         */
        forEach: function(callback) {
            return this.data.forEach(callback);
        },

        /**
         * Return a boolean indicating whether an element with the specified value exists in a Set object or not.
         *
         * @param value
         *
         * @returns {boolean}
         */
        has: function(value) {
            return this.data.has(value);
        },

        /**
         * Returns an array of values in the set.
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
        }
    };

}(Icinga, jQuery));
