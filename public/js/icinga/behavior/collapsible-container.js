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
        var _this = e.data.self;
        var $containers = $('.collapsible-container');
        $containers.each(function() {
            var $container = $(this);
            if ($container.children('.collapsible-control').length < 1 ) {
                $container.append($('#collapsible-control-ghost').clone().removeAttr('id'));
            }
            updateCollapseState($container, _this);
        });
    }

    /**
     * Render collapsible container state
     *
     * @param {jQuery} $container   The collapsible container
     * @param {object} listener     The EventListener object
     */
    function updateCollapseState($container, listener) {
        var classes = listener.collapsedClasses;
        if ($container.hasClass('perf-data')) {
            if (classes.indexOf('perf-data') > -1) {
                var $button = $container.find('.collapsible-control');
                $container.addClass('collapsed');
                $container.find('.collapsible-control').text($button.data('labels').collapsed);
            } else {
                var $button = $container.find('.collapsible-control');
                $container.removeClass('collapsed');
                $container.find('.collapsible-control').text($button.data('labels').def);
            }
        }
        if ($container.hasClass('custom-vars')) {
            if (classes.indexOf('custom-vars') > -1) {
                var $button = $container.find('.collapsible-control');
                $container.addClass('collapsed');
                $container.find('.collapsible-control').text($button.data('labels').collapsed);
            } else {
                var $button = $container.find('.collapsible-control');
                $container.removeClass('collapsed');
                $container.find('.collapsible-control').text($button.data('labels').def);
            }
        }
    }

    /**
     * Toggle collapse state of collapsible containers
     *
     * @param {object} e Event
     */
    function onControlClicked(e) {
        var _this = e.data.self;
        var $parentCollapsible = $(e.target).closest('.collapsible-container');
        if ($parentCollapsible.hasClass('perf-data')) {
            if ( _this.collapsedClasses.indexOf('perf-data') > -1) {
                _this.collapsedClasses.splice(_this.collapsedClasses.indexOf('perf-data'), 1);
                $parentCollapsible.addClass('collapsed');
            } else {
                _this.collapsedClasses.push('perf-data');
                $parentCollapsible.removeClass('collapsed');
            }
        }
        if ($parentCollapsible.hasClass('custom-vars')) {
            if ( _this.collapsedClasses.indexOf('custom-vars') > -1) {
                _this.collapsedClasses.splice(_this.collapsedClasses.indexOf('custom-vars'), 1);
                $parentCollapsible.addClass('collapsed');
            } else {
                _this.collapsedClasses.push('custom-vars');
                $parentCollapsible.removeClass('collapsed');
            }
        }
        updateCollapseState($parentCollapsible, _this);
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
        this.collapsedClasses = ['perf-data', 'custom-vars'];
    };

    collapsibleContainer.prototype = new Icinga.EventListener();

    Icinga.Behaviors.collapsibleContainer = collapsibleContainer;
})(Icinga, jQuery);
