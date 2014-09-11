// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

(function(Icinga, $) {

    "use strict";

    Icinga.Behaviors = Icinga.Behaviors || {};

    var Tristate = function (icinga) {
        this.icinga = icinga;
    };

    Tristate.prototype.apply = function(el) {
        var self = this, icinga = this.icinga;
    };

    Tristate.prototype.bind = function() {
        // Toggle all triStateButtons
        $(document).on('click', 'div.tristate .tristate-dummy', { self: this }, this.clickTriState);
    };

    Tristate.prototype.unbind = function() {
        $(document).off('click', 'div.tristate .tristate-dummy', this.clickTriState);
    };

    Tristate.prototype.clickTriState = function (event) {
        var self = event.data.self;
        var $tristate = $(this);
        var triState  = parseInt($tristate.data('icinga-tristate'), 10);

        // load current values
        var old   = $tristate.data('icinga-old').toString();
        var value = $tristate.parent().find('input:radio:checked').first().prop('checked', false).val();

        // calculate the new value
        if (triState) {
            // 1         => 0
            // 0         => unchanged
            // unchanged => 1
            value = value === '1' ? '0' : (value === '0' ? 'unchanged' : '1');
        } else {
            // 1 => 0
            // 0 => 1
            value = value === '1' ? '0' : '1';
        }

        // update form value
        $tristate.parent().find('input:radio[value="' + value + '"]').prop('checked', true);
        // update dummy

        if (value !== old) {
            $tristate.parent().find('b.tristate-changed').css('visibility', 'visible');
        } else {
            $tristate.parent().find('b.tristate-changed').css('visibility', 'hidden');
        }
        self.icinga.ui.setTriState(value.toString(), $tristate);    
    };

    Icinga.Behaviors.Tristate = Tristate;

}) (Icinga, jQuery);
