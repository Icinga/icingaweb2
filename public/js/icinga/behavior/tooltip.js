// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Tooltip = function () {
        this.mouseX = 0;
        this.mouseY = 0;
    };

    Tooltip.prototype.apply = function(el) {
        var self = this;

        $('[title]').each(function () {
            var $el = $(this);
            $el.attr('title', $el.data('title-rich') || $el.attr('title'));
        });
        $('svg rect.chart-data[title]', el).tipsy({ gravity: 'se', html: true });
        $('.historycolorgrid a[title]', el).tipsy({ gravity: 's', offset: 2 });
        $('img.icon[title]', el).tipsy({ gravity: $.fn.tipsy.autoNS, offset: 2 });
        $('[title]', el).tipsy({ gravity: $.fn.tipsy.autoNS, delayIn: 500 });

        // migrate or remove all orphaned tooltips
        $('.tipsy').each(function () {
            var arrow = $('.tipsy-arrow', this)[0];
            if (!Icinga.utils.elementsOverlap(arrow, $('#main')[0])) {
                $(this).remove();
                return;
            }
            if (!Icinga.utils.elementsOverlap(arrow, el)) {
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

    Tooltip.prototype.bind = function() {
        var self = this;
        $(document).on('mousemove', function (event) {
            self.mouseX = event.pageX;
            self.mouseY = event.pageY;
        });
    };

    Tooltip.prototype.unbind = function() {
        $(document).off('mousemove');
    };

    // Export
    Icinga.Behaviors.Tooltip = Tooltip;

}) (Icinga, jQuery);
