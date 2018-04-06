/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.FilterEditor
 *
 * Initially expanded, but collapsable subtrees
 */
(function(Icinga, $) {

    'use strict';

    var containerId = /^col(\d+)$/;
    var filterEditors = {};

    function FilterEditor(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('beforerender', this.beforeRender, this);
        this.on('rendered', this.onRendered, this);
    }

    FilterEditor.prototype = new Icinga.EventListener();

    FilterEditor.prototype.beforeRender = function(event) {
        var $container = $(event.target);
        var match = containerId.exec($container.attr('id'));

        if (match !== null) {
            var id = match[1];
            var subTrees = {};
            filterEditors[id] = subTrees;

            $container.find('.tree .handle').each(function () {
                var $li = $(this).closest('li');

                subTrees[$li.find('select').first().attr('name')] = $li.hasClass('collapsed');
            });
        }
    };

    FilterEditor.prototype.onRendered = function(event) {
        var $container = $(event.target);
        var match = containerId.exec($container.attr('id'));

        if (match !== null) {
            var id = match[1];

            if (typeof filterEditors[id] !== "undefined") {
                var subTrees = filterEditors[id];
                delete filterEditors[id];

                $container.find('.tree .handle').each(function () {
                    var $li = $(this).closest('li');
                    var name = $li.find('select').first().attr('name');
                    if (typeof subTrees[name] !== "undefined" && subTrees[name] !== $li.hasClass('collapsed')) {
                        $li.toggleClass('collapsed');
                    }
                });

                icinga.ui.fixControls($container);
            }
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.FilterEditor = FilterEditor;

}) (Icinga, jQuery);
