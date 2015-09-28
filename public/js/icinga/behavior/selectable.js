/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {
    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Selectable = function(icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };

    $.extend(Selectable.prototype, new Icinga.EventListener(), {
        onRendered: function(e) {
            $('.selectable', e.target).on('dblclick', e.data.self.selectText);
        },

        selectText: function(e) {
            var b = document.body,
                r;
            if (b.createTextRange) {
                r = b.createTextRange();
                r.moveToElementText(e.target);
                r.select();
            } else if (window.getSelection) {
                var s = window.getSelection();
                r = document.createRange();
                r.selectNodeContents(e.target);
                s.removeAllRanges();
                s.addRange(r);
            }
        }
    });

    Icinga.Behaviors.Selectable = Selectable;
})(Icinga, jQuery);
