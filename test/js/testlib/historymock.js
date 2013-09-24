/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

var URI = require('URIjs');

(function() {
    GLOBAL.window = {
        location: {
            href: 'http://localhost/icinga2-web/testcase',
            pathname: '/icinga2-web/testcase',
            query: '',
            hash: '',
            host: 'localhost',
            protocol: 'http'
        }
    };
    "use strict";

    var states = [];


    /**
     * Api for setting the window URL
     *
     * @param {string} url      The new url to use for window.location
     */
    window.setWindowUrl = function(url) {
        var url = URI(url);
        window.location.protocol = url.protocol();
        window.location.pathname = url.pathname();
        window.location.query = url.query();
        window.location.search = url.search();
        window.location.hash = url.hash();
        window.location.href = url.href();
    };

    /**
     * Mock for the History API
     *
     * @type {{pushState: Function, popState: Function, replaceState: Function, clear: Function}}
     */
    module.exports = {
        pushState: function(state, title, url) {
            window.setWindowUrl(url);
            states.push(arguments);
        },
        popState: function() {
            return states.pop();
        },
        replaceState: function(state, title, url) {
            states.pop();
            window.setWindowUrl(url);
            states.push(arguments);
        },
        clearState: function() {
            states = [];
        },
        getState: function() {
            return states;
        }
    };
})();