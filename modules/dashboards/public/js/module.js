;(function(Icinga) {

    'use strict';

    let dataIcingaId;

    let Dashboards = function(module) {
        /**
         * The Icinga.Module instance
         */
        this.module = module;

        this.initialize();
    };

    Dashboards.prototype = {

        /**
         * This will be triggerd once the document is ready
         */
        initialize: function()
        {
            this.module.on('rendered', '.container', this.onRendered);
            this.module.on("dragstart", '.dashlet-sortable', this.onDragStart);
            this.module.on("dragend", '.dashlet-sortable', this.onDragend);
            this.module.on("dragleave", '.dashlet-sortable', this.onDragLeave);
            this.module.on("dragover",'.dashlet-sortable', this.onDragOver);
            this.module.on("drop",'.dashlet-sortable', this.onDrop);
            this.module.on('click', 'button', this.updateDashletsWidth);
            this.module.icinga.logger.debug('Dashboards module loaded');
        },

        /**
         * Get all dashlets from the parent class name .dashboard that have .dashlet-sortable as class name
         *
         * @param $target
         *
         * @returns {jQuery}
         */
        getDashlets: function ($target) {
            return $target.parent('.dashboard').find('.dashlet-sortable');
        },

        /**
         * This function is fired when an element tries to move
         */
        onDragStart: function (event) {
            let $target = $(event.target);

            event.originalEvent.dataTransfer.setData('text', event.target.id);
            $target.addClass('draggable-element');
            this.getDashlets($target).addClass('drag-active');
            dataIcingaId = $target.attr('data-icinga-dashlet-id');
        },

        /**
         * Fired when an element is move over a draggable target
         */
        onDragOver: function (event) {
            let $target = $(event.target);

            // Prevent standard action to allow the drop event and
            // Don't addClass drag-over if this container is the same as the Container
            // being dragged (has class dragged-element)
            if ($target.attr('data-icinga-dashlet-id') !== dataIcingaId) {
                event.preventDefault();
                event.stopPropagation();
                $target.addClass('drag-over');
            }
        },

        /**
         * Fired when an element leaves the draggable target
         */
        onDragLeave: function (event) {
            let $target = $(event.target);

            $target.removeClass('drag-over');
        },

        onDragend: function (event) {
            let $target = $(event.target);

            // reset the transparency
            $target.removeClass('draggable-element');
            $target.removeClass('drag-over');
            this.getDashlets($target).removeClass('drag-active');
        },

        onDrop: function (event) {
            let $target = $(event.target);

            // Prevent standard action to allow the drop event
            event.preventDefault();
            const dragTarget = event.originalEvent.dataTransfer.getData('text');
            // Save a ref on the draggable element
            const draggableElement = $('#' + dragTarget);
            // Get all following siblings of each element in the set of matched elements filtered by draggableElement
            if ($target.nextAll().filter(draggableElement).length) {
                $(draggableElement).insertBefore($target);
            } else {
                $(draggableElement).insertAfter($target);
            }

            $target.removeClass('drag-over');

            this.updateDashletsPosition($target);
        },

        updateDashletsPosition: function ($target) {
            let dashletIds = '';

            this.getDashlets($target).each(function () {
                // Fetch the id Attribute from the html tag
                dataIcingaId = $(this).attr("data-icinga-dashlet-id");
                // Check if the dashletsId that we create is still empty
                if (dashletIds === " ") {
                    dashletIds = dataIcingaId;
                }
                else {
                    dashletIds = dashletIds+","+dataIcingaId;
                }
            });

            //TODO: If your Icinga Web 2 is hosted from somewhere else,
            // you have to replace localhost with the url that Icinga Web 2 is hosted from
            $.ajax({
                url: 'http://localhost/icingaweb2/dashboards/dashlets/drop',
                method: 'POST',
                data: {'dashletIds': dashletIds},
                dataType: 'text',
                success: function (data) {
                    console.log(data, 'Order saved Successfully!');
                },
                error: function (jqxHR, textStatus, error) {
                    console.log(error);
                }
            });
        },

        updateDashletsWidth: function (event) {
            let $dashlets = this.getDashlets($(event.target));

            // Make a div resizable when the Try it button is pressed
            this.getDashlets($dashlets).css({resize: 'both'});

            this.getDashlets($dashlets).on('mousedown', function () {
                let $dashlet = $(this);

                $dashlet.one('mouseup', function () {
                    let newWidthInPercent = $dashlet.width() / $dashlet.parent().width() * 100;

                    if (newWidthInPercent > 66.6) {
                        $dashlet.animate().css({width: 99.9 + '%'}, 1500);
                        $dashlet.animate().css({height: 670 + 'px'}, 2000);
                    } else if (newWidthInPercent > 33.3 && newWidthInPercent < 66.6) {
                        $dashlet.animate().css({width: 66.6 + '%'}, 1500);
                        $dashlet.animate().css({height: 670 + 'px'}, 2000);
                    } else if (newWidthInPercent < 33.3) {
                        $dashlet.animate().css({width: 33.3 + '%'}, 1500);
                        $dashlet.animate().css({height: 670 + 'px'}, 2000);
                    } else {
                        console.log('It is not allowed to resize a div above 99.9%!');
                    }

                    let widthAfterResize = '';

                    $dashlet.each(function () {

                        let currentWidth = $(this).width() / $(this).parent().width() * 100;

                        let defaultWidth = $(this).attr('data-icinga-dashlet-col');
                        dataIcingaId = $(this).attr('data-icinga-dashlet-id');

                        if (currentWidth !== defaultWidth) {
                            widthAfterResize = currentWidth;
                        } else {
                            widthAfterResize = widthAfterResize + ',' + currentWidth;
                        }
                    });

                    //TODO: If your Icinga Web 2 is hosted from somewhere else,
                    // you have to replace localhost with the url that Icinga Web 2 is hosted from
                    $.ajax({
                        url: 'http://localhost/icingaweb2/dashboards/dashlets/resize',
                        method: 'POST',
                        data: {'defaultWidth': widthAfterResize, 'dashletIds': dataIcingaId},
                        dataType: 'text',
                        success: function () {
                            console.log('Dashlets width updated successfully');
                        }
                    });
                });
            });
        },

        onRendered: function(e)
        {
        }
    };

    Icinga.availableModules.dashboards = Dashboards;

}(Icinga));
