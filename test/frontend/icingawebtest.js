// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
*   Tools for setting up the casperjs tests
*   mainly setting host, port and path path
**/

// load config files
var fs = require('fs');
var env = require('system').env;
var args = require('system').args;
var utils = require('utils');


var host = 'localhost';
var port = 80;
var path = 'icingaweb';
var verbose = false;
var user = 'jdoe';
var pass = 'password';

if (typeof(env.CASPERJS_HOST) === 'string')
    host = env.CASPERJS_HOST;
if (typeof(env.CASPERJS_PORT) === 'string')
    port  = parseInt(env.CASPERJS_PORT, 10);
if (typeof(env.CASPERJS_PATH) === 'string')
    path = env.CASPERJS_PATH;
if (typeof(env.CASPERJS_USER) === 'string')
    user = env.CASPERJS_USER;
if (typeof(env.CASPERJS_PASS) === 'string')
    pass = env.CASPERJS_PASS;

for (var i=0;i<args.length;i++) {
    switch(args[i]) {
        case '--verbose':
            verbose = true;
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

if (host === null) {
    console.error('Can\'t initialize tests: No host given in casperjs.config or via CASPERJS_HOST environment');
    return false;
}
if (port === null) {
    console.error('Can\'t initialize tests: No port given in casperjs.config or via CASPERJS_PORT environment');
    return false;
}
if (path === null) {
    console.error('Can\'t initialize tests: No path given in casperjs.config or via CASPERJS_PATH environment');
    return false;
}
(function() {
    'use strict';

    var getBaseURL = function(url) {
        url = url || '';
        if (url.substr(0,4) == 'http') {
            return url;
        }
        return 'http://'+host+':'+port+'/'+path+'/'+url;
    };
    var cstart = casper.start;
    var cthenOpen = casper.thenOpen;
    var copen = casper.open;


    var startFromBase = function(url, then) {
        return cstart.call(casper, getBaseURL(url), then);
    };

    var thenOpenFromBase = function(url, options) {
        return cthenOpen.apply(casper, [getBaseURL(url), options]);
    };

    var openFromBase = function(url, options) {
        return copen.apply(casper, [getBaseURL(url), options]);
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

    exports.getCredentials = function() {
        return {
            'username'  :   user,
            'password'  :   pass
        };
    };

    exports.performLogin = function() {
        casper.start("/authentication/logout");
        casper.thenOpen("/authentication/login", function() {
            this.fill('form#form_login', icinga.getCredentials());
            this.click('form#form_login input#submit');
        });
    };
})();
