/*global Icinga:false, $: false, document: false, define:false requirejs:false base_url:false console:false */

/**
 This prototype encapsulates the modules registered in the module folder
 **/
(function() {
    "use strict";

    var loaded = {};

    define(['logging'],function(log) {

        var registerModuleFunctions = function(module) {
            var enableFn = module.enable, disableFn = module.disable;
            
            module.enable = (function(root) {
                root = root || $('body');
                for (var jqMatcher in this.eventHandler) {
                    for (var ev in this.eventHandler[jqMatcher]) {
                        log.debug("Registered module: ", "'"+ev+"'", jqMatcher);
                        $(root).on(ev,jqMatcher,this.eventHandler[jqMatcher][ev]);
                    }
                }
                if(enableFn) {
                    enableFn.apply(this,arguments);
                }
            }).bind(module);

            module.disable = (function(root) {
                root = root || $('body');
                for (var jqMatcher in this.eventHandler) {
                    for (var ev in this.eventHandler[jqMatcher]) {
                        log.debug("Unregistered module: ", "'"+ev+"'", jqMatcher);
                        $(root).off(ev,jqMatcher,this.eventHandler[jqMatcher][ev]);
                    }
                }
                if (disableFn) {
                    disableFn.apply(this,arguments);
                }
            }).bind(module);


        };

        var CallInterface = function() {

            /**
             * Loads a module and calls successCallback with the module as the parameter on success, otherwise
             * the errorCallback with the errorstring as the first parameter
             *
             * @param name
             *  @param errorCallback
             * @param successCallback
             */
            this.enableModule = function(name,errorCallback,successCallback) {
                requirejs([name],function(module) {
                    if (typeof module === "undefined") {
                        return errorCallback(new Error("Unknown module: "+name));
                    }
                        
                    if (typeof module.eventHandler === "object") {
                        registerModuleFunctions(module);
                    }
                    if (typeof module.enable === "function") {
                        module.enable();
                    }
                    loaded[name] = {
                        module: module,
                        active: true
                    };
                    if (typeof successCallback === "function") {
                        successCallback(module);
                    }
                },function(err) {
                    errorCallback("Could not load module "+name+" "+err,err);
                });
            };

            this.disableModule = function(name) {
                if(loaded[name] && loaded[name].active) {
                    loaded[name].module.disable();
                }
            };
            
            /**
            * This should *ONLY* be called in testcases
            **/
            this.resetHard = function() {
                if (typeof describe !== "function") {
                    return; 
                }
                loaded = {};
            };
        };

        
        return new CallInterface();
    });

})();
