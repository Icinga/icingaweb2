/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

/**
 * Ensures that our date/time controls will work on every browser (natively or javascript based)
 */
define(['jquery', 'datetimepicker'], function($) {
    "use strict";

    var DateTimePicker = function(target) {
        $(target).find('.datetime input')
            .attr('data-format', 'yyyy-MM-dd hh:mm:ss');

        $(target).find('.datetime')
            .addClass('input-append')
            .append('<span class="add-on">' +
                '<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i></span>')
            .datetimepicker();
    };
    return DateTimePicker;
});
