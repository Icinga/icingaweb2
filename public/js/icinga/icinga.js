/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
define([
    'jquery',
    'vendor/jquery.sparkline.min',
    'logging',
    'icinga/behaviour',
    'icinga/util/async',
    'icinga/container',
    'modules/list'
], function ($,sparkline,log,behaviour,async,containerMgr, modules) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {
        var internalBehaviours = ['icinga/behaviour/actionTable','icinga/behaviour/mainDetail'];

        this.modules     = {};
        var failedModules = [];

        var initialize = function () {
            require(['modules/list']);
            enableDefaultBehaviour();

            containerMgr.registerAsyncMgr(async);
            containerMgr.initializeContainers(document);
            log.debug("Initialization finished");

            enableModules();
        };

        var enableDefaultBehaviour = function() {
            $.each(internalBehaviours,function(idx,behaviourImpl) {
                behaviour.enableBehaviour(behaviourImpl,log.error);
            });
        };

        var enableModules = function(moduleList) {
            moduleList = moduleList || modules;

            $.each(modules,function(idx,module) {
                if(module.behaviour) {
                    behaviour.enableBehaviour(module.name+"/"+module.name,function(error) {
                        failedModules.push({name: module.name,errorMessage: error});
                    });
                }
            });
        };

        var enableCb = function(behaviour) {
            behaviour.enable();
        };

        $(document).ready(initialize.bind(this));

        return {
            /**
             *
             */
            loadModule: function(blubb,bla) {
                behaviour.registerBehaviour(blubb,bla);
            } ,

            loadIntoContainer: function(ctr) {

            }

        };
    };
    return new Icinga();
});


