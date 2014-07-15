// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
        isVisible: function(element)
        {
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
         * Cleanup
         */
        destroy: function () {
            this.urlHelper = null;
            this.icinga = null;
        }
    };

}(Icinga, jQuery));
