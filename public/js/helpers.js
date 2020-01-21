/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

/* jQuery Plugins */
(function ($) {

    'use strict';

    /* Get data value or default */
    $.fn.getData = function (name, fallback) {
        var value = this.data(name);
        if (typeof value !== 'undefined') {
            return value;
        }

        return fallback;
    };

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

    $.fn.offsetTopRelativeTo = function($ancestor) {
        if (typeof $ancestor === 'undefined') {
            return false;
        }

        var el = this[0];
        var offset = el.offsetTop;
        var $parent = $(el.offsetParent);

        if ($parent.is('body') || $parent.is($ancestor)) {
            return offset;
        }

        if (el.tagName === 'TR') {
            // TODO: Didn't found a better way, this will probably break sooner or later
            return $parent.offsetTopRelativeTo($ancestor);
        }

        return offset + $parent.offsetTopRelativeTo($ancestor);
    };

})(jQuery);
