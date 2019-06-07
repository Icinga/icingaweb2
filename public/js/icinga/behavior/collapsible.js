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
        this.defaultVisibleRows = 2;
        this.defaultHeight = 36;

        this.collapsibleStates = this.getStateFromStorage();
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

                if (typeof _this.collapsibleStates[collapsiblePath] === 'undefined') {
                    _this.collapsibleStates[collapsiblePath] = true;
                    _this.collapse($collapsible);
                } else if (_this.collapsibleStates[collapsiblePath]) {
                    _this.collapse($collapsible);
                }
            } else {
                // This collapsible is not large enough (anymore)
                delete _this.collapsibleStates[collapsiblePath];
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
            if (_this.collapsibleStates[collapsiblePath]) {
                _this.collapsibleStates[collapsiblePath] = false;
                _this.expand($collapsible);
            } else {
                _this.collapsibleStates[collapsiblePath] = true;
                _this.collapse($collapsible);
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
            return $collapsible.innerHeight() > ($collapsible.data('height') || this.defaultHeight);
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
            $collapsible.css({display: 'block', height: $collapsible.data('height') || this.defaultHeight});
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
     * Load the collapsible states from storage
     *
     * @returns {{}}
     */
    Collapsible.prototype.getStateFromStorage = function () {
        var state = localStorage.getItem('collapsible.state');
        if (!! state) {
            return JSON.parse(state);
        }

        return {};
    };

    /**
     * Save the collapsible states to storage
     */
    Collapsible.prototype.destroy = function () {
        localStorage.setItem('collapsible.state', JSON.stringify(this.collapsibleStates));
    };

    Icinga.Behaviors.Collapsible = Collapsible;

})(Icinga, jQuery);
