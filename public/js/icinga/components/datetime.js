/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

/**
 * Ensures that our date/time controls will work on every browser (natively or javascript based)
 */
define(['jquery', 'datepicker', 'timepicker'],function($) {
    "use strict";

    var DateTimeBehaviour = function() {
        this.enable = function() {
            if (!Modernizr.inputtypes.date) {
                $(".datepick").datepicker();
            }
            if (!Modernizr.inputtypes.time) {
                $(".timepick").timepicker();
            }
        }
    };
    return new DateTimeBehaviour();
});
