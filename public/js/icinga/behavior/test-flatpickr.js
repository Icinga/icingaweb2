/*! Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Flatpickr = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered);
    };

    Flatpickr.prototype = new Icinga.EventListener();

    Flatpickr.prototype.onRendered = function (event) {
        var $el = $(this);
        var selector = document.querySelectorAll("input[type=datetime-local]");
        var $container = $('<flatpickr>');
        var data = $el.find(selector);
        var options = {
            appendTo: $container[0],
            dateFormat: 'Y-m-d\TH:i:s',
            wrap: true,
        };

        // flatpickr(selector, options);

        if (typeof $().flatpickr === 'function') {
            event.target.insertAdjacentElement('beforeend', $container[0]);
            $(data).each(function () {
                if (data.hasOwnProperty('enableTime')) {
                    options.enableTime = true;
                    options.dateFormat += ' H:i';
                    options.defaultHour = data.defaultHour || 12;
                    options.defaultMinute = data.defaultMinute || 0;
                }

                if (data.hasOwnProperty('enableSeconds')) {
                    options.enableSeconds = true;
                    options.dateFormat += ':S';
                    options.defaultSeconds = data.defaultSeconds || 0;
                }

                if (data.hasOwnProperty('allowInput')) {
                    options.allowInput = true;
                    options.clickOpens = false;
                    options.parseDate = function() {
                        // Accept any date string but don't update the value of the input
                        // If the dev console is open this will issue a warning.
                        return true;
                    };
                }

                console.log(options);

                $el.flatpickr(options);
            });
        }
    };

    Icinga.Behaviors.Flatpickr = Flatpickr;

})(Icinga, jQuery);
