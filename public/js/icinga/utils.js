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

            $.each(params, function (key, value) {
              // We overwrite existing params
              newparams[key] = value;
            });

            if (Object.keys(newparams).length > 0) {
              var queryString = '?';
              $.each(newparams, function (key, value) {
                  if (queryString !== '?') {
                      queryString += '&';
                  }
                  queryString += encodeURIComponent(key) + '=' + encodeURIComponent(value);
              });
              result += queryString;
            }
            if (parts.hash.length > 0) {
                result += '#' + parts.hash;
            }
            return result;
        },

        // Local URLs only
        removeUrlParams: function (url, params) {
            var parts = this.parseUrl(url),
                result = parts.path,
                newparams = parts.params;

            $.each(params, function (idx, key) {
                delete newparams[key];
            });

            if (Object.keys(newparams).length > 0) {
              var queryString = '?';
              $.each(newparams, function (key, value) {
                  if (queryString !== '?') {
                      queryString += '&';
                  }
                  queryString += encodeURIComponent(key) + '=' + encodeURIComponent(value);
              });
              result += queryString;
            }
            if (parts.hash.length > 0) {
                result += '#' + parts.hash;
            }
            return result;
        },

        /**
         * Parse url params
         */
        parseParams: function (a) {
            var params = {},
                segment = a.search.replace(/^\?/,'').split('&'),
                len = segment.length,
                i = 0,
                s;

            for (; i < len; i++) {
                if (!segment[i]) {
                    continue;
                }
                s = segment[i].split('=');
                params[s[0]] = decodeURIComponent(s[1]);
            }
            return params;
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
         * @param   {jQuery}    $node
         *
         * @returns {string}
         */
        getCSSPath: function($node) {
            if (! $node.length) {
                throw 'Requires a node';
            }

            var path = [];

            while (true) {
                var id = $node.attr('id');

                // Ignore forms and form controls because id generation is unreliable :(
                if (typeof id !== 'undefined' && ! $node.is(':input') && ! $node.is('form')) {
                    path.push('#' + id);
                    break;
                }

                var tagName = $node.prop('tagName').toLowerCase();
                //var classes = $node.attr('class');
                //
                //if (classes) {
                //    classes = classes.split(' ').join('.');
                //}

                var $parent = $node.parent();

                if (! $parent.length) {
                    path.push(tagName);
                    break;
                }

                var $siblings = $parent.children(tagName);

                if ($siblings.length > 1) {
                    var index = $siblings.index($node) + 1;
                    path.push(tagName + ':nth-of-type(' + index.toString() + ')');
                } else {
                    path.push(tagName);
                }

                $node = $parent;
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
        }
    };

}(Icinga, jQuery));
