/*! Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

;(function (Icinga, $) {

    'use strict';

    /**
     * Possible type of widgets this behavior is being applied to
     *
     * @type {object}
     */
    const WIDGET_TYPES = { Dashlets : 'Dashlets', Dashboards : 'Dashboards', DashboardHomes : 'Homes' };

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for the enhanced Icinga Web 2 dashboards
     *
     * @param icinga {Icinga} The current Icinga Object
     *
     * @constructor
     */
    var Dashboard = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;

        /**
         * Type of the widget which is currently being sorted
         *
         * @type {string}
         */
        this.sortedWidgetType = WIDGET_TYPES.Dashlets;

        /**
         * Widget container id which is currently being dragged
         *
         * @type {null|string}
         */
        this.containerId = null;

        // Register event handlers for drag and drop functionalities
        this.on('dragstart', '.widget-sortable', this.onDragStart, this);
        this.on('dragover', '.widget-sortable', this.onDragOver, this);
        this.on('dragleave', '.widget-sortable', this.onDragLeave, this);
        this.on('dragend', '.widget-sortable', this.onDragEnd, this);
        this.on('drop', '.widget-sortable', this.onDrop, this);
    };

    Dashboard.prototype = new Icinga.EventListener();

    /**
     * A user tries to drag an element, so make sure it's sortable and setup the procedure
     *
     * @param event {Event} The `dragstart` event triggered when starting to drag the element
     *                      with a mouse click and begin to move it
     */
    Dashboard.prototype.onDragStart = function (event) {
        let _this = event.data.self;
        let $target = $(event.target);

        if (! _this.isDraggable($target)) {
            return false;
        }

        _this.containerId = $target.attr('id');
        $target.addClass('draggable-element');

        let $parent = $target.parent()[0];
        // Prevents child elements from being the target of pointer events
        $($parent).children('.widget-sortable').addClass('drag-active');
    };

    /**
     * Event handler for drag over
     *
     * Check that the target el is draggable and isn't the el itself
     * which is currently being dragged
     *
     * @param event {Event} The `drag over` event triggered when dragging over another dashlet
     */
    Dashboard.prototype.onDragOver = function (event) {
        let $target = $(event.target);
        let _this = event.data.self;

        // Moving an element arbitrarily elsewhere isn't allowed
        if (! _this.isDraggable($target) || ! _this.isDraggableSiblingOf($target)) {
            return false;
        }

        // Don't show mouse drop cursor if the target element is the draggable element
        if ($target.attr('id') !== _this.containerId) {
            event.preventDefault();
            event.stopPropagation();

            $target.addClass('drag-over');
        }
    };

    /**
     * The element doesn't get dragged over anymore, so just remove the drag-over class
     *
     * @param event {Event} The `drag leave` event triggered when dragging over a dashlet
     *                      and leaving without dropping the draggable element
     */
    Dashboard.prototype.onDragLeave = function (event) {
        let $target = $(event.target);
        let _this = event.data.self;

        if (! _this.isDraggable($target) || ! _this.isDraggableSiblingOf($target)) {
            return false;
        }

        $target.removeClass('drag-over');
    };

    /**
     * Remove all class names added dynamically
     *
     * @param event {Event} The `drag end` event triggered when the draggable element is released
     */
    Dashboard.prototype.onDragEnd = function (event) {
        let $target = $(event.target);
        let _this = event.data.self;

        if (! _this.isDraggable($target) || ! _this.isDraggableSiblingOf($target)) {
            return false;
        }

        $target.removeClass('draggable-element');
        $target.removeClass('drag-over');

        let $parent = $target.parent()[0];
        // The draggable is now released, so we have to remove the class to enable the pointer events again
        $($parent).children('.widget-sortable').removeClass('drag-active');
    };

    /**
     * Event handler for on drop action
     *
     * @param event {Event} The `ondrop` event triggered when the dashlet has been dropped
     */
    Dashboard.prototype.onDrop = function (event) {
        let $target = $(event.target);
        let _this = event.data.self;

        // Don't allow to drop an element arbitrarily elsewhere
        if (! _this.isDraggable($target) || ! _this.isDraggableSiblingOf($target)) {
            return false;
        }

        // Prevent default behaviors to allow the drop event
        event.preventDefault();
        event.stopPropagation();

        const draggable = $target.parent().children('#' + _this.containerId);

        // If the target element has been located before the draggable element,
        // insert the draggable before the target element otherwise after it
        if ($target.nextAll().filter(draggable).length) {
            draggable.insertBefore($target);
        } else {
            draggable.insertAfter($target);
        }

        // Draggable element is now dropped, so drag-over class must also be removed
        $target.removeClass('drag-over');

        if ($target.data('icinga-pane')) {
            _this.sortedWidgetType = WIDGET_TYPES.Dashboards;
        } else if ($target.data('icinga-home')) {
            _this.sortedWidgetType = WIDGET_TYPES.DashboardHomes;
        }

        _this.sendReorderedWidgets($target);
    };

    /**
     * Get whether the given element is draggable
     *
     * @param $target {jQuery}
     *
     * @returns {boolean}
     */
    Dashboard.prototype.isDraggable = function ($target) {
        return $target.attr('draggable');
    };

    /**
     * Get whether the given element is sibling of the element currently being dragged
     *
     * @param $target {jQuery}
     *
     * @returns {number}
     */
    Dashboard.prototype.isDraggableSiblingOf = function ($target) {
        return $target.parent().children('#' + this.containerId).length;
    };

    /**
     * Set up a request with the reordered containers and post the data to the controller
     *
     * @param $target {jQuery}
     */
    Dashboard.prototype.sendReorderedWidgets = function ($target) {
        let _this = this,
            data = {};

        switch (_this.sortedWidgetType) {
            case WIDGET_TYPES.DashboardHomes: {
                let $homes = [];
                $target.parent().children('.home-list-control.widget-sortable').each(function () {
                    let home = $(this);
                    if (typeof home.data('icinga-home') === 'undefined') {
                        _this.icinga.logger.error(
                            '[Dashboards] Dashboard home widget has no "icingaHome" data attribute registered: ',
                            home[0]
                        );
                        return;
                    }

                    $homes.push(home.data('icinga-home'));
                });

                data = { ...$homes };
                break;
            }
            case WIDGET_TYPES.Dashboards: {
                let $home, $panes = [];
                $target.parent().children('.dashboard-list-control.widget-sortable').each(function () {
                    let pane = $(this);
                    if (typeof pane.data('icinga-pane') === 'undefined') {
                        _this.icinga.logger.error(
                            '[Dashboards] Dashboard widget has no "icingaPane" data attribute registered: ',
                            pane[0]
                        );
                        return;
                    }

                    pane = pane.data('icinga-pane').split('|', 2);
                    if (! $home) {
                        $home = pane.shift();
                    }

                    $panes.push(pane.pop());
                });

                data[$home] = $panes;
                break;
            }
            case WIDGET_TYPES.Dashlets: {
                let $home, $pane, $dashlets = [];
                $target.parent().children('.widget-sortable').each(function () {
                    let dashlet = $(this);
                    if (typeof dashlet.data('icinga-dashlet') === 'undefined') {
                        _this.icinga.logger.error(
                            '[Dashboards] Dashlet widget has no "icingaDashlet" data attribute registered: ',
                            dashlet[0]
                        );
                        return;
                    }

                    if (! $home && ! $pane) {
                        let pane = dashlet.parent();
                        if (typeof pane.data('icinga-pane') === 'undefined') {
                            // Nested parents
                            pane = pane.parent();
                            if (typeof pane.data('icinga-pane') === 'undefined') {
                                _this.icinga.logger.error(
                                    '[Dashboards] Dashlet parent widget has no "icingaPane" data attribute registered: ',
                                    pane[0]
                                );
                                return;
                            }
                        }

                        pane = pane.data('icinga-pane').split('|', 2);
                        $home = pane.shift();
                        $pane = pane.shift();
                    }

                    $dashlets.push(dashlet.data('icinga-dashlet'));
                });

                if ($home && $pane) {
                    data[$home] = { [$pane] : $dashlets };
                }
            }
        }

        if (Object.keys(data).length) {
            data.Type = _this.sortedWidgetType;

            $.ajax({
                context     : _this,
                type        : 'post',
                url         : _this.icinga.config.baseUrl + '/dashboards/reorder-widgets',
                headers     : { 'Accept' : 'application/json' },
                contentType : 'application/json',
                data        : JSON.stringify(data),
            });
        }
    };

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
