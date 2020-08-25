/*! Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

(function (Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Flatpickr = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
        this.icinga = icinga;
    };

    Flatpickr.prototype = new Icinga.EventListener();

    Flatpickr.prototype.onRendered = function (event) {
        if (! $('#flatpickr-container').length
            && ! $('#login').length
            && ! $('#guest-error').length
            && ! $('#setup').length
        ) {
            $('#layout').append(
                '<div id="flatpickr-container"></div>'
            );
        }

        if (typeof $().flatpickr === 'function') {
            $('.icinga-flatpickr').each(function () {
                var $el = $(this);
                var data = $el.find('input').data();
                var _this = event.data.self;
                var lang = _this.icinga.ui.getLocale();
                var fmt = _this.icinga.ui.getDateFormat();
                var map = { dd : "d", MM : "m", y : "Y", GGGGG : "" };
                fmt = fmt.replace(/dd|MM|y|yy|GGGGG/g, function(matched){ return map[matched]; });
                var options = {
                    locale: lang.slice(0, 2),
                    appendTo: $('#flatpickr-container')[0],
                    dateFormat: fmt,
                    wrap: true
                };

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
                    options.parseDate = function () {
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
