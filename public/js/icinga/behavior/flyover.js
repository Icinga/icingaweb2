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

        this.on('rendered', '#main > .container', this.onRendered, this);
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
        // Close flyover on click outside the flyover
        var $target = $(event.target);

        if (! $target.closest('.flyover').length) {
            var _this = event.data.self;
            $.each(expandedFlyovers, function (id) {
                _this.onClickFlyoverToggle({target: $('.flyover-toggle', id)[0]});
            });
        }
    };

    Flyover.prototype.onClickFlyoverToggle = function(event) {
        var $flyover = $(event.target).closest('.flyover');

        $flyover.toggleClass('flyover-expanded');

        var $container = $flyover.closest('.container');
        if ($flyover.hasClass('flyover-expanded')) {
            if ($flyover.offset().left - $container.offset().left > $container.innerWidth() / 2) {
                $flyover.addClass('flyover-right');
            }

            if ($flyover.is('[data-flyover-suspends-auto-refresh]')) {
                $container[0].dataset.suspendAutorefresh = '';
            }

            expandedFlyovers['#' + $flyover.attr('id')] = null;
        } else {
            $flyover.removeClass('flyover-right');

            if ($flyover.is('[data-flyover-suspends-auto-refresh]')) {
                delete $container[0].dataset.suspendAutorefresh;
            }

            delete expandedFlyovers['#' + $flyover.attr('id')];
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Flyover = Flyover;

})(Icinga, jQuery);
