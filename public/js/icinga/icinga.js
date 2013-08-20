/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
define([
    'jquery',
    'logging',
    'icinga/util/async',
    'icinga/componentLoader'
], function ($, log, async,components) {
    'use strict';

    /**
     * Icinga prototype
     */
    var Icinga = function() {

        var initialize = function () {
            components.load();
            log.debug("Initialization finished");
        };

        $(document).ready(initialize.bind(this));

        return {
            components: components
        };
    };
    return new Icinga();
});
