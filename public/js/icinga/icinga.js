/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
define([
    'jquery',
    'logging',
    'icinga/module',
    'icinga/util/async',
    'icinga/container',
    'modules/list'
], function ($, log, moduleMgr, async, containerMgr, modules) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {
        var internalModules = ['icinga/components/actionTable','icinga/components/mainDetail'];

        this.modules     = {};
        var failedModules = [];

        var initialize = function () {
            registerLazyModuleLoading();
            enableInternalModules();
            
            containerMgr.registerAsyncMgr(async);
            containerMgr.initializeContainers(document);
            log.debug("Initialization finished");

            enableModules();
        };
        
        var registerLazyModuleLoading = function() {
            async.registerHeaderListener("X-Icinga-Enable-Module", loadModuleScript, this);
        };

        var enableInternalModules = function() {
            $.each(internalModules,function(idx,module) {
                 moduleMgr.enableModule(module, log.error);
            });
        };

        var loadModuleScript = function(name) {
            console.log("Loading ", name);
            moduleMgr.enableModule("modules/"+name+"/"+name, function(error) {
                failedModules.push({
                    name: name,
                    errorMessage: error
                 });
            });
        };

        var enableModules = function(moduleList) {
            moduleList = moduleList || modules;

            $.each(modules,function(idx,module) {
                loadModuleScript(module.name);
            });
        };


        $(document).ready(initialize.bind(this));

        return {
            /**
             *
             */
            loadModule: function(blubb,bla) {
                behaviour.registerBehaviour(blubb,bla);
            },

            loadIntoContainer: function(ctr) {

            },
        
            loadUrl: function(url, target, params) {
                target = target || "icinga-main";
                async.loadToTarget(target, url, params);
            },
 
            getFailedModules: function() {
                return failedModules;
            }

        };
    };
    return new Icinga();
});


