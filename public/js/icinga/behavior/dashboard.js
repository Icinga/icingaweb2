;(function (Icinga, $) {
    'use strict';

    let dashletData;

    Icinga.Behaviors = Icinga.Behaviors || {};

    function Dashboard(icinga) {
        Icinga.EventListener.call(this, icinga);
        this.icinga = icinga;

        this.on('rendered', '.dashboard > .container', this.onRendered, this);
        this.on('dragstart', '.dashboard > .dashlet-sortable', this.onDragStart, this);
        this.on('dragend', '.dashboard > .dashlet-sortable', this.onDragEnd, this);
        this.on('dragleave', '.dashboard > .dashlet-sortable', this.onDragLeave, this);
        this.on('dragover', '.dashboard > .dashlet-sortable', this.onDragOver, this);
        this.on('drop', '.dashboard > .dashlet-sortable', this.onDrop, this);
        this.on('click', '.dashboard > .dashlet-sortable', this.onUpdateDashlet, this);
    }

    Dashboard.prototype = new Icinga.EventListener();

    Dashboard.prototype.onDragStart = function (event) {
        let $target = $(event.target);

        if (typeof $target.data('icinga-dashlets') === 'undefined') {
            $target = $target.parent();
        }

        dashletData = $target.data('icinga-dashlets');

        event.originalEvent.dataTransfer.setData('text', $target.attr('id'));

        $target.addClass('drag-active');
    };

    Dashboard.prototype.onDragLeave = function (event) {
        let $target = $(event.target);

        if (typeof $target.data('icinga-dashlets') === 'undefined') {
            $target = $target.parent();
        }

        $target.removeClass('drag-over');
    };

    Dashboard.prototype.onDragEnd = function (event) {
        let $target = $(event.target);

        if (typeof $target.data('icinga-dashlets') === 'undefined') {
            $target = $target.parent();
        }

        $target.removeClass('drag-over');
        $target.removeClass('drag-active');
    };

    Dashboard.prototype.onDragOver = function (event) {
        let $target = $(event.target);

        if (typeof $target.data('icinga-dashlets') === 'undefined') {
            $target = $target.parent();
        }

        let currentId = $target.data('icinga-dashlets');

        if (typeof currentId !== 'undefined' && currentId !== dashletData) {
            event.preventDefault();
            event.stopPropagation();
            $target.addClass('drag-over');
        }
    };

    Dashboard.prototype.onDrop = function (event) {
        let $target = $(event.target);

        if (typeof $target.data('icinga-dashlets') === 'undefined') {
            $target = $target.parent();
        }

        event.preventDefault();

        const dragTarget = event.originalEvent.dataTransfer.getData('text');
        const draggableElement = $('#' + dragTarget);

        if ($target.nextAll().filter(draggableElement).length) {
            $(draggableElement).insertBefore($target);
        } else {
            $(draggableElement).insertAfter($target);
        }

        $target.removeClass('drag-over');
        let dashlets = '';

        $target.parent().children('.dashlet-sortable').each(function () {
            let $current = $(this).data('icinga-dashlets');

            if (dashlets === '') {
                dashlets = $current;
            } else {
                dashlets += ',' + $current;
            }
        });

        var $url = $target.baseURI;
        $.ajax({
            url: ($url + 'dashlets/update-priority').replace('undefined', ''),
            method: 'POST',
            data: {'dashletsData': dashlets},
            dataType: 'text',
            success: function (data) {
                console.log(data, 'Order saved Successfully');
            },
            error: function (jqxHR, textStatus, error) {
                console.error(error);
            }
        });
    };

    Dashboard.prototype.onUpdateDashlet = function (event) {
        let $dashlets = event.target.parent().children('.dashlet-sortable');

        $dashlets.css({resize: 'both'});
        $dashlets.on('mousedown', function () {
            let $dashlet = $(this);

            $dashlet.on('mouseup', function () {

            });
        });
    };

    Dashboard.prototype.onRendered = function (event) {

    };

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
