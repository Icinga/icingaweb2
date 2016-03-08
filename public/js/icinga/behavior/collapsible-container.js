/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    /**
     * Inject close button into collapsible-container and render its collapse
     * state
     *
     * @param {object} e Event
     */
    function onRendered(e) {
        if ( $(this).find(".collapsible-container").length > 0 ) {
            var $collapsibleContainer = $(this).find(".collapsible-container");
            var $this = $(this);
            var $b = null;
            if ($collapsibleContainer.children('.collapsible-control').length < 1 ) {
                $b = $('#collapsible-control-ghost').clone().removeAttr("id");
                $collapsibleContainer.append($b);
            } else {
                $b = $collapsibleContainer.children('.collapsible-control');
            }
            updateCollapseState($this, $collapsibleContainer, $b);
        }
    }

    /**
     * Render collapsible container state
     *
     * @param {jQuery} $container               The column container
     * @param {jQuery} $collapsibleContainer    The collapsible container
     * @param {jQuery} $button                  The toggle button
     */
    function updateCollapseState($container, $collapsibleContainer, $button) {
        if ($container.hasClass("custom-vars-collapsed")) {
            $collapsibleContainer.height("auto");
            $button.text($button.data("labels").def);
        } else {
            $collapsibleContainer.height(40);
            $button.text($button.data("labels").collapsed);
        }
    }

    function onControlClicked(e) {
        var $col2 = $('#col2');
        $col2.toggleClass("custom-vars-collapsed");
        updateCollapseState($col2, $(this).closest('.collapsible-container'), $(this));
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for adding collapsing behavior for containers
     *
     * The collapsibleContainer behavior adds a button to respective containers
     * with whom the user can collapse and open the container.
     *
     * @param {Icinga} icinga
     *
     * @constructor
     */
    var collapsibleContainer = function(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#col2', onRendered, this);
        this.on('click', '#col2 .collapsible-container .collapsible-control', onControlClicked, this);
    };

    collapsibleContainer.prototype = new Icinga.EventListener();

    Icinga.Behaviors.collapsibleContainer = collapsibleContainer;
})(Icinga, jQuery);
