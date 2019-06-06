/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for collapsible containers.
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    var Collapsible = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '.container', this.onRendered, this);
        this.on('click', '.collapsible + .collapsible-control', this.onControlClicked, this);

        this.icinga = icinga;
        this.expandedContainers = {};
        this.defaultNumOfRows = 2;
        this.defaultHeight = 36;
    };
    Collapsible.prototype = new Icinga.EventListener();

    /**
     * Initializes all collapsibles. Triggered on rendering of a container.
     *
     * @param event  Event  The `onRender` event triggered by the rendered container
     */
    Collapsible.prototype.onRendered = function(event) {
        var _this = event.data.self;

        $('.collapsible', event.currentTarget).each(function() {
            var $collapsible = $(this);

            if (_this.canCollapse($collapsible)) {
                $collapsible.after($('#collapsible-control-ghost').clone().removeAttr('id'));
                $collapsible.addClass('can-collapse');
                _this.updateCollapsedState($collapsible);
            }
        });
    };

    /**
     * Event handler for toggling collapsibles. Switches the collapsed state of the respective container.
     *
     * @param event  Event  The `onClick` event triggered by the clicked collapsible-control element
     */
    Collapsible.prototype.onControlClicked = function(event) {
        var _this = event.data.self;
        var $target = $(event.currentTarget);
        var $collapsible = $target.prev('.collapsible');

        if (! $collapsible.length) {
            _this.icinga.logger.error('[Collapsible] Collapsible control has no associated .collapsible: ', $target);
        } else {
            _this.updateCollapsedState($collapsible);
        }
    };

    /**
     * Applies the collapse state of the given container. Adds or removes class `collapsed` to containers and sets the
     * height.
     *
     * @param $collapsible  jQuery  The given collapsible container element
     */
    Collapsible.prototype.updateCollapsedState = function($collapsible) {
        var collapsiblePath = this.icinga.utils.getCSSPath($collapsible);
        if (typeof this.expandedContainers[collapsiblePath] === 'undefined') {
            this.expandedContainers[collapsiblePath] = $collapsible.is('.collapsed');
        }

        if (this.expandedContainers[collapsiblePath]) {
            this.expandedContainers[collapsiblePath] = false;
            $collapsible.removeClass('collapsed');
            $collapsible.css({display: '', height: ''});
        } else {
            this.expandedContainers[collapsiblePath] = true;
            $collapsible.addClass('collapsed');

            var rowSelector = this.getRowSelector($collapsible);
            if (!! rowSelector) {
                var $rows = $(rowSelector, $collapsible).slice(0, $collapsible.data('numofrows') || this.defaultNumOfRows);

                var totalHeight = $rows.offset().top - $collapsible.offset().top;
                $rows.outerHeight(function (_, height) {
                    totalHeight += height;
                });

                $collapsible.css({display: 'block', height: totalHeight});
            } else {
                $collapsible.css({display: 'block', height: $collapsible.data('height') || this.defaultHeight});
            }
        }
    };

    /**
     * Return an appropriate row element selector
     *
     * @param $collapsible jQuery  The given collapsible container element
     *
     * @returns {string}
     */
    Collapsible.prototype.getRowSelector = function ($collapsible) {
        if ($collapsible.is('table')) {
            return '> tbody > th, > tbody > tr';
        } else if ($collapsible.is('ul, ol')) {
            return '> li';
        }

        return '';
    };

    /**
     * Check whether the given collapsible needs to collapse
     *
     * @param $collapsible jQuery  The given collapsible container element
     *
     * @returns {boolean}
     */
    Collapsible.prototype.canCollapse = function ($collapsible) {
        var rowSelector = this.getRowSelector($collapsible);
        if (!! rowSelector) {
            return $(rowSelector, $collapsible).length > ($collapsible.data('numofrows') || this.defaultNumOfRows);
        } else {
            return $collapsible.innerHeight() > ($collapsible.data('height') || this.defaultHeight);
        }
    };

    Icinga.Behaviors.Collapsible = Collapsible;

})(Icinga, jQuery);
