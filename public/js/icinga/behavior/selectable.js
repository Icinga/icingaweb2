/*! Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {
    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Select all contents from the target of the given event
     *
     * @param {object} e Event
     */
    function onSelect(e) {
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

    /**
     * Behavior for text that is selectable via double click
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var Selectable = function(icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };

    $.extend(Selectable.prototype, new Icinga.EventListener(), {
        onRendered: function(e) {
            $(e.target).find('.selectable').on('dblclick', onSelect);
        }
    });

    Icinga.Behaviors.Selectable = Selectable;

})(Icinga, jQuery);
