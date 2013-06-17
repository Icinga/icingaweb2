/**
*   Tools for setting up the casperjs tests
*   mainly setting host, port and path path
**/

// load config files
var fs = require('fs');
var env = require('system').env;
var args = require('system').args;
var utils = require('utils');


var configFile = fs.absolute('./casperjs.config');
var host = null;
var port = null;
var path = null;
var verbose = false;


if (typeof(env.CASPERJS_HOST) === "string")
    host = env.CASPERJS_HOST;
if (typeof(env.CASPERJS_PORT) === "string")
    port  = parseInt(env.CASPERJS_PORT, 10);
if (typeof(env.CASPERJS_PATH) === "string")
    path = env.CASPERJS_PATH;


for (var i=0;i<args.length;i++) {
    switch(args[i]) {
        case '--verbose':
            verbose = true;
            break;
        case '--configFile':
            configFile = args[++i];
            break;
        case '--host':
            host = args[++i];
            break;
        case '--port':
            port = parseInt(args[++i], 10);
            break;
        case '--path':
            path = args[++i];
            break;
    }
}

if (fs.isReadable(configFile)) {
    var cfg = fs.read(configFile);
    try {
        config = JSON.parse(cfg);
        if(host === null)
            host = config.host;
        if(port === null)
            port = parseInt(config.port, 10);
        if(path === null)
            path = config.path;
     } catch(e) {
        console.error("Configuration "+cfg+" is invalid: "+e);
    }
} 


if (host === null)
    throw "Can't initialize tests: No host given in casperjs.config or via CASPERJS_HOST environment";
if (port === null)
    throw "Can't initialize tests: No port given in casperjs.config or via CASPERJS_PORT environment";
if (path === null)
    throw "Can't initialize tests: No path given in casperjs.config or via CASPERJS_PATH environment";


(function() {
    "use strict";
    
    var getBaseURL = function(url) {
        url = url || "";
        if (url.substr(0,4) == "http") {
            return url;
        }
        url = "http://"+host+":"+port+"/"+path+"/"+url;
    };
    var cstart = casper.start; 
    var copen = casper.open; 
    var copenFrom = casper.openFrom; 
    var startFromBase = function(url, then) {
        return cstart.apply(casper,[this.getBaseURL(url), then]);
    };
    
    var thenOpenFromBase = function(url, options) {
        return copen.apply(casper,[this.getBaseURL(url), options]);
    };
    
    var openFromBase = function(url, options) {
        return copenFrom.apply(casper,[this.getBaseURL(url), options]);
    };
    casper.on('remote.message', function(message) {
        console.log(message);
    });

    casper.on('page.error', function(message, trace) {
        console.error(message, JSON.stringify(trace));
    });


    exports.getTestEnv = function() {
        casper.getBaseURL = getBaseURL;
        casper.start = startFromBase;     
        casper.thenOpen = thenOpenFromBase; 
        casper.open = openFromBase; 
        return casper;
    };

    exports.setupRequireJs = function(libraries) {
        if (typeof libraries === "undefined") {
            libraries = {
                jquery: 'vendor/jquery-1.8.3',
                bootstrap: 'vendor/bootstrap.min',
                eve: 'vendor/raphael/eve'
            };
        } else {
            libraries = libraries || {};
            libraries.logging = 'icinga/util/logging';
            libraries.jquery = 'vendor/jquery-1.8.3';
            libraries["modules/list"] = "/moduleMock";
            if (libraries.bootstrap || libraries.icinga) {
                libraries.bootstrap = 'vendor/bootstrap.min';
                libraries.history = 'vendor/history';
                libraries.eve =  'vendor/raphael/eve';
                libraries.raphael = 'vendor/raphael/raphael.amd';
                libraries["raphael.core"] = 'vendor/raphael/raphael.core';
                libraries["raphael.svg"] = 'vendor/raphael/raphael.svg';
                libraries["raphael.vml"] = 'vendor/raphael/raphael.vml';
            }
            if (libraries.ace) {
                libraries.ace = 'vendor/ace/ace';
            }
        }
        var bootstrap = libraries.icinga;
        delete(libraries.icinga);
        requirejs.config({
            baseUrl: window.base_url + '/js',
            paths: libraries       
        });
        if (bootstrap) {

            requirejs(['jquery', 'history']);
            requirejs(['bootstrap']);
            requirejs(['icinga/icinga'], function (Icinga) {
                window.$ = $;
                window.jQuery = $;
                window.Icinga = Icinga;
                window.History = History;
            });
        }
    };
})();


