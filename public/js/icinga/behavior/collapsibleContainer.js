/*! Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

;(function(Icinga, $) {

    'use strict';

    var expandedContainers = [];
    var maxLength = 32;
    var defaultNumOfRows = 2;
    var defaultHeight = 36;

    function CollapsibleContainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', '#col2', this.onRendered, this);
        this.on('click', '.collapsible-container .collapsible-control, .collapsible-table-container .collapsible-control', this.onControlClicked, this);
    }

    CollapsibleContainer.prototype = new Icinga.EventListener();

    CollapsibleContainer.prototype.onRendered = function(event) {
        $(event.target).find('.collapsible-container').each(function() {
            var $this = $(this);

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
            updateCollapsedState($this);
        });

        $(event.target).find('.collapsible-table-container').each(function() {
            var $this = $(this);

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
            updateCollapsedState($this);
        });
    };

    CollapsibleContainer.prototype.onControlClicked = function(event) {
        var $target = $(event.target);
        var $c = $target.closest('.collapsible-container, .collapsible-table-container');

        if ($c.hasClass('collapsed')) {
            if (expandedContainers.length > maxLength - 1) {
                expandedContainers.shift();
            }
            expandedContainers.push($c.attr('id'));
        } else {
            expandedContainers.splice(expandedContainers.indexOf($c.attr('id')), 1);
        }

        updateCollapsedState($c);
    };

    function updateCollapsedState($container, listener) {
        var $collapsible;
        if ($container.hasClass('has-collapsible')) {
            $collapsible = $container.find('.collapsible');
        } else {
            $collapsible = $container;
        }
        if (expandedContainers.indexOf($container.attr('id')) > -1) {
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
