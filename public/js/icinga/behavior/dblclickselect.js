/*! Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var DblClickSelect = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };
    DblClickSelect.prototype = new Icinga.EventListener();

    DblClickSelect.prototype.onRendered = function(evt) {
        $(evt.target).on('dblclick', '.dblclickselect', function() { $(this).selectText(); });
    };

    /**
     * extend jQuery with a selectText function
     *
     * This function will create a browser selection of the choosen DOM object.
     */
    $.fn.selectText = function() {
        if (this.length === 0) return;
        var e = this[0];

        var b = document.body, r;
        if (b.createTextRange) {
            r = b.createTextRange();
            r.moveToElementText(e);
            r.select();
        } else if (window.getSelection) {
            var s = window.getSelection();
            r = document.createRange();
            r.selectNodeContents(e);
            s.removeAllRanges();
            s.addRange(r);
        }
    };

    // Export
    Icinga.Behaviors.DblClickSelect = DblClickSelect;

}) (Icinga, jQuery);
