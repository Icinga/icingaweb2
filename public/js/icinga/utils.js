/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/**
 * Icinga utility functions
 */
(function(Icinga, $) {

    'use strict';

    Icinga.Utils = function (icinga) {

        /**
         * Utility functions may need access to their Icinga instance
         */
        this.icinga = icinga;

        /**
         * We will use this to create an URL helper only once
         */
        this.urlHelper = null;
    };

    Icinga.Utils.prototype = {

        timeWithMs: function (now) {

            if (typeof now === 'undefined') {
                now = new Date();
            }

            var ms = now.getMilliseconds() + '';
            while (ms.length < 3) {
                ms = '0' + ms;
            }

            return now.toLocaleTimeString() + '.' + ms;
        },

        timeShort: function (now) {

            if (typeof now === 'undefined') {
                now = new Date();
            }

            return now.toLocaleTimeString().replace(/:\d{2}$/, '');
        },

        formatHHiiss: function (date) {
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            if (hours < 10) hours = '0' + hours;
            if (minutes < 10) minutes = '0' + minutes;
            if (seconds < 10) seconds = '0' + seconds;
            return hours + ':' + minutes + ':' + seconds;
        },

        /**
         * Format the given byte-value into a human-readable string
         *
         * @param   {number}    The amount of bytes to format
         * @returns {string}    The formatted string
         */
        formatBytes: function (bytes) {
            var log2  = Math.log(bytes) / Math.LN2;
            var pot   = Math.floor(log2 / 10);
            var unit  = (['b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB'])[pot];
            return ((bytes / Math.pow(1024, pot)).toFixed(2)) + ' ' + unit;
        },

        /**
        * Return whether the given element is visible in the users view
        *
        * Borrowed from: http://stackoverflow.com/q/487073
        *
        * @param   {selector}   element     The element to check
        * @returns {Boolean}
        */
        isVisible: function(element) {
            var $element = $(element);
            if (!$element.length) {
                return false;
            }

            var docViewTop = $(window).scrollTop();
            var docViewBottom = docViewTop + $(window).height();
            var elemTop = $element.offset().top;
            var elemBottom = elemTop + $element.height();

            return ((elemBottom >= docViewTop) && (elemTop <= docViewBottom) &&
                 (elemBottom <= docViewBottom) && (elemTop >= docViewTop));
        },

        getUrlHelper: function () {
            if (this.urlHelper === null) {
                this.urlHelper = document.createElement('a');
            }

            return this.urlHelper;
        },

        /**
         * Parse a given Url and return an object
         */
        parseUrl: function (url) {

            var a = this.getUrlHelper();
            a.href = url;

            var result = {
                source  : url,
                protocol: a.protocol.replace(':', ''),
                host    : a.hostname,
                port    : a.port,
                query   : a.search,
                file    : (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
                hash    : a.hash.replace('#',''),
                path    : a.pathname.replace(/^([^\/])/,'/$1'),
                relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
                segments: a.pathname.replace(/^\//,'').split('/'),
                params  : this.parseParams(a)
            };
            a = null;

            return result;
        },

        // Local URLs only
        addUrlParams: function (url, params) {
            var parts = this.parseUrl(url),
                result = parts.path,
                newparams = parts.params;

            // We overwrite existing params
            $.each(params, function (key, value) {
                key = encodeURIComponent(key);
                value = typeof value !== 'string' || !! value ? encodeURIComponent(value) : null;

                var found = false;
                for (var i = 0; i < newparams.length; i++) {
                    if (newparams[i].key === key) {
                        newparams[i].value = value;
                        found = true;
                        break;
                    }
                }

                if (! found) {
                    newparams.push({ key: key, value: value });
                }
            });

            if (newparams.length) {
                result += '?' + this.buildQuery(newparams);
            }

            if (parts.hash.length) {
                result += '#' + parts.hash;
            }

            return result;
        },

        // Local URLs only
        removeUrlParams: function (url, params) {
            var parts = this.parseUrl(url),
                result = parts.path,
                newparams = parts.params;

            $.each(params, function (_, key) {
                key = encodeURIComponent(key);

                for (var i = 0; i < newparams.length; i++) {
                    if (newparams[i].key === key) {
                        newparams.splice(i, 1);
                        return;
                    }
                }
            });

            if (newparams.length) {
                result += '?' + this.buildQuery(newparams);
            }

            if (parts.hash.length) {
                result += '#' + parts.hash;
            }

            return result;
        },

        /**
         * Return a query string for the given params
         *
         * @param {Array} params
         * @return {string}
         */
        buildQuery: function (params) {
            var query = '';

            for (var i = 0; i < params.length; i++) {
                if (!! query) {
                    query += '&';
                }

                query += params[i].key;
                switch (params[i].value) {
                    case true:
                        break;
                    case false:
                        query += '=0';
                        break;
                    case null:
                        query += '=';
                        break;
                    default:
                        query += '=' + params[i].value;
                }
            }

            return query;
        },

        /**
         * Parse url params
         */
        parseParams: function (a) {
            var params = [],
                segment = a.search.replace(/^\?/,'').split('&'),
                len = segment.length,
                i = 0,
                key,
                value,
                equalPos;

            for (; i < len; i++) {
                if (! segment[i]) {
                    continue;
                }

                equalPos = segment[i].indexOf('=');
                if (equalPos !== -1) {
                    key = segment[i].slice(0, equalPos);
                    value = segment[i].slice(equalPos + 1);
                } else {
                    key = segment[i];
                    value = true;
                }

                params.push({ key: key, value: value });
            }

            return params;
        },

        /**
         * Add the specified flag to the given URL
         *
         * @param {string} url
         * @param {string} flag
         *
         * @returns {string}
         */
        addUrlFlag: function (url, flag) {
            var pos = url.search(/#(?!!)/);

            if (url.indexOf('?') !== -1) {
                flag = '&' + flag;
            } else {
                flag = '?' + flag;
            }

            if (pos === -1) {
                return url + flag;
            }

            return url.slice(0, pos) + flag + url.slice(pos);
        },

        /**
         * Check whether two HTMLElements overlap
         *
         * @param a {HTMLElement}
         * @param b {HTMLElement}
         *
         * @returns {Boolean}      whether elements overlap, will return false when one
         *                         element is not in the DOM
         */
        elementsOverlap: function(a, b)
        {
            // a bounds
            var aoff = $(a).offset();
            if (!aoff) {
                return false;
            }
            var at = aoff.top;
            var ah = a.offsetHeight || (a.getBBox && a.getBBox().height);
            var al = aoff.left;
            var aw = a.offsetWidth || (a.getBBox && a.getBBox().width);

            // b bounds
            var boff = $(b).offset();
            if (!boff) {
                return false;
            }
            var bt = boff.top;
            var bh = b.offsetHeight || (b.getBBox && b.getBBox().height);
            var bl = boff.left;
            var bw = b.offsetWidth || (b.getBBox && b.getBBox().width);

            return !(at > (bt + bh) || bt > (at + ah)) && !(bl  > (al + aw) || al > (bl + bw));
        },

        /**
         * Create a selector that can be used to fetch the element the same position in the DOM-Tree
         *
         * Create the path to the given element in the DOM-Tree, comparable to an X-Path. Climb the
         * DOM tree upwards until an element with an unique ID is found, this id is used as the anchor,
         * all other elements will be addressed by their position in the parent.
         *
         * @param   {HTMLElement} el    The element to extract the path for.
         *
         * @returns {Array}             The path of the element, that can be passed to getElementByPath
         */
        getDomPath: function (el) {
            if (! el) {
                return [];
            }
            if (el.id !== '') {
                return ['#' + el.id];
            }
            if (el === document.body) {
                return ['body'];
            }

            var siblings = el.parentNode.childNodes;
            var index = 0;
            for (var i = 0; i < siblings.length; i ++) {
                if (siblings[i].nodeType === 1) {
                    index ++;
                }

                if (siblings[i] === el) {
                    var p = this.getDomPath(el.parentNode);
                    p.push(':nth-child(' + (index) + ')');
                    return p;
                }
            }
        },

        /**
         * Get the CSS selector to the given node
         *
         * @param {HTMLElement} element
         *
         * @returns {string}
         */
        getCSSPath: function(element) {
            if (typeof element === 'undefined') {
                throw 'Requires a element';
            }

            if (typeof element.jquery !== 'undefined') {
                if (! element.length) {
                    throw 'Requires a element';
                }

                element = element[0];
            }

            var path = [];

            while (true) {
                var id = element.getAttribute("id");

                // Only use ids if they're truly unique
                // TODO: The check used to use document.querySelectorAll, but this resulted in many issues with ids
                //       that start with a decimal. jQuery seems to escape those correctly, so this is the only reason
                //       why it's still.. jQuery.
                if (!! id && $('* #' + id).length === 1) {
                    path.push('#' + id);
                    break;
                }

                var tagName = element.tagName;
                var parent = element.parentElement;

                if (! parent) {
                    path.push(tagName.toLowerCase());
                    break;
                }

                if (parent.children.length) {
                    var index = 0;
                    do {
                        if (element.tagName === tagName) {
                            index++;
                        }
                    } while ((element = element.previousElementSibling));

                    path.push(tagName.toLowerCase() + ':nth-of-type(' + index + ')');
                } else {
                    path.push(tagName.toLowerCase());
                }

                element = parent;
            }

            return path.reverse().join(' > ');
        },

        /**
         * Climbs up the given dom path and returns the element
         *
         * This is the counterpart
         *
         * @param   path    {Array}         The selector
         * @returns         {HTMLElement}   The corresponding element
         */
        getElementByDomPath: function (path) {
            var $element;
            $.each(path, function (i, selector) {
                if (! $element) {
                    $element = $(selector);
                } else {
                    $element = $element.children(selector).first();
                    if (! $element[0]) {
                        return false;
                    }
                }
            });
            return $element[0];
        },

        objectKeys: Object.keys || function (obj) {
            var keys = [];
            $.each(obj, function (key) {
                keys.push(key);
            });
            return keys;
        },

        objectsEqual: function equals(obj1, obj2) {
            var obj1Keys = Object.keys(obj1);
            var obj2Keys = Object.keys(obj2);
            if (obj1Keys.length !== obj2Keys.length) {
                return false;
            }

            return obj1Keys.concat(obj2Keys)
                .every(function (key) {
                    return obj1[key] === obj2[key];
                });
        },

        arraysEqual: function (array1, array2) {
            if (array1.length !== array2.length) {
                return false;
            }

            var value1, value2;
            for (var i = 0; i < array1.length; i++) {
                value1 = array1[i];
                value2 = array2[i];

                if (typeof value1 === 'object') {
                    if (typeof value2 !== 'object' || ! this.objectsEqual(value1, value2)) {
                        return false;
                    }
                } else if (value1 !== value2) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Cleanup
         */
        destroy: function () {
            this.urlHelper = null;
            this.icinga = null;
        },

        /**
         * Encode the parenthesis too
         *
         * @param str {String} A component of a URI
         *
         * @returns {String} Encoded component
         */
        fixedEncodeURIComponent: function (str) {
            return encodeURIComponent(str).replace(/[()]/g, function(c) {
                return '%' + c.charCodeAt(0).toString(16);
            });
        },

        escape: function (str) {
            return String(str).replace(
                /[&<>"']/gm,
                function (c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[c];
                }
            );
        },

        /**
         * Pad a string with another one
         *
         * @param   {String}    str         the string to pad
         * @param   {String}    padding     the string to use for padding
         * @param   {Number}    minLength   the minimum length of the result
         *
         * @returns {String}    the padded string
         */
        padString: function(str, padding, minLength) {
            str = String(str);
            padding = String(padding);
            while (str.length < minLength) {
                str = padding + str;
            }
            return str;
        },

        /**
         * Shuffle a string
         *
         * @param   {String}    str     The string to shuffle
         *
         * @returns {String}    The shuffled string
         */
        shuffleString: function(str) {
            var a = str.split(""),
                n = a.length;

            for(var i = n - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var tmp = a[i];
                a[i] = a[j];
                a[j] = tmp;
            }
            return a.join("");
        },

        /**
         * Generate an id
         *
         * @param   {Number}    len     The desired length of the id
         *
         * @returns {String}    The id
         */
        generateId: function(len) {
            return this.shuffleString('abcefghijklmnopqrstuvwxyz').substr(0, len);
        }
    };

}(Icinga, jQuery));
