/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.Subcontainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    'use strict';

    var columnContainerIdPattern = /^col/;
    var subcontainerBackups = Object.create(null);

    subcontainerBackups.col1 = Object.create(null);
    subcontainerBackups.col2 = Object.create(null);
    subcontainerBackups.col3 = Object.create(null);

    function onRenderedCollapsible(event) {
        backupSubcontainer($(event.target));
    }

    function onRenderedDefault(event) {
        var loader = icinga.loader;

        $(event.target).find('.subcontainer').each(function() {
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
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        subcontainerBackups[backupPath.columnId][backupPath.subcontainerId] = collapsible;
    }

    function unbackupSubcontainer(collapsible) {
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        delete subcontainerBackups[backupPath.columnId][backupPath.subcontainerId];
    }

    function restoreSubcontainer(collapsible) {
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        if (typeof subcontainerBackups[backupPath.columnId][backupPath.subcontainerId] !== 'undefined') {
            collapsible.replaceWith(subcontainerBackups[backupPath.columnId][backupPath.subcontainerId]);
        }
    }

    function getPathToBackup(collapsible) {
        var backupPath = {columnId: null, subcontainerId: null};

        collapsible.parents().each(function() {
            var parent = $(this);

            if (parent.hasClass('subcontainer')) {
                if (backupPath.subcontainerId === null) {
                    backupPath.subcontainerId = parent.attr('id');
                }
            } else if (parent.hasClass('container') && columnContainerIdPattern.exec(parent.attr('id')) !== null
                && backupPath.columnId === null) {
                backupPath.columnId = parent.attr('id');
            }
        });

        return backupPath;
    }

    function Subcontainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    Subcontainer.prototype = Object.create(Icinga.EventListener.prototype);

    Subcontainer.prototype.onRendered = function(event) {
        if ($(event.target).hasClass('collapsible')) {
            onRenderedCollapsible(event);
        } else {
            onRenderedDefault(event);
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.Subcontainer = Subcontainer;

}) (Icinga, jQuery);
