/*global Icinga:false, $: false, document: false, define:false require:false base_url:false console:false */


/**
 This prototype encapsulates the behaviours registered in the behaviour folder
 **/
(function() {
    "use strict";

    var loaded = {};

    define(['logging'],function(log) {

        var registerBehaviourFunctions = function(behaviour) {
            var enableFn = behaviour.enable, disableFn = behaviour.disable;

            behaviour.enable = (function(root) {
                root = root || document;
                for (var jqMatcher in this.eventHandler) {
                    for (var event in this.eventHandler[jqMatcher]) {
                        log.debug("Registered behaviour: ","'"+event+"'", jqMatcher);
                        $(root).on(event,jqMatcher,this.eventHandler[jqMatcher][event]);
                    }
                }
                if(enableFn) {
                    enableFn.apply(this,arguments);
                }
            }).bind(behaviour);

            behaviour.disable = (function(root) {
                for (var jqMatcher in this.eventHandler) {
                    for (var event in this.eventHandler[jqMatcher]) {
                        log.debug("Unregistered behaviour: ","'"+event+"'", jqMatcher);
                        $(root).off(event,jqMatcher,this.eventHandler[jqMatcher][event]);
                    }
                }
                if (disableFn) {
                    disableFn.apply(this,arguments);
                }
            }).bind(behaviour);


        };

        var CallInterface = function() {

            /**
             * Loads a behaviour and calls successCallback with the behaviour as the parameter on success, otherwise
             * the errorCallback with the errorstring as the first parameter
             *
             * @param name
             *  @param errorCallback
             * @param successCallback
             */
            this.enableBehaviour = function(name,errorCallback,successCallback) {
                require([name],function(behaviour) {
                    if (typeof behaviour.eventHandler === "object") {
                        registerBehaviourFunctions(behaviour);
                    }
                    if (typeof behaviour.enable === "function") {
                        behaviour.enable();
                    }
                    loaded[name] = {
                        behaviour: behaviour,
                        active: true
                    };
                    if (typeof successCallback === "function") {
                        successCallback(behaviour);
                    }
                },function(err) {
                    errorCallback("Could not load behaviour "+name+" "+err,err);
                });
            };

            this.disableBehaviour = function(name) {
                if(loaded[name] && loaded[name].active) {
                    loaded[name].disable();
                }
            };

        };

        return new CallInterface();
    });

})();