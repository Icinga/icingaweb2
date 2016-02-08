/*! Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Autofocus = function (icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', this.onRendered, this);
    };

    Autofocus.prototype = new Icinga.EventListener();

    Autofocus.prototype.onRendered = function(e) {
        setTimeout(function() {
            if (document.activeElement === e.target
                || document.activeElement === document.body
            ) {
                $(e.target).find('.autofocus').focus();
            }
        }, 0);
    };

    Icinga.Behaviors.Autofocus = Autofocus;

})(Icinga, jQuery);
