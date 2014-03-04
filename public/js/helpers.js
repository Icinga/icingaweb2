
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
