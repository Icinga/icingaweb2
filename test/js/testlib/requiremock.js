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
* {{LICENSE_HEADER}}
* {{LICENSE_HEADER}}
**/

/**
*   This node module acts as a mock for requirejs and allows you to 
*   define your own dependencies in your tests. It also removes the 
*   asynchronous character of dependency loading, so you don't need
*   to handle everthing in the callbacks.
*
*   Per default it resolves the 'logging' dependency by routing it 
*   to console.
*  
**/
var path = require('path');
var registeredDependencies = {};

/**
*   Mock for the requirejs(dependencyList, callback) function, loads
*   all dependencies that have been registered under the given names
*   in dependencies and calls fn with them as the parameter
*
**/
var debug = false;
var requireJsMock = function(dependencies, fn) {
    var fnArgs = [];
    for (var i=0;i<dependencies.length;i++) {
        if (typeof registeredDependencies[dependencies[i]] === "undefined") {
            if (debug === true)
                console.warn("Unknown dependency "+dependencies[i]+" in define()");
        }
        fnArgs.push(registeredDependencies[dependencies[i]]);
    }
    fn.apply(this,fnArgs);
};

/**
 * Mock the Logger
 */
var logger = {
    debug: function() {},
    warn: function() {},
    error: function() {},
    emergency: function() {}
};

/**
*   Mock for the 'define' function of requireJS, behaves exactly the same
*   except that it looks up the dependencies in the list provided by registerDependencies()
*   A module that hasn't been defined with a name can be fetched with getDefined() (without parameter)
*
**/
var defineMock = function() { 
    var fn = function() {}, 
        fnargs = [],
        currentArg = 0,
        scopeName = '__define__';
    do {
        var argType = typeof arguments[currentArg];
        if( argType === "string") {
            scopeName = arguments[currentArg];
            currentArg++;
            continue;
        } else if (argType === "function") {
            fn = arguments[currentArg];
        } else if (Array.isArray(arguments[currentArg])) {
            var argList = arguments[currentArg];
            fn = arguments[currentArg+1];
            for (var i=0;i<argList.length;i++) {
                if (typeof registerDependencies[argList[i]] === "undefined" && debug) {
//                    console.warn("Unknown dependency "+argList[i]+" in define()");
                }
                
                fnargs.push(registeredDependencies[argList[i]]);
            }
        }
        break;
    } while(true);
    registeredDependencies[scopeName] = fn.apply(this,fnargs);
};

/**
*   Called on module initialisation, will register the 
*   requirejs, define and jquery '$' methods globally
*   and also purge any module-global dependencies
*
**/
function initRequireMethods() {
    GLOBAL.$ = require('jquery');
    GLOBAL.jQuery = GLOBAL.$;
    GLOBAL.requirejs = requireJsMock;
    GLOBAL.define = defineMock;
    registeredDependencies = {
        'jquery' : GLOBAL.$,
        'logging' : logger
    };
}
initRequireMethods();

/**
*   Resets all additional dependencies, i.e. all dependencies
*   without a name
**/
function purgeDependencies() {
    registeredDependencies = {
        'jquery' : GLOBAL.$,
        'logging' : logger
    };
}
// helper to log debug messages with console
console.debug = function() {};

/**
*  Registers a name=>object map of dependencies
*  for lookup with requirejs()/define()
**/
function registerDependencies(obj) {
    for(var name in obj) {
        registeredDependencies[name] = obj[name];
    }
}
var base = path.normalize(__dirname+"../../../../public/js");
GLOBAL.requireNew = function(key) {
    key = path.normalize(base+"/"+key);
    delete require.cache[key];
    return require(key);
}; 

/**
*   The API for this module
**/
module.exports = {
    purgeDependencies: purgeDependencies,
    registerDependencies: registerDependencies,
    getDefine: function(name) {
        if (typeof name === "undefined") {
            return registeredDependencies.__define__;
        } else {
            return registeredDependencies[name];
        }
    }
};

