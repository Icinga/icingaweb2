/*! Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

(function(Icinga) {

    var Doc = function(module) {
        this.module = module;
        this.initialize();
        this.module.icinga.logger.debug('Doc module loaded');
    };

    Doc.prototype = {

        initialize: function()
        {
            this.module.on('rendered',     this.rendered);
            this.module.icinga.logger.debug('Doc module initialized');
        },

        rendered: function(event) {
            var $container = $(event.currentTarget);
            if ($('> .content.styleguide', $container).length) {
                $container.removeClass('module-doc');
            }
        }
    };

    Icinga.availableModules.doc = Doc;

}(Icinga));

