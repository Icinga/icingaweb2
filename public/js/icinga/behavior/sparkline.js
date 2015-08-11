/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Sparkline = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };
    Sparkline.prototype = new Icinga.EventListener();

    Sparkline.prototype.onRendered = function(evt) {
        var el = evt.target;

        $('.sparkline', el).each(function(i, element) {
            // read custom options
            var $spark = $(element);
            var title  = $spark.attr('title');

            if ($spark.attr('labels')) {
                $spark.removeAttr('original-title');
            }

            var options;
            if ($spark.hasClass('sparkline-perfdata')) {
                options = {
                    enableTagOptions: true,
                    width: 16,
                    height: 16,
                    title: title,
                    disableTooltips: true,
                    borderWidth: 1.4,
                    borderColor: '#FFF'
                };
                $spark.sparkline('html', options);
            } else if ($spark.hasClass('sparkline-multi')) {
                options = {
                    width: 100,
                    height: 100,
                    title: title,
                    enableTagOptions: true
                };
                $spark.sparkline('html', options);
            }

        });
    };

    Icinga.Behaviors.Sparkline = Sparkline;

}) (Icinga, jQuery);
