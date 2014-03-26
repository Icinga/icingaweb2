
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

(function (console) {

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
})(console);

/* jQuery Plugins */
(function ($) {

    'use strict';

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
