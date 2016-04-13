/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    /**
     * Add a close button to #col1 and #col2, if layout has two columns
     *
     * @param {object} e Event
     */
    function onRendered(e) {
        if ($('#layout').hasClass('twocols')) {
            if ($('#col1 .controls > .close-container-control').length < 1) {
                var $b = $('#close-container-control-ghost');
                $('#col1 .controls').append($b.clone().removeAttr('id'));
            }
            if ($('#col2 .controls > .close-container-control').length < 1) {
                var $b = $('#close-container-control-ghost');
                $('#col2 .controls').append($b.clone().removeAttr('id'));
            }
        }
    }

    /**
     * Remove close buttons from #col1 and #col2
     *
     * @param {object} e Event
     */
    function onColumnClosed(e) {
	    if (!$('#layout').hasClass('twocols')) {
		    $('#col1 .controls > .close-container-control').remove();
		    $('#col2 .controls > .close-container-control').remove();
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for adding and removing close buttons to columns
     *
     * The ContainerToggles behavior listens for render and close-column events for adding and removing
     * the close buttons.
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var ContainerToggles = function(icinga) {
        Icinga.EventListener.call(this, icinga);
        this.on('rendered', '#col1, #col2', onRendered, this);
		this.on('close-column', '#col1, #col2', onColumnClosed, this);
    };

    ContainerToggles.prototype = new Icinga.EventListener();

    Icinga.Behaviors.ContainerToggles = ContainerToggles;
})(Icinga, jQuery);
