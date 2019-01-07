/*! Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    var expandedContainers = [];
    var maxLength = 32;
    var defaultNumOfRows = 2;
    var defaultHeight = 36;

    /**
     * Behavior for collapsible containers. Creates collapsible containers from `<div class="collapsible-container">…</div>`
     * or <div class="collapsible-container"><div class="collapsible">…</div></div>`
     *
     * @param  icinga  Icinga  The current Icinga Object
     */

    function CollapsibleContainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#col2', this.onRendered, this);
        this.on('click', '.collapsible-container .collapsible-control, .collapsible-table-container .collapsible-control', this.onControlClicked, this);
    }

    CollapsibleContainer.prototype = new Icinga.EventListener();

    /**
     * Initializes all collapsible-container elements. Triggered on rendering of a container.
     *
     * @param event  Event  The `onRender` event triggered by the rendered container
     */
    CollapsibleContainer.prototype.onRendered = function(event) {
        $(event.target).find('.collapsible-container').each(function() {
            var $this = $(this);

            if ($this.data('collapsible-id') && $('[data-collapsible-id=' + $this.data('collapsible-id') + ']').length < 2) {
                if ($this.find('.collapsible').length > 0) {
                    $this.addClass('has-collapsible');
                    if ($this.find('.collapsible').innerHeight() > ($this.data('height') || defaultHeight)) {
                        $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                        $this.addClass('can-collapse');
                    }
                } else {
                    if ($this.innerHeight() > ($this.data('height') || defaultHeight)) {
                        $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                        $this.addClass('can-collapse');
                    }
                }
            }
            updateCollapsedState($this);
        });

        $(event.target).find('.collapsible-table-container').each(function() {
            var $this = $(this);

            if ($this.data('collapsible-id') && $('[data-collapsible-id=' + $this.data('collapsible-id') + ']').length < 2) {
                if ($this.find('.collapsible').length > 0) {
                    $this.addClass('has-collapsible');
                    if ($this.find('tr').length > ($this.attr('data-numofrows') || defaultNumOfRows)) {
                        $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                        $this.addClass('can-collapse');
                    }

                    if ($this.find('li').length > ($this.attr('data-numofrows') || defaultNumOfRows)) {
                        $this.append($('#collapsible-control-ghost').clone().removeAttr('id'));
                        $this.addClass('can-collapse');
                    }
                }
            }
            updateCollapsedState($this);
        });
    };

    /**
     * Event handler for clocking collapsible control. Toggles the collapsed state of the respective container.
     *
     * @param event  Event  The `onClick` event triggered by the clicked collapsible-control element
     */
    CollapsibleContainer.prototype.onControlClicked = function(event) {
        var $target = $(event.target);
        var $c = $target.closest('.collapsible-container, .collapsible-table-container');

        if ($c.hasClass('collapsed')) {
            if (expandedContainers.length > maxLength - 1) {
                expandedContainers.shift();
            }
            expandedContainers.push($c.data('collapsible-id'));
        } else {
            expandedContainers.splice(expandedContainers.indexOf($c.data('collapsible-id')), 1);
        }

        console.log(expandedContainers);

        updateCollapsedState($c);
    };

    /**
     * Renders the collapse state of the given container. Adds or removes class `collapsible` to containers and sets the
     * height.
     *
     * @param $container  jQuery  The given collapsible container element
     */
    function updateCollapsedState($container) {
        var $collapsible;
        if ($container.hasClass('has-collapsible')) {
            $collapsible = $container.find('.collapsible');
        } else {
            $collapsible = $container;
        }
        if (expandedContainers.indexOf($container.data('collapsible-id')) > -1) {
            $container.removeClass('collapsed');
            $collapsible.css({ maxHeight: 'none' });
        } else {
            if ($container.hasClass('can-collapse')) {
                $container.addClass('collapsed');
                if ($container.hasClass('collapsible-container')) {
                    $collapsible.css({maxHeight: $container.data('height') || defaultHeight});
                }
                if ($container.hasClass('collapsible-table-container')) {
                    $collapsible.css({maxHeight: ($container.data('numofrows') || defaultNumOfRows) * $container.find('tr').height()});
                }
            }
        }
    }

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.collapsibleContainer = CollapsibleContainer;

})(Icinga, jQuery);
