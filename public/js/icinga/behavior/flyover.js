/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Flyover
 *
 * A toggleable flyover
 */
(function(Icinga, $) {

    'use strict';

    var expandedFlyovers = {};

    function Flyover(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
        this.on('click', this.onClick, this);
        this.on('click', '.flyover-toggle', this.onClickFlyoverToggle, this);
    }

    Flyover.prototype = new Icinga.EventListener();

    Flyover.prototype.onRendered = function(event) {
        // Re-expand expanded containers after an auto-refresh

        $(event.target).find('.flyover').each(function() {
            var $this = $(this);

            if (typeof expandedFlyovers['#' + $this.attr('id')] !== 'undefined') {
                var $container = $this.closest('.container');

                if ($this.offset().left - $container.offset().left > $container.innerWidth() / 2) {
                    $this.addClass('flyover-right');
                }

                $this.toggleClass('flyover-expanded');
            }
        });
    };

    Flyover.prototype.onClick = function(event) {
        var $target = $(event.target);

        if (! $target.closest('.flyover').length) {
            var _this = event.data.self;
            $target.closest('#main').find('.flyover.flyover-expanded').each(function() {
                $(this).find('.flyover-toggle:first').each(function() {
                    _this.onClickFlyoverToggle({target: this});
                });
            });
        }
    };

    Flyover.prototype.onClickFlyoverToggle = function(event) {
        var $flyover = $(event.target).closest('.flyover');

        $flyover.toggleClass('flyover-expanded');

        if ($flyover.hasClass('flyover-expanded')) {
            var $container = $flyover.closest('.container');

            if ($flyover.offset().left - $container.offset().left > $container.innerWidth() / 2) {
                $flyover.addClass('flyover-right');
            }

            expandedFlyovers['#' + $flyover.attr('id')] = null;
        } else {
            $flyover.removeClass('flyover-right');

            delete expandedFlyovers['#' + $flyover.attr('id')];
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Flyover = Flyover;

})(Icinga, jQuery);
