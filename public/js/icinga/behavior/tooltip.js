/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Tooltip = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.mouseX = 0;
        this.mouseY = 0;
        this.on('mousemove', this.onMousemove, this);
        this.on('rendered', this.onRendered, this);
    };
    Tooltip.prototype = new Icinga.EventListener();

    Tooltip.prototype.onMousemove = function(event) {
        event.data.self.mouseX = event.pageX;
        event.data.self.mouseY = event.pageY;
    };

    Tooltip.prototype.onRendered = function(evt) {
        var self = evt.data.self, icinga = evt.data.icinga, el = evt.target;

        $('[title]', el).each(function () {
            var $el = $(this);
            $el.attr('title', $el.data('title-rich') || $el.attr('title'));
        });
        $('svg .chart-data', el).tipsy({ gravity: 'se', html: true });
        $('i[title]', el).tipsy({ gravity: $.fn.tipsy.autoNS, offset: 2 });
        $('[title]', el).each(function (i, el) {
           var $el = $(el);
           var delay, gravity;
           if ($el.data('tooltip-delay') !== undefined) {
               delay = $el.data('tooltip-delay');
           }
           if ($el.data('tooltip-gravity')) {
               gravity = $el.data('tooltip-gravity');
           }
           if (delay === undefined &&
               gravity === undefined &&
               !$el.data('title-rich')) {
               // use native tooltips for everything that doesn't
               // require specific behavior or html markup
               return;
           }
           delay = delay === undefined ? 500 : delay;
           $el.tipsy({
               gravity: gravity || $.fn.tipsy.autoNS,
               delayIn: delay
           });
        });

        // migrate or remove all orphaned tooltips
        $('.tipsy').each(function () {
            var arrow = $('.tipsy-arrow', this)[0];
            if (!icinga.utils.elementsOverlap(arrow, $('#main')[0])) {
                $(this).remove();
                return;
            }
            if (!icinga.utils.elementsOverlap(arrow, el)) {
                return;
            }
            var title = $(this).find('.tipsy-inner').html();
            var atMouse = document.elementFromPoint(self.mouseX, self.mouseY);
            var nearestTip = $(atMouse).closest('[original-title="' + title + '"]')[0];
            if (nearestTip) {
                var tipsy = $.data(nearestTip, 'tipsy');
                tipsy.$tip = $(this);
                $.data(this, 'tipsy-pointee', nearestTip);
            } else {
                // doesn't match delete
                $(this).remove();
            }
        });
    };

    // Export
    Icinga.Behaviors.Tooltip = Tooltip;

}) (Icinga, jQuery);
