/*! Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

;(function (Icinga, $) {

    'use strict';

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

        /**
         * Base route
         *
         * @type {string}
         */
        this.baseUrl = icinga.config.baseUrl

        /**
         * Dashlet container id which is currently being dragged
         *
         * @type {null|string}
         */
        this.containerId = null;

        // Register event handlers for drag and drop functionalities
        this.on('dragstart', '.dashboard > .dashlet-sortable', this.onDragStart, this);
        this.on('dragover', '.dashboard > .dashlet-sortable', this.onDragOver, this);
        this.on('dragleave', '.dashboard > .dashlet-sortable', this.onDragLeave, this);
        this.on('dragend', '.dashboard > .dashlet-sortable', this.onDragEnd, this);
        this.on('drop', '.dashboard > .dashlet-sortable', this.onDrop, this);
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

        if (! $target.hasClass('dashlet-sortable')) {
            return false;
        }

        event.originalEvent.dataTransfer.setData('text', $target.attr('id'));
        $target.addClass('draggable-element');

        _this.containerId = $target.attr('id');
        // Prevents child elements from being the target of pointer events
        $('.dashboard.content').children('.dashlet-sortable').addClass('drag-active');
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

        if (! $target.hasClass('dashlet-sortable')) {
            $target = $target.closest('.dashlet-sortable');
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

        $target.removeClass('drag-over');
    };

    /**
     * Remove all class names added dynamically
     *
     * @param event {Event} The `drag end` event triggered when the draggable element is released
     */
    Dashboard.prototype.onDragEnd = function (event) {
        let $target = $(event.target);

        $target.removeClass('draggable-element');
        $target.removeClass('drag-over');

        // The draggable is now released, so we have to remove the class to enable the pointer events again
        $('.dashboard.content').children('.dashlet-sortable').removeClass('drag-active');
    };

    /**
     * Event handler for on drop action
     *
     * @param event {Event} The `ondrop` event triggered when the dashlet has been dropped
     */
    Dashboard.prototype.onDrop = function (event) {
        let $target = $(event.target);
        let _this = event.data.self;

        // Prevents from being dropped in a child elements
        if (! $target.hasClass('dashlet-sortable')) {
            $target = $target.closest('.dashlet-sortable');
        }

        // Prevent default behaviors to allow the drop event
        event.preventDefault();

        const dragTarget = event.originalEvent.dataTransfer.getData('text');
        const draggable = $('#' + dragTarget);

        // If the target element has been located before the draggable element,
        // insert the draggable before the target element otherwise after it
        if ($target.nextAll().filter(draggable).length) {
            $(draggable).insertBefore($target);
        } else {
            $(draggable).insertAfter($target);
        }

        // Draggable element is now dropped, so drag-over class must also be removed
        $target.removeClass('drag-over');

        _this.postReorderedDashlets();
    };

    /**
     * Set up a request with the reordered containers and post the data to the controller
     */
    Dashboard.prototype.postReorderedDashlets = function () {
        let _this = this,
            $dashboard = $('.dashboard.content'),
            $paneAndHome = $dashboard.data('icinga-pane').split('|', 2),
            $dashlets = [];

        $dashboard.children('.dashlet-sortable').each(function () {
            $dashlets.push($(this).data('icinga-dashlet'));
        });

        let $pane = $paneAndHome.pop();
        let $home = $paneAndHome.pop();

        let data = {[$home]: { [$pane]: $dashlets }};

        $.ajax({
            context     : _this,
            type        : 'post',
            url         : _this.baseUrl + '/dashboards/reorder-dashlets',
            headers     : {'Accept': 'application/json'},
            contentType : 'application/json',
            data        : JSON.stringify(data)
        });
    };

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
