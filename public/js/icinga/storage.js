/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

(function (Icinga, $) {

    'use strict';

    const KEY_TTL = 7776000000; // 90 days (90×24×60×60×1000)

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
        onChange: function(key, callback, context) {
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
        var items = storage.get(key);
        if (typeof items !== 'undefined' && !! items) {
            Object.keys(items).forEach(function(key) {
                var value = items[key];

                if (typeof value !== 'object' || typeof value['lastAccess'] === 'undefined') {
                    items[key] = {'value': value, 'lastAccess': Date.now()};
                } else if (Date.now() - value['lastAccess'] > KEY_TTL) {
                    delete items[key];
                }
            }, this);
        }

        storage.set(key, items);
        return (new Icinga.Storage.StorageAwareMap(items).setStorage(storage, key));
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

            storage.onChange(key, this.onChange, this);
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
         * Update the storage
         *
         * @returns {void}
         */
        updateStorage: function() {
            if (! this.hasStorage()) {
                return;
            }

            if (this.size > 0) {
                this.storage.set(this.key, this.toObject());
            } else {
                this.storage.remove(this.key);
            }
        },

        /**
         * Update the map
         *
         * @param   {object}    newValue
         * @param   {object}    oldValue
         */
        onChange: function(newValue, oldValue) {
            // Check for deletions first. Uses keys() to iterate over a copy
            this.keys().forEach(function (key) {
                if (typeof newValue[key] === 'undefined') {
                    this.data.delete(key);
                    $(window).trigger('StorageAwareMapDelete', key);
                }
            }, this);

            // Now check for new entries
            Object.keys(newValue).forEach(function(key) {
                var known = this.data.has(key);
                // Always override any known value as we want to keep track of all `lastAccess` changes
                this.data.set(key, newValue[key]);

                if (! known) {
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
            this.data.set(key, {'value': value, 'lastAccess': Date.now()});

            this.updateStorage();
            return this;
        },

        /**
         * Remove all key/value pairs from the map
         *
         * @returns {void}
         */
        clear: function() {
            this.data.clear();
            this.updateStorage();
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

            this.updateStorage();
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
                this.data.forEach(function (value, key) {
                    list.push([key, value['value']]);
                });
            }

            return list;
        },

        /**
         * Execute a provided function once for each item in the map, in insertion order
         *
         * @param   {function}  callback
         * @param   {object}    thisArg
         *
         * @returns {void}
         */
        forEach: function(callback, thisArg) {
            if (typeof thisArg === 'undefined') {
                thisArg = this;
            }

            return this.data.forEach(function(value, key) {
                callback.call(thisArg, value['value'], key);
            });
        },

        /**
         * Return the value associated to the key, or undefined if there is none
         *
         * @param   {string}    key
         *
         * @returns {*}
         */
        get: function(key) {
            var value = this.data.get(key)['value'];
            this.set(key, value); // Update `lastAccess`

            return value;
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
                this.data.forEach(function(_, key) {
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
                this.data.forEach(function(value) {
                    list.push(value['value']);
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
                this.data.forEach(function (value, key) {
                    obj[key] = value;
                });
            }

            return obj;
        }
    };

}(Icinga, jQuery));
