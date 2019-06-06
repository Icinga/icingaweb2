/*! Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for collapsible containers. Creates collapsibles from `<div class="collapsible">…</div>`
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    var CollapsibleContainer = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#col2', this.onRendered, this);
        this.on('click', '.collapsible + .collapsible-control', this.onControlClicked, this);

        this.icinga = icinga;
        this.expandedContainers = {};
        this.defaultNumOfRows = 2;
        this.defaultHeight = 36;
    };
    CollapsibleContainer.prototype = new Icinga.EventListener();

    /**
     * Initializes all collapsibles. Triggered on rendering of a container.
     *
     * @param event  Event  The `onRender` event triggered by the rendered container
     */
    CollapsibleContainer.prototype.onRendered = function(event) {
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
    CollapsibleContainer.prototype.onControlClicked = function(event) {
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
     * Renders the collapse state of the given container. Adds or removes class `collapsible` to containers and sets the
     * height.
     *
     * @param $collapsible  jQuery  The given collapsible container element
     */
    CollapsibleContainer.prototype.updateCollapsedState = function($collapsible) {
        var collapsiblePath = this.icinga.utils.getCSSPath($collapsible);
        if (typeof this.expandedContainers[collapsiblePath] === 'undefined') {
            this.expandedContainers[collapsiblePath] = $collapsible.is('.collapsed');
        }

        if (this.expandedContainers[collapsiblePath]) {
            this.expandedContainers[collapsiblePath] = false;
            $collapsible.removeClass('collapsed');
            $collapsible.css({ maxHeight: 'none' });
        } else {
            this.expandedContainers[collapsiblePath] = true;
            $collapsible.addClass('collapsed');

            var rowSelector = this.getRowSelector($collapsible);
            if (!! rowSelector) {
                var $rows = $(rowSelector, $collapsible).slice(0, $collapsible.data('numofrows') || this.defaultNumOfRows);

                var totalHeight = 0;
                $rows.outerHeight(function (_, height) {
                    totalHeight += height;
                });

                $collapsible.css({maxHeight: totalHeight});
            } else {
                $collapsible.css({maxHeight: $collapsible.data('height') || this.defaultHeight});
            }
        }
    };

    CollapsibleContainer.prototype.getRowSelector = function ($collapsible) {
        if ($collapsible.is('table')) {
            return '> tbody > th, > tbody > tr';
        } else if ($collapsible.is('ul, ol')) {
            return '> li';
        }

        return '';
    };

    CollapsibleContainer.prototype.canCollapse = function ($collapsible) {
        var rowSelector = this.getRowSelector($collapsible);
        if (!! rowSelector) {
            return $(rowSelector, $collapsible).length > ($collapsible.data('numofrows') || this.defaultNumOfRows);
        } else {
            return $collapsible.innerHeight() > ($collapsible.data('height') || this.defaultHeight);
        }
    };

    Icinga.Behaviors.collapsibleContainer = CollapsibleContainer;

})(Icinga, jQuery);
