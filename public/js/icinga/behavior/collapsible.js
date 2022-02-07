/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for collapsible containers.
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    var Collapsible = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('layout-change', this.onLayoutChange, this);
        this.on('rendered', '#main > .container, #modal-content', this.onRendered, this);
        this.on('click', '.collapsible + .collapsible-control, .collapsible > .collapsible-control',
            this.onControlClicked, this);

        this.icinga = icinga;
        this.defaultVisibleRows = 2;
        this.defaultVisibleHeight = 36;

        this.state = new Icinga.Storage.StorageAwareMap.withStorage(
            Icinga.Storage.BehaviorStorage('collapsible'),
            'expanded'
        )
            .on('add', this.onExpand, this)
            .on('delete', this.onCollapse, this);
    };

    Collapsible.prototype = new Icinga.EventListener();

    /**
     * Initializes all collapsibles. Triggered on rendering of a container.
     *
     * @param event  Event  The `onRender` event triggered by the rendered container
     */
    Collapsible.prototype.onRendered = function(event) {
        var _this = event.data.self;
        var toCollapse = [];

        $.each(event.target.querySelectorAll('.collapsible:not(.can-collapse)'), function (_, collapsible) {
            // Assumes that any newly rendered elements are expanded
            if (_this.canCollapse(collapsible) && _this.setupCollapsible(collapsible)) {
                toCollapse.push([collapsible, _this.calculateCollapsedHeight(collapsible)]);
            }
        });

        // Elements are all collapsed in a row now, after height calculations are done.
        // This avoids reflows since instantly collapsing an element will cause one if
        // the height of the next element is being calculated.
        for (var i = 0; i < toCollapse.length; i++) {
            _this.collapse(toCollapse[i][0], toCollapse[i][1]);
        }
    };

    /**
     * Updates all collapsibles.
     *
     * @param event  Event  The `layout-change` event triggered by window resizing or column changes
     */
    Collapsible.prototype.onLayoutChange = function(event) {
        var _this = event.data.self;
        var toCollapse = [];

        $.each(document.querySelectorAll('.collapsible'), function (_, collapsible) {
            if ($(collapsible).is('.can-collapse')) {
                if (! _this.canCollapse(collapsible)) {
                    var toggleSelector = collapsible.dataset.toggleElement;
                    if (! toggleSelector) {
                        $(collapsible).next('.collapsible-control').remove();
                    }

                    collapsible.classList.remove('can-collapse');
                    _this.expand(collapsible);
                }
            } else if (_this.canCollapse(collapsible) && _this.setupCollapsible(collapsible)) {
                // It's expanded but shouldn't
                toCollapse.push([collapsible, _this.calculateCollapsedHeight(collapsible)]);
            }
        });

        setTimeout(function () {
            for (var i = 0; i < toCollapse.length; i++) {
                _this.collapse(toCollapse[i][0], toCollapse[i][1]);
            }
        }, 0);
    };

    /**
     * A collapsible got expanded in another window, try to apply this here as well
     *
     * @param   {string}    collapsiblePath
     */
    Collapsible.prototype.onExpand = function(collapsiblePath) {
        var collapsible = $(collapsiblePath)[0];

        if (collapsible && $(collapsible).is('.can-collapse')) {
            this.expand(collapsible);
        }
    };

    /**
     * A collapsible got collapsed in another window, try to apply this here as well
     *
     * @param   {string}    collapsiblePath
     */
    Collapsible.prototype.onCollapse = function(collapsiblePath) {
        var collapsible = $(collapsiblePath)[0];

        if (collapsible && this.canCollapse(collapsible)) {
            this.collapse(collapsible, this.calculateCollapsedHeight(collapsible));
        }
    };

    /**
     * Event handler for toggling collapsibles. Switches the collapsed state of the respective container.
     *
     * @param event  Event  The `onClick` event triggered by the clicked collapsible-control element
     */
    Collapsible.prototype.onControlClicked = function(event) {
        var _this = event.data.self;
        var $target = $(event.currentTarget);

        var collapsible = $target.prev('.collapsible')[0];
        if (! collapsible) {
            collapsible = $target.parent('.collapsible')[0];
        }

        if (! collapsible) {
            _this.icinga.logger.error(
                '[Collapsible] Collapsible control has no associated .collapsible: ', $target[0]);
        } else if (typeof collapsible.dataset.noPersistence !== 'undefined') {
            if ($(collapsible).is('.collapsed')) {
                _this.expand(collapsible);
            } else {
                _this.collapse(collapsible, _this.calculateCollapsedHeight(collapsible));
            }
        } else {
            var collapsiblePath = _this.icinga.utils.getCSSPath(collapsible);
            if (_this.state.has(collapsiblePath)) {
                _this.state.delete(collapsiblePath);
                _this.collapse(collapsible, _this.calculateCollapsedHeight(collapsible));
            } else {
                _this.state.set(collapsiblePath);
                _this.expand(collapsible);
            }
        }
    };

    /**
     * Setup the given collapsible
     *
     * @param collapsible  The given collapsible container element
     *
     * @returns {boolean}  Whether it needs to collapse or not
     */
    Collapsible.prototype.setupCollapsible = function (collapsible) {
        var toggleSelector = collapsible.dataset.toggleElement;
        if (!! toggleSelector) {
            var toggle = $(collapsible).children(toggleSelector)[0];
            if (! toggle && $(collapsible.nextSibling).is(toggleSelector)) {
                toggle = collapsible.nextSibling;
            }

            if (! toggle) {
                this.icinga.logger.error(
                    '[Collapsible] Control `' + toggleSelector + '` not found in .collapsible', collapsible);
            } else if (! toggle.classList.contains('collapsible-control')) {
                toggle.classList.add('collapsible-control');
            }
        } else {
            setTimeout(function () {
                var collapsibleControl = document
                    .getElementById('collapsible-control-ghost')
                    .cloneNode(true);
                collapsibleControl.removeAttribute('id');
                collapsible.parentNode.insertBefore(collapsibleControl, collapsible.nextElementSibling);
            }, 0);
        }

        collapsible.classList.add('can-collapse');

        return typeof collapsible.dataset.noPersistence !== 'undefined'
            || ! this.state.has(this.icinga.utils.getCSSPath(collapsible));
    };

    /**
     * Return an appropriate row element selector
     *
     * @param collapsible  The given collapsible container element
     *
     * @returns {string}
     */
    Collapsible.prototype.getRowSelector = function(collapsible) {
        if (!! collapsible.dataset.visibleHeight) {
            return '';
        }

        if (collapsible.tagName === 'TABLE') {
            return '> tbody > tr';
        } else if (collapsible.tagName === 'UL' || collapsible.tagName === 'OL') {
            return '> li:not(.collapsible-control)';
        }

        return '';
    };

    /**
     * Check whether the given collapsible needs to collapse
     *
     * @param collapsible  The given collapsible container element
     *
     * @returns {boolean}
     */
    Collapsible.prototype.canCollapse = function(collapsible) {
        var rowSelector = this.getRowSelector(collapsible);
        if (!! rowSelector) {
            var visibleRows = Number(collapsible.dataset.visibleRows);
            if (isNaN(visibleRows)) {
                visibleRows = this.defaultVisibleRows;
            }

            return $(rowSelector, collapsible).length > visibleRows * 2;
        } else {
            var actualHeight = collapsible.scrollHeight - parseFloat(
                window.getComputedStyle(collapsible).getPropertyValue('padding-top')
            );

            var maxHeight = Number(collapsible.dataset.visibleHeight);
            if (isNaN(maxHeight)) {
                maxHeight = this.defaultVisibleHeight;
            }

            return actualHeight >= maxHeight * 2;
        }
    };

    /**
     * Calculate the height the given collapsible should have when collapsed
     *
     * @param collapsible
     */
    Collapsible.prototype.calculateCollapsedHeight = function (collapsible) {
        var height;

        var rowSelector = this.getRowSelector(collapsible);
        if (!! rowSelector) {
            height = collapsible.scrollHeight;
            height -= parseFloat(window.getComputedStyle(collapsible).getPropertyValue('padding-bottom'));

            var visibleRows = Number(collapsible.dataset.visibleRows);
            if (isNaN(visibleRows)) {
                visibleRows = this.defaultVisibleRows;
            }

            var $rows = $(rowSelector, collapsible).slice(visibleRows);
            for (var i = 0; i < $rows.length; i++) {
                var row = $rows[i];

                if (row.previousElementSibling === null) { // very first element
                    height -= row.offsetHeight;
                    height -= parseFloat(window.getComputedStyle(row).getPropertyValue('margin-top'));
                } else if (i < $rows.length - 1) { // every element but the last one
                    var prevBottomBorderAt = row.previousElementSibling.offsetTop;
                    prevBottomBorderAt += row.previousElementSibling.offsetHeight;
                    height -= row.offsetTop - prevBottomBorderAt + row.offsetHeight;
                } else { // the last element
                    height -= row.offsetHeight;
                    height -= parseFloat(window.getComputedStyle(row).getPropertyValue('margin-top'));
                    height -= parseFloat(window.getComputedStyle(row).getPropertyValue('margin-bottom'));
                }
            }
        } else {
            height = Number(collapsible.dataset.visibleHeight);
            if (isNaN(height)) {
                height = this.defaultVisibleHeight;
            }

            height += parseFloat(window.getComputedStyle(collapsible).getPropertyValue('padding-top'));

            if (
                !! collapsible.dataset.toggleElement
                && ! $(collapsible.nextSibling).is(collapsible.dataset.toggleElement)
            ) {
                var toggle = $(collapsible).children(collapsible.dataset.toggleElement)[0];
                height += toggle.offsetHeight; // TODO: Very expensive at times. (50ms+) Check why!
                height += parseFloat(window.getComputedStyle(toggle).getPropertyValue('margin-top'));
                height += parseFloat(window.getComputedStyle(toggle).getPropertyValue('margin-bottom'));
            }
        }

        return height;
    };

    /**
     * Collapse the given collapsible
     *
     * @param collapsible The given collapsible container element
     * @param toHeight {int} The height in pixels to collapse to
     */
    Collapsible.prototype.collapse = function(collapsible, toHeight) {
        collapsible.style.cssText = 'display: block; height: ' + toHeight + 'px; padding-bottom: 0';
        collapsible.classList.add('collapsed');
    };

    /**
     * Expand the given collapsible
     *
     * @param   collapsible    The given collapsible container element
     */
    Collapsible.prototype.expand = function(collapsible) {
        collapsible.classList.remove('collapsed');
        collapsible.style.cssText = '';
    };

    Icinga.Behaviors.Collapsible = Collapsible;

})(Icinga, jQuery);
