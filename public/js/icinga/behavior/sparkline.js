/*! Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Sparkline = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };

    Sparkline.prototype = new Icinga.EventListener();

    Sparkline.prototype.onRendered = function(e) {
        $(e.target).find('.sparkline').each(function() {
            $(this).sparkline('html', {
                enableTagOptions: true,
                disableTooltips: true
            });
        });
    };

    Icinga.Behaviors.Sparkline = Sparkline;

})(Icinga, jQuery);
