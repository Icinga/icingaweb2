/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Subcontainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    'use strict';

    var subcontainerBackups = {};

    function backupSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null) {
            subcontainerBackups[subcontainerId] = collapsible;
        }
    }

    function removeSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null) {
            delete subcontainerBackups[subcontainerId];
        }
    }

    function restoreSubcontainer(collapsible) {
        var subcontainerId = getSubcontainerId(collapsible);

        if (subcontainerId !== null && typeof subcontainerBackups[subcontainerId] !== 'undefined') {
            var backup = subcontainerBackups[subcontainerId];
            backup.detach();
            collapsible.replaceWith(backup);
            collapsible.remove();
        }
    }

    function getSubcontainerId(collapsible) {
        var subcontainerId = collapsible.parents('.subcontainer').attr('id');

        return typeof subcontainerId === 'undefined' ? null : 'subcontainer-' + subcontainerId;
    }

    function Subcontainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);

        $(document).on('click', '.subcontainer .subcontainer-toggle', this.onToggle);
    }

    Subcontainer.prototype = new Icinga.EventListener();

    Subcontainer.prototype.onRendered = function(event) {
        var eventTarget = $(event.target);

        if (eventTarget.hasClass('subcontainer-content')) {
            backupSubcontainer(eventTarget);
        } else {
            eventTarget.find('.subcontainer').each(function() {
                var subcontainerContent = $(this).find('.subcontainer-content').first();

                if (subcontainerContent.length > 0) {
                    restoreSubcontainer(subcontainerContent);
                }
            });
        }
    };

    Subcontainer.prototype.onToggle = function(event) {
        var collapsible = $(event.target).parents('.subcontainer').first().find('.subcontainer-content').first();

        if (collapsible.length > 0) {
            if (collapsible.hasClass('collapsed')) {
                icinga.loader.loadUrl(
                    collapsible.data('icingaUrl'),
                    collapsible,
                    undefined,
                    undefined,
                    undefined,
                    true
                );
            } else {
                removeSubcontainer(collapsible);

                collapsible.empty();
            }

            collapsible.toggleClass('collapsed');
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Subcontainer = Subcontainer;

}) (Icinga, jQuery);
