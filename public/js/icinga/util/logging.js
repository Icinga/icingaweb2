/*global Icinga:false, document: false, define:false require:false base_url:false console:false, window:false */

define(function() {
    'use strict';

    function logTagged(tag, logArgs) {
        var now = new Date();
        var ms = now.getMilliseconds() + '';
        while (ms.length < length) {
            ms = '0' + ms;
        }
        logArgs = [].slice.call(logArgs);
        logArgs.unshift(now.toLocaleTimeString() + '.' + ms);

        var args = [tag.toUpperCase() + ' :'];
        for (var el in logArgs) {
            args.push(logArgs[el]);
        }

        try {
            if (console[tag]) {

                console[tag].apply(console,logArgs);
            } else {
                console.log.apply(console,args);
            }

        } catch (e) { // IE fallback
            console.log(logArgs);
        }
    }

    if(!window.console) {
        window.console = { log: function() {} };
    }
    var features = {
        debug: function() {
            if (!window.ICINGA_DEBUG) {
                return;
            }
            logTagged('debug', arguments);
        },
        warn: function() {
            logTagged('warn', arguments);
        },
        error: function() {
            logTagged('error', arguments);
        },
        emergency: function() {
            logTagged('emergency', arguments);
            // TODO: log *emergency* errors to the backend
        }
    };

    return features;
});
