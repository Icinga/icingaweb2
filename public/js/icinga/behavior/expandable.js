/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Expandable
 *
 * Initially collapsed, but expandable content
 */
(function(Icinga, $) {

    'use strict';

    var expandedExpandables = {};

    function Expandable(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
        this.on('click', this.onClick, this);
    }

    Expandable.prototype = new Icinga.EventListener();

    Expandable.prototype.onRendered = function(event) {
        $(event.target).find('.expandable-toggle').each(function() {
            var $this = $(this);

            if (typeof expandedExpandables['#' + $this.attr('id')] !== 'undefined') {
                $this.prop('checked', true);
            }
        });
    };

    Expandable.prototype.onClick = function(event) {
        var $expandableToggle = $(event.target);

        if ($expandableToggle.prop('checked')) {
            expandedExpandables['#' + $expandableToggle.attr('id')] = null;
        } else {
            delete expandedExpandables['#' + $expandableToggle.attr('id')];
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Expandable = Expandable;

}) (Icinga, jQuery);
