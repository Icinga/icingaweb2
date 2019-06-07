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
        this.expanded = new Set();
        this.defaultVisibleRows = 2;
        this.defaultVisibleHeight = 36;

        this.loadStorage();
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
            var collapsiblePath = _this.icinga.utils.getCSSPath($collapsible);

            // Assumes that any newly rendered elements are expanded
            if (_this.canCollapse($collapsible)) {
                $collapsible.after($('#collapsible-control-ghost').clone().removeAttr('id'));
                $collapsible.addClass('can-collapse');

                if (! _this.expanded.has(collapsiblePath)) {
                    _this.collapse($collapsible);
                }
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
            var collapsiblePath = _this.icinga.utils.getCSSPath($collapsible);
            if (_this.expanded.has(collapsiblePath)) {
                _this.expanded.delete(collapsiblePath);
                _this.collapse($collapsible);
            } else {
                _this.expanded.add(collapsiblePath);
                _this.expand($collapsible);
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
            return '> tbody > tr';
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
            return $(rowSelector, $collapsible).length > ($collapsible.data('visibleRows') || this.defaultVisibleRows);
        } else {
            return $collapsible.innerHeight() > ($collapsible.data('visibleHeight') || this.defaultVisibleHeight);
        }
    };

    /**
     * Collapse the given collapsible
     *
     * @param   $collapsible    jQuery      The given collapsible container element
     */
    Collapsible.prototype.collapse = function ($collapsible) {
        $collapsible.addClass('collapsed');

        var rowSelector = this.getRowSelector($collapsible);
        if (!! rowSelector) {
            var $rows = $(rowSelector, $collapsible).slice(0, $collapsible.data('visibleRows') || this.defaultVisibleRows);

            var totalHeight = $rows.offset().top - $collapsible.offset().top;
            $rows.outerHeight(function (_, height) {
                totalHeight += height;
            });

            $collapsible.css({display: 'block', height: totalHeight});
        } else {
            $collapsible.css({display: 'block', height: $collapsible.data('visibleHeight') || this.defaultVisibleHeight});
        }
    };

    /**
     * Expand the given collapsible
     *
     * @param   $collapsible    jQuery      The given collapsible container element
     */
    Collapsible.prototype.expand = function ($collapsible) {
        $collapsible.removeClass('collapsed');
        $collapsible.css({display: '', height: ''});
    };

    /**
     * Load state from storage
     */
    Collapsible.prototype.loadStorage = function () {
        var expanded = localStorage.getItem('collapsible.expanded');
        if (!! expanded) {
            this.expanded = new Set(JSON.parse(expanded));
        }
    };

    /**
     * Save state to storage
     */
    Collapsible.prototype.destroy = function () {
        if (this.expanded.size > 0) {
            localStorage.setItem('collapsible.expanded', JSON.stringify(Array.from(this.expanded.values())));
        } else {
            localStorage.removeItem('collapsible.expanded');
        }
    };

    Icinga.Behaviors.Collapsible = Collapsible;

})(Icinga, jQuery);
