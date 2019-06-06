/*! Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for collapsible containers. Creates collapsible containers from `<div class="collapsible-container">…</div>`
     * or <div class="collapsible-container"><div class="collapsible">…</div></div>`
     *
     * @param  icinga  Icinga  The current Icinga Object
     */
    var CollapsibleContainer = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#col2', this.onRendered, this);
        this.on('click', '.collapsible-container .collapsible-control, .collapsible-table-container .collapsible-control', this.onControlClicked, this);

        this.icinga = icinga;
        this.expandedContainers = {};
        this.defaultNumOfRows = 2;
        this.defaultHeight = 36;
    };
    CollapsibleContainer.prototype = new Icinga.EventListener();

    /**
     * Initializes all collapsible-container elements. Triggered on rendering of a container.
     *
     * @param event  Event  The `onRender` event triggered by the rendered container
     */
    CollapsibleContainer.prototype.onRendered = function(event) {
        var _this = event.data.self;

        $(event.target).find('.collapsible-container[data-collapsible-id]').each(function() {
            var $this = $(this);

            if ($this.find('.collapsible').length > 0) {
                $this.addClass('has-collapsible');
                if ($this.find('.collapsible').innerHeight() > ($this.data('height') || _this.defaultHeight)) {
                    $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                    $this.addClass('can-collapse');
                }
            } else {
                if ($this.innerHeight() > ($this.data('height') || _this.defaultHeight)) {
                    $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                    $this.addClass('can-collapse');
                }
            }
            _this.updateCollapsedState($this);
        });

        $(event.target).find('.collapsible-table-container[data-collapsible-id]').each(function() {
            var $this = $(this);

            if ($this.find('.collapsible').length > 0) {
                $this.addClass('has-collapsible');
                if ($this.find('tr').length > ($this.attr('data-numofrows') || _this.defaultNumOfRows)) {
                    $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                    $this.addClass('can-collapse');
                }

                if ($this.find('li').length > ($this.attr('data-numofrows') || _this.defaultNumOfRows)) {
                    $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                    $this.addClass('can-collapse');
                }
            }
            _this.updateCollapsedState($this);
        });
    };

    /**
     * Event handler for clocking collapsible control. Toggles the collapsed state of the respective container.
     *
     * @param event  Event  The `onClick` event triggered by the clicked collapsible-control element
     */
    CollapsibleContainer.prototype.onControlClicked = function(event) {
        var _this = event.data.self;
        var $target = $(event.target);
        var $c = $target.closest('.collapsible-container, .collapsible-table-container');

        _this.expandedContainers[$c.attr('id')] = $c.is('.collapsed');

        console.log(_this.expandedContainers);

        _this.updateCollapsedState($c);
    };

    /**
     * Renders the collapse state of the given container. Adds or removes class `collapsible` to containers and sets the
     * height.
     *
     * @param $container  jQuery  The given collapsible container element
     */
    CollapsibleContainer.prototype.updateCollapsedState = function($container) {
        var $collapsible;
        if ($container.hasClass('has-collapsible')) {
            $collapsible = $container.find('.collapsible');
        } else {
            $collapsible = $container;
        }

        var collapsibleId = $container.data('collapsibleId');
        if (typeof this.expandedContainers[collapsibleId] === 'undefined') {
            this.expandedContainers[collapsibleId] = false;
        }

        if (this.expandedContainers[collapsibleId]) {
            $container.removeClass('collapsed');
            $collapsible.css({ maxHeight: 'none' });
        } else {
            if ($container.hasClass('can-collapse')) {
                $container.addClass('collapsed');
                if ($container.hasClass('collapsible-container')) {
                    $collapsible.css({maxHeight: $container.data('height') || this.defaultHeight});
                }
                if ($container.hasClass('collapsible-table-container')) {
                    $collapsible.css({maxHeight: ($container.data('numofrows') || this.defaultNumOfRows) * $container.find('tr').height()});
                }
            }
        }
    };

    Icinga.Behaviors.collapsibleContainer = CollapsibleContainer;

})(Icinga, jQuery);
