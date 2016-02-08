/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function (Object) {

    'use strict';

    Object.keys = Object.keys || function(o) {
        var result = [];
        for(var name in o) {
            if (o.hasOwnProperty(name)) {
                result.push(name);
            }
        }

        return result;
    };

})(Object);

(function (Array) {

    'use strict';
    if (!Array.prototype.indexOf) {
        Array.prototype.indexOf = function(elt) {
            var len = this.length >>> 0;

            var from = Number(arguments[1]) || 0;
            from = (from < 0) ? Math.ceil(from) : Math.floor(from);
            if (from < 0) from += len;

            for (; from < len; from++) {
                if (from in this && this[from] === elt) {
                    return from;
                }
            }
            return -1;
        };
    }
})(Array);

if ('undefined' !== typeof console) { (function (console) {

    'use strict';

    if ('undefined' === console) {
        return;
    }

    /* Fix console for IE9, TBD: test IE8 */
    if (typeof console.log == 'object' && Function.prototype.bind && console) {
        [
            'log',
            'info',
            'warn',
            'error',
            'assert',
            'dir',
            'clear',
            'profile',
            'profileEnd'
        ].forEach(function (method) {
            console[method] = this.call(console[method], console);
        }, Function.prototype.bind);
    }
})(console); }

/* I intentionally moved this here, AFTER console handling */
/* Could be switched, but please take care when doing so   */
if (!Function.prototype.bind) {
    Function.prototype.bind = function (oThis) {
        if (typeof this !== 'function') {
            throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
        }

        var aArgs = Array.prototype.slice.call(arguments, 1),
            fToBind = this,
            fNOP = function () {},
            fBound = function () {
                return fToBind.apply(this instanceof fNOP && oThis
                    ? this
                    : oThis,
                    aArgs.concat(Array.prototype.slice.call(arguments)));
            };

        fNOP.prototype = this.prototype;
        fBound.prototype = new fNOP();

        return fBound;
    };
}

/* jQuery Plugins */
(function ($) {

    'use strict';

    /* Whether a HTML tag has a specific attribute */
    $.fn.hasAttr = function(name) {
        // We have inconsistent behaviour across browsers (false VS undef)
        var val = this.attr(name);
        return typeof val !== 'undefined' && val !== false;
    };

    /* Get class list */
    $.fn.classes = function (callback) {

        var classes = [];

        $.each(this, function (i, el) {
            var c = $(el).attr('class');
            if (typeof c === 'string') {
                $.each(c.split(/\s+/), function(i, p) {
                    if (classes.indexOf(p) === -1) {
                        classes.push(p);
                    }
                });
            }
        });

        if (typeof callback === 'function') {
            for (var i in classes) {
                if (classes.hasOwnProperty(i)) {
                    callback(classes[i]);
                }
            }
        }

        return classes;
    };

    /* Serialize form elements to an object */
    $.fn.serializeObject = function()
    {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

})(jQuery);
