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
var registeredDependencies = {};

/**
*   Mock for the requirejs(dependencyList, callback) function, loads
*   all dependencies that have been registered under the given names
*   in dependencies and calls fn with them as the parameter
*
**/
var requireJsMock = function(dependencies, fn) {
    var fnArgs = [];
    for (var i=0;i<dependencies.length;i++) {
        if (typeof registeredDependencies[dependencies[i]] === "undefined") {
            console.warn("Unknown dependency "+dependencies[i]+" in define()");
        }
        fnArgs.push(registeredDependencies[dependencies[i]]);
    }
    fn.apply(this,fnArgs);
};

/**
*   Mock for the 'define' function of requireJS, behaves exactly the same
*   except that it looks up the dependencies in the list provided by registerDepencies()
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
                if (typeof registerDependencies[argList[i]] === "undefined") {
                    console.warn("Unknown dependency "+argList[i]+" in define()");
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
    GLOBAL.requirejs = requireJsMock;
    GLOBAL.define = defineMock;
    registeredDependencies = {
        'jquery' : GLOBAL.$,
        'logging' : console
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
        '__define__' : registeredDependencies.__define__,
        'logging' : console
    };
}
// helper to log debug messages with console
console.debug = console.log;

/**
*  Registers a name=>object map of dependencies
*  for lookup with requirejs()/define()
**/
function registerDependencies(obj) {
    for(var name in obj) {
        registeredDependencies[name] = obj[name];
    }
}

/**
*   The API for this module
**/
module.exports = {
    purgeDependencies: purgeDependencies,
    registerDependencies: registerDependencies,
    getDefine: function(name) {
        if (typeof arg === "undefined") {
            return registeredDependencies.__define__;
        } else {
            return registeredDependencies[name];
        }
    }
};
