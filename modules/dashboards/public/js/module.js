;(function (Icinga) {

    let dataIcingaId;

    let Dashboards = function (module) {
        /**
         * The Icinga.Module instance
         */
        this.module = module;

        this.initialize();
    };

    Dashboards.prototype = {

        initialize: function () {
            this.module.on('rendered', '.container', this.onRendered);
            this.module.on("dragstart", '.dashlet-sortable', this.onDragStart);
            this.module.on("dragend", '.dashlet-sortable', this.onDragend);
            this.module.on("dragleave", '.dashlet-sortable', this.onDragLeave);
            this.module.on("dragover", '.dashlet-sortable', this.onDragOver);
            this.module.on("drop", '.dashlet-sortable', this.onDrop);
            this.module.on('click', 'button#Click-me', this.updatePostsWidth);
            this.module.icinga.logger.debug('Dashboards module loaded');
        },

        getDashlets: function ($target) {
            return $target.parent('.dashboard').find('.dashlet-sortable');
        },

        onDragStart: function (event) {
            let $target = $(event.target);

            event.originalEvent.dataTransfer.setData('text', event.target.id);
            $target.addClass('draggable-element');
            this.getDashlets($target).addClass('drag-active');
            dataIcingaId = $target.attr('data-icinga-dashlet-id');
        },

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

            this.updatePostsPosition($target);
        },

        updatePostsPosition: function ($target) {
            let postIds = '';

            this.getDashlets($target).each(function () {
                // Fetch the id Attribute from the html tag
                dataIcingaId = $(this).attr("data-icinga-dashlet-id");
                // Check if the postIds that we create is still empty
                if (postIds === " ") {
                    postIds = dataIcingaId;
                } else {
                    postIds = postIds + "," + dataIcingaId;
                }
            });

            $.ajax({
                url: 'http://localhost:8080/icingaweb2/dashboards/dashboards/update',
                method: 'POST',
                data: {'postIds': postIds},
                dataType: 'text',
                success: function (data) {
                    console.log(data, 'Order saved Successfully!');
                },
                error: function (jqxHR, textStatus, error) {
                    console.log(error);
                }
            });
        },

        updatePostsWidth: function (event) {
            let $dashlets = this.getDashlets($(event.target));
            // Make a div resizable when the Add event button is pressed
            this.getDashlets($dashlets).css({resize: 'both'});

            this.getDashlets($dashlets).on('mousedown', function () {
                let $dashlet = $(this);

                $dashlet.one('mouseup', function () {
                    let newWidthInPercent = $dashlet.width() / $dashlet.parent().width() * 100;

                    if (newWidthInPercent > 66.6) {
                        $dashlet.animate({width: 99.9 + '%'}, 1500);
                        $dashlet.animate({height: 580 + 'px'}, 100);
                    } else if (newWidthInPercent > 33.3 && newWidthInPercent < 66.6) {
                        $dashlet.animate({width: 66.6 + '%'}, 1500);
                        $dashlet.animate({height: 540 + 'px'}, 100);
                    } else if (newWidthInPercent < 33.3) {
                        $dashlet.animate({width: 33.3 + '%'}, 1500);
                        $dashlet.animate({height: 670 + 'px'}, 1000);
                    } else {
                        console.log('It is not allowed to resize a div above 99.9%!');
                    }

                    let DB_width = '';
                    $dashlet.each(function () {
                        let widthAfterResize = $(this).width() / $(this).parent().width() * 100;

                        let currentWidth = $(this).attr('data-icinga-dashlet-col');
                        dataIcingaId = $(this).attr('data-icinga-dashlet-id');

                        if (widthAfterResize !== currentWidth) {
                            DB_width = widthAfterResize;
                        } else {
                            DB_width = DB_width + ',' + widthAfterResize;
                        }
                    });

                    $.ajax({
                        url: 'http://localhost:8080/icingaweb2/dashboards/dashboards/style',
                        method: 'POST',
                        data: {'DB_width': DB_width, 'ids': dataIcingaId},
                        dataType: 'text',
                        success: function () {
                            console.log('PostsWidth updated successfully');
                        }
                    });
                });
            });
        },

        onRendered: function (e) {
        }
    };

    Icinga.availableModules.dashboards = Dashboards;

}(Icinga));
