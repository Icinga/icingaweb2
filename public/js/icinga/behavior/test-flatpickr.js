/*! Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Flatpickr = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered);
    };

    Flatpickr.prototype = new Icinga.EventListener();

    Flatpickr.prototype.onRendered = function (e) {
        var input = document.querySelectorAll("input[type=datetime-local]");

        if (typeof $().flatpickr === 'function') {
            $(e.target).find('.flatpickr').each(function () {

                $(this).flatpickr(input, {
                    dateFormat: "Y-m-d\TH:i:s",
                    enableTime: true
                });
            });
        }
    };

    Icinga.Behaviors.Flatpickr = Flatpickr;

})(Icinga, jQuery);
