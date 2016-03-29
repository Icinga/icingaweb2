/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    'use strict';

    /**
     * Get the maximum timezone offset
     *
     * @returns {Number}
     */
    Date.prototype.getStdTimezoneOffset = function() {
        if (Date.maxTimezoneOffset !== undefined) {
            return Date.maxTimezoneOffset;
        }

        var year = new Date().getYear();
        var previousOffset;

        for (var i=0; i<12; i++) {
            var d = new Date(year, i, 1);
            if (previousOffset !== undefined) {
                previousOffset = Math.max(previousOffset, d.getTimezoneOffset());
            } else {
                previousOffset = d.getTimezoneOffset();
            }
        }

        Date.maxTimezoneOffset = previousOffset;

        return Date.maxTimezoneOffset;
    };

    /**
     * Test for daylight saving time zone
     *
     * @returns {boolean}
     */
    Date.prototype.isDst = function() {
        return (this.getStdTimezoneOffset() === this.getTimezoneOffset()) ? false : true;
    };

    /**
     * Write timezone information into a cookie
     *
     * @constructor
     */
    Icinga.Timezone = function() {
        this.cookieName = 'icingaweb2-tzo';
    };

    Icinga.Timezone.prototype = {
        /**
         * Initialize interface method
         */
        initialize: function () {
            this.writeTimezone();
        },

        destroy: function() {
            // PASS
        },

        /**
         * Write timezone information into cookie
         */
        writeTimezone: function() {
            var date = new Date();
            var timezoneOffset = (date.getTimezoneOffset()*60) * -1;
            var dst = date.isDst();

            if (this.readCookie(this.cookieName)) {
                return;
            }

            this.writeCookie(this.cookieName, timezoneOffset + '-' + Number(dst), 1);
        },

        /**
         * Write cookie data
         *
         * @param {String} name
         * @param {String} value
         * @param {Number} days
         */
        writeCookie: function(name, value, days) {
            var expires = '';

            if (days) {
                var date = new Date();
                date.setTime(date.getTime()+(days*24*60*60*1000));
                var expires = '; expires=' + date.toGMTString();
            }
            document.cookie = name + '=' + value + expires + '; path=/';
        },

        /**
         * Read cookie data
         *
         * @param {String} name
         * @returns {*}
         */
        readCookie: function(name) {
            var nameEq = name + '=';
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') {
                    c = c.substring(1,c.length);
                }
                if (c.indexOf(nameEq) == 0) {
                    return c.substring(nameEq.length,c.length);
                }
            }
            return null;
        }
    };

})(Icinga, jQuery);
