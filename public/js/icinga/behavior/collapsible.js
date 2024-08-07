/*! Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

;(function(Icinga) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    let $ = window.$;

    try {
        $ = require('icinga/icinga-php-library/notjQuery');
    } catch (e) {
        console.warn('[Collapsible] notjQuery unavailable. Using jQuery for now');
    }

    /**
     * Behavior for collapsible containers.
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    class Collapsible extends Icinga.EventListener {
        constructor(icinga) {
            super(icinga);

            this.on('layout-change', this.onLayoutChange, this);
            this.on('rendered', '#main > .container, #modal-content', this.onRendered, this);
            this.on('click', '.collapsible + .collapsible-control, .collapsible .collapsible-control',
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
        }

        /**
         * Initializes all collapsibles. Triggered on rendering of a container.
         *
         * @param event  Event  The `onRender` event triggered by the rendered container
         */
        onRendered(event) {
            let _this = event.data.self,
                toCollapse = [],
                toExpand = [];

            event.target.querySelectorAll('.collapsible').forEach(collapsible => {
                // Assumes that any newly rendered elements are expanded
                if (! ('canCollapse' in collapsible.dataset) && _this.canCollapse(collapsible)) {
                    if (_this.setupCollapsible(collapsible)) {
                        toCollapse.push([collapsible, _this.calculateCollapsedHeight(collapsible)]);
                    } else if (_this.isDetails(collapsible)) {
                        // Except if it's a <details> element, which may not be expanded by default
                        toExpand.push(collapsible);
                    }
                }
            });

            // Elements are all collapsed in a row now, after height calculations are done.
            // This avoids reflows since instantly collapsing an element will cause one if
            // the height of the next element is being calculated.
            for (const collapseInfo of toCollapse) {
                _this.collapse(collapseInfo[0], collapseInfo[1]);
            }

            for (const collapsible of toExpand) {
                _this.expand(collapsible);
            }
        }

        /**
         * Updates all collapsibles.
         *
         * @param event  Event  The `layout-change` event triggered by window resizing or column changes
         */
        onLayoutChange(event) {
            let _this = event.data.self;
            let toCollapse = [];

            document.querySelectorAll('.collapsible').forEach(collapsible => {
                if ('canCollapse' in collapsible.dataset) {
                    if (! _this.canCollapse(collapsible)) {
                        let toggleSelector = collapsible.dataset.toggleElement;
                        if (! this.isDetails(collapsible)) {
                            if (! toggleSelector) {
                                collapsible.nextElementSibling.remove();
                            } else {
                                let toggle = document.getElementById(toggleSelector);
                                if (toggle) {
                                    toggle.classList.remove('collapsed');
                                    delete toggle.dataset.canCollapse;
                                }
                            }
                        }

                        delete collapsible.dataset.canCollapse;
                        _this.expand(collapsible);
                    }
                } else if (_this.canCollapse(collapsible) && _this.setupCollapsible(collapsible)) {
                    // It's expanded but shouldn't
                    toCollapse.push([collapsible, _this.calculateCollapsedHeight(collapsible)]);
                }
            });

            setTimeout(function () {
                for (const collapseInfo of toCollapse) {
                    _this.collapse(collapseInfo[0], collapseInfo[1]);
                }
            }, 0);
        }

        /**
         * A collapsible got expanded in another window, try to apply this here as well
         *
         * @param   {string}    collapsiblePath
         */
        onExpand(collapsiblePath) {
            let collapsible = document.querySelector(collapsiblePath);

            if (collapsible && 'canCollapse' in collapsible.dataset) {
                if ('stateCollapses' in collapsible.dataset) {
                    this.collapse(collapsible, this.calculateCollapsedHeight(collapsible));
                } else {
                    this.expand(collapsible);
                }
            }
        }

        /**
         * A collapsible got collapsed in another window, try to apply this here as well
         *
         * @param   {string}    collapsiblePath
         */
        onCollapse(collapsiblePath) {
            let collapsible = document.querySelector(collapsiblePath);

            if (collapsible && this.canCollapse(collapsible)) {
                if ('stateCollapses' in collapsible.dataset) {
                    this.expand(collapsible);
                } else {
                    this.collapse(collapsible, this.calculateCollapsedHeight(collapsible));
                }
            }
        }

        /**
         * Event handler for toggling collapsibles. Switches the collapsed state of the respective container.
         *
         * @param event  Event  The `onClick` event triggered by the clicked collapsible-control element
         */
        onControlClicked(event) {
            let _this = event.data.self,
                target = event.currentTarget;

            let collapsible = target.previousElementSibling;
            if ('collapsibleAt' in target.dataset) {
                collapsible = document.querySelector(target.dataset.collapsibleAt);
            } else if (! collapsible) {
                collapsible = target.closest('.collapsible');
            }

            if (! collapsible) {
                _this.icinga.logger.error(
                    '[Collapsible] Collapsible control has no associated .collapsible: ', target);

                return;
            } else if ('noPersistence' in collapsible.dataset) {
                if (collapsible.classList.contains('collapsed')) {
                    _this.expand(collapsible);
                } else {
                    _this.collapse(collapsible, _this.calculateCollapsedHeight(collapsible));
                }
            } else {
                let collapsiblePath = _this.icinga.utils.getCSSPath(collapsible),
                    stateCollapses = 'stateCollapses' in collapsible.dataset;

                if (_this.state.has(collapsiblePath)) {
                    _this.state.delete(collapsiblePath);

                    if (stateCollapses) {
                        _this.expand(collapsible);
                    } else {
                        _this.collapse(collapsible, _this.calculateCollapsedHeight(collapsible));
                    }
                } else {
                    _this.state.set(collapsiblePath);

                    if (stateCollapses) {
                        _this.collapse(collapsible, _this.calculateCollapsedHeight(collapsible));
                    } else {
                        _this.expand(collapsible);
                    }
                }
            }

            if (_this.isDetails(collapsible)) {
                // The browser handles these clicks as well, and would toggle the state again
                event.preventDefault();
            }
        }

        /**
         * Setup the given collapsible
         *
         * @param collapsible  The given collapsible container element
         *
         * @returns {boolean}  Whether it needs to collapse or not
         */
        setupCollapsible(collapsible) {
            if (this.isDetails(collapsible)) {
                let summary = collapsible.querySelector(':scope > summary');
                if (! summary.classList.contains('collapsible-control')) {
                    summary.classList.add('collapsible-control');
                }

                if (collapsible.open) {
                    collapsible.dataset.stateCollapses = '';
                }
            } else if (!! collapsible.dataset.toggleElement) {
                let toggleSelector = collapsible.dataset.toggleElement,
                    toggle = collapsible.querySelector(toggleSelector),
                    externalToggle = false;
                if (! toggle) {
                    if (collapsible.nextElementSibling && collapsible.nextElementSibling.matches(toggleSelector)) {
                        toggle = collapsible.nextElementSibling;
                    } else {
                        externalToggle = true;
                        toggle = document.getElementById(toggleSelector);
                    }
                }

                if (! toggle) {
                    if (externalToggle) {
                        this.icinga.logger.error(
                            '[Collapsible] External control with id `'
                                + toggleSelector
                                + '` not found for .collapsible',
                            collapsible
                        );
                    } else {
                        this.icinga.logger.error(
                            '[Collapsible] Control `' + toggleSelector + '` not found in .collapsible', collapsible);
                    }

                    return false;
                } else if (externalToggle) {
                    collapsible.dataset.hasExternalToggle = '';

                    toggle.dataset.canCollapse = '';
                    toggle.dataset.collapsibleAt = this.icinga.utils.getCSSPath(collapsible);
                    $(toggle).on('click', e => {
                        // Only required as onControlClicked() is compatible with Icinga.EventListener
                        e.data = { self: this };
                        this.onControlClicked(e);
                    });
                } else if (! toggle.classList.contains('collapsible-control')) {
                    toggle.classList.add('collapsible-control');
                }
            } else {
                setTimeout(function () {
                    let collapsibleControl = document
                        .getElementById('collapsible-control-ghost')
                        .cloneNode(true);
                    collapsibleControl.removeAttribute('id');
                    collapsible.parentNode.insertBefore(collapsibleControl, collapsible.nextElementSibling);
                }, 0);
            }

            collapsible.dataset.canCollapse = '';

            if ('noPersistence' in collapsible.dataset) {
                return ! ('stateCollapses' in collapsible.dataset);
            }

            if ('stateCollapses' in collapsible.dataset) {
                return this.state.has(this.icinga.utils.getCSSPath(collapsible));
            } else {
                return ! this.state.has(this.icinga.utils.getCSSPath(collapsible));
            }
        }

        /**
         * Return an appropriate row element selector
         *
         * @param collapsible  The given collapsible container element
         *
         * @returns {string}
         */
        getRowSelector(collapsible) {
            if (!! collapsible.dataset.visibleHeight) {
                return '';
            }

            if (collapsible.tagName === 'TABLE') {
                return ':scope > tbody > tr';
            } else if (collapsible.tagName === 'UL' || collapsible.tagName === 'OL') {
                return ':scope > li:not(.collapsible-control)';
            }

            return '';
        }

        /**
         * Check whether the given collapsible needs to collapse
         *
         * @param collapsible  The given collapsible container element
         *
         * @returns {boolean}
         */
        canCollapse(collapsible) {
            if (this.isDetails(collapsible)) {
                return collapsible.querySelector(':scope > summary') !== null;
            }

            let rowSelector = this.getRowSelector(collapsible);
            if (!! rowSelector) {
                let collapseAfter = Number(collapsible.dataset.collapseAfter)
                if (isNaN(collapseAfter)) {
                    collapseAfter = Number(collapsible.dataset.visibleRows);
                    if (isNaN(collapseAfter)) {
                        collapseAfter = this.defaultVisibleRows;
                    }

                    collapseAfter *= 2;
                }

                if (collapseAfter === 0) {
                    return true;
                }

                return collapsible.querySelectorAll(rowSelector).length > collapseAfter;
            } else {
                let maxHeight = Number(collapsible.dataset.visibleHeight);
                if (isNaN(maxHeight)) {
                    maxHeight = this.defaultVisibleHeight;
                } else if (maxHeight === 0) {
                    return true;
                }

                let actualHeight = collapsible.scrollHeight - parseFloat(
                    window.getComputedStyle(collapsible).getPropertyValue('padding-top')
                );

                return actualHeight >= maxHeight * 2;
            }
        }

        /**
         * Calculate the height the given collapsible should have when collapsed
         *
         * @param collapsible
         */
        calculateCollapsedHeight(collapsible) {
            let height;

            if (this.isDetails(collapsible)) {
                return -1;
            }

            let rowSelector = this.getRowSelector(collapsible);
            if (!! rowSelector) {
                height = collapsible.scrollHeight;
                height -= parseFloat(window.getComputedStyle(collapsible).getPropertyValue('padding-bottom'));

                let visibleRows = Number(collapsible.dataset.visibleRows);
                if (isNaN(visibleRows)) {
                    visibleRows = this.defaultVisibleRows;
                }

                let rows = Array.from(collapsible.querySelectorAll(rowSelector)).slice(visibleRows);
                for (let i = 0; i < rows.length; i++) {
                    let row = rows[i];

                    if (row.previousElementSibling === null) { // very first element
                        height -= row.offsetHeight;
                        height -= parseFloat(window.getComputedStyle(row).getPropertyValue('margin-top'));
                    } else if (i < rows.length - 1) { // every element but the last one
                        let prevBottomBorderAt = row.previousElementSibling.offsetTop;
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
                    && ! ('hasExternalToggle' in collapsible.dataset)
                    && (! collapsible.nextElementSibling
                        || ! collapsible.nextElementSibling.matches(collapsible.dataset.toggleElement))
                ) {
                    let toggle = collapsible.querySelector(collapsible.dataset.toggleElement);
                    height += toggle.offsetHeight; // TODO: Very expensive at times. (50ms+) Check why!
                    height += parseFloat(window.getComputedStyle(toggle).getPropertyValue('margin-top'));
                    height += parseFloat(window.getComputedStyle(toggle).getPropertyValue('margin-bottom'));
                }
            }

            return height;
        }

        /**
         * Collapse the given collapsible
         *
         * @param collapsible The given collapsible container element
         * @param toHeight {int} The height in pixels to collapse to
         */
        collapse(collapsible, toHeight) {
            if (this.isDetails(collapsible)) {
                collapsible.open = false;
            } else {
                collapsible.style.display = 'block';
                collapsible.style.height = toHeight + 'px';
                collapsible.style.paddingBottom = '0px';

                if ('hasExternalToggle' in collapsible.dataset) {
                    document.getElementById(collapsible.dataset.toggleElement).classList.add('collapsed');
                }
            }

            collapsible.classList.add('collapsed');
        }

        /**
         * Expand the given collapsible
         *
         * @param   collapsible    The given collapsible container element
         */
        expand(collapsible) {
            collapsible.classList.remove('collapsed');

            if (this.isDetails(collapsible)) {
                collapsible.open = true;
            } else {
                collapsible.style.display = '';
                collapsible.style.height = '';
                collapsible.style.paddingBottom = '';

                if ('hasExternalToggle' in collapsible.dataset) {
                    document.getElementById(collapsible.dataset.toggleElement).classList.remove('collapsed');
                }
            }
        }

        /**
         * Get whether the given collapsible is a <details> element
         *
         * @param collapsible
         *
         * @return {Boolean}
         */
        isDetails(collapsible) {
            return collapsible.tagName === 'DETAILS';
        }
    }

    Icinga.Behaviors.Collapsible = Collapsible;

})(Icinga);
