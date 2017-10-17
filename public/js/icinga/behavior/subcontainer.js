/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Subcontainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    'use strict';

    var subcontainerBackups = Object.create(null);

    function onRenderedCollapsible(event, eventTarget) {
        backupSubcontainer(eventTarget);
    }

    function onRenderedDefault(event, eventTarget) {
        var loader = icinga.loader;

        eventTarget.find('.subcontainer').each(function() {
            var subcontainer = $(this);

            subcontainer.find('.collapsible').first().each(function() {
                restoreSubcontainer($(this));
            });

            var collapsibles = subcontainer.find('.collapsible').first();

            subcontainer.find('.toggle').on('click', function() {
                collapsibles.each(function() {
                    var collapsible = $(this);

                    if (collapsible.hasClass('collapsed')) {
                        loader.loadUrl(
                            collapsible.data('icingaUrl'),
                            collapsible,
                            undefined,
                            undefined,
                            undefined,
                            true
                        );
                    } else {
                        unbackupSubcontainer(collapsible);

                        collapsible.empty();
                    }
                });

                collapsibles.toggleClass('collapsed');
            });
        });
    }

    function backupSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null) {
            subcontainerBackups[subcontainerId] = collapsible;
        }
    }

    function unbackupSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null) {
            delete subcontainerBackups[subcontainerId];
        }
    }

    function restoreSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null && typeof subcontainerBackups[subcontainerId] !== 'undefined') {
            collapsible.replaceWith(subcontainerBackups[subcontainerId]);
        }
    }

    function getSubcontainerId(collapsible) {
        var subcontainerId = null;

        collapsible.parents('subcontainer').each(function() {
            if (subcontainerId === null) {
                subcontainerId = $(this).attr('id');
            }
        });

        return subcontainerId;
    }

    function Subcontainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    Subcontainer.prototype = Object.create(Icinga.EventListener.prototype);

    Subcontainer.prototype.onRendered = function(event) {
        var eventTarget = $(event.target);

        if (eventTarget.hasClass('collapsible')) {
            onRenderedCollapsible(event, eventTarget);
        } else {
            onRenderedDefault(event, eventTarget);
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Subcontainer = Subcontainer;

}) (Icinga, jQuery);
