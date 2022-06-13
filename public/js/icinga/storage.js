/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

;(function(Icinga) {

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
         * Storage backend
         *
         * @type {Storage}
         */
        this.backend = window.localStorage;
    };

    /**
     * Callbacks for storage events on particular keys
     *
     * @type {{function}}
     */
    Icinga.Storage.subscribers = {};

    /**
     * Pass storage events to subscribers
     *
     * @param   {StorageEvent}  event
     */
    window.addEventListener('storage', function(event) {
        var url = icinga.utils.parseUrl(event.url);
        if (! url.path.startsWith(icinga.config.baseUrl)) {
            // A localStorage is shared between all paths on the same origin.
            // So we need to make sure it's us who made a change.
            return;
        }

        if (typeof Icinga.Storage.subscribers[event.key] !== 'undefined') {
            var newValue = null,
                oldValue = null;
            if (!! event.newValue) {
                try {
                    newValue = JSON.parse(event.newValue);
                } catch(error) {
                    icinga.logger.error('[Storage] Failed to parse new value (\`' + event.newValue
                        + '\`) for key "' + event.key + '". Error was: ' + error);
                    event.storageArea.removeItem(event.key);
                    return;
                }
            }
            if (!! event.oldValue) {
                try {
                    oldValue = JSON.parse(event.oldValue);
                } catch(error) {
                    icinga.logger.warn('[Storage] Failed to parse old value (\`' + event.oldValue
                        + '\`) of key "' + event.key + '". Error was: ' + error);
                    oldValue = null;
                }
            }

            Icinga.Storage.subscribers[event.key].forEach(function (subscriber) {
                subscriber[0].call(subscriber[1], newValue, oldValue, event);
            });
        }
    });

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
         * Set the storage backend
         *
         * @param {Storage} backend
         */
        setBackend: function(backend) {
            this.backend = backend;
        },

        /**
         * Prefix the given key
         *
         * @param   {string}    key
         *
         * @returns {string}
         */
        prefixKey: function(key) {
            var prefix = 'icinga.';
            if (typeof this.prefix !== 'undefined') {
                prefix = prefix + this.prefix + '.';
            }

            return prefix + key;
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
            this.backend.setItem(this.prefixKey(key), JSON.stringify(value));
        },

        /**
         * Get value for the given key
         *
         * @param   {string}    key
         *
         * @returns {*}
         */
        get: function(key) {
            key = this.prefixKey(key);
            var value = this.backend.getItem(key);

            try {
                return JSON.parse(value);
            } catch(error) {
                icinga.logger.error('[Storage] Failed to parse value (\`' + value
                    + '\`) of key "' + key + '". Error was: ' + error);
                this.backend.removeItem(key);
                return null;
            }
        },

        /**
         * Remove given key from storage
         *
         * @param   {string}    key
         *
         * @returns {void}
         */
        remove: function(key) {
            this.backend.removeItem(this.prefixKey(key));
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
            if (this.backend !== window.localStorage) {
                throw new Error('[Storage] Only the localStorage emits events');
            }

            var prefixedKey = this.prefixKey(key);

            if (typeof Icinga.Storage.subscribers[prefixedKey] === 'undefined') {
                Icinga.Storage.subscribers[prefixedKey] = [];
            }

            Icinga.Storage.subscribers[prefixedKey].push([callback, context]);
        }
    };

    /**
     * Icinga.Storage.StorageAwareMap
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
         * Event listeners for our internal events
         *
         * @type {{}}
         */
        this.eventListeners = {
            'add': [],
            'delete': []
        };

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

        if (!! items && Object.keys(items).length) {
            storage.set(key, items);
        } else if (items !== null) {
            storage.remove(key);
        }

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

            if (storage.backend === window.localStorage) {
                storage.onChange(key, this.onChange, this);
            }

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
         */
        onChange: function(newValue) {
            // Check for deletions first. Uses keys() to iterate over a copy
            this.keys().forEach(function (key) {
                if (newValue === null || typeof newValue[key] === 'undefined') {
                    var value = this.data.get(key)['value'];
                    this.data.delete(key);
                    this.trigger('delete', key, value);
                }
            }, this);

            if (newValue === null) {
                return;
            }

            // Now check for new entries
            Object.keys(newValue).forEach(function(key) {
                var known = this.data.has(key);
                // Always override any known value as we want to keep track of all `lastAccess` changes
                this.data.set(key, newValue[key]);

                if (! known) {
                    this.trigger('add', key, newValue[key]['value']);
                }
            }, this);
        },

        /**
         * Register an event handler to handle storage updates
         *
         * Available events are: add, delete. The callback receives the
         * key and its value as first and second argument, respectively.
         *
         * @param   {string}    event
         * @param   {function}  callback
         * @param   {object}    thisArg
         *
         * @returns {this}
         */
        on: function(event, callback, thisArg) {
            if (typeof this.eventListeners[event] === 'undefined') {
                throw new Error('Invalid event "' + event + '"');
            }

            this.eventListeners[event].push([callback, thisArg]);
            return this;
        },

        /**
         * Trigger all event handlers for the given event
         *
         * @param   {string}    event
         * @param   {string}    key
         * @param   {*}         value
         */
        trigger: function(event, key, value) {
            this.eventListeners[event].forEach(function (handler) {
                var thisArg = handler[1];
                if (typeof thisArg === 'undefined') {
                    thisArg = this;
                }

                handler[0].call(thisArg, key, value);
            });
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
         * @param   {*}         value   Default null
         *
         * @returns {this}
         */
        set: function(key, value) {
            if (typeof value === 'undefined') {
                value = null;
            }

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

            this.data.forEach(function(value, key) {
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

}(Icinga));
