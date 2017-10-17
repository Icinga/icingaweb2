/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.SubContainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    "use strict";

    var columnContainerIdPattern = /^col/;
    var subContainerBackups = Object.create(null);

    subContainerBackups.col1 = Object.create(null);
    subContainerBackups.col2 = Object.create(null);
    subContainerBackups.col3 = Object.create(null);

    function onRenderedCollapsible(event) {
        backupSubContainer($(event.target));
    }

    function onRenderedDefault(event) {
        var loader = icinga.loader;

        $(".subcontainer", $(event.target)).each(function() {
            var subcontainer = $(this);

            $(".collapsible", subcontainer).first().each(function() {
                restoreSubContainer($(this));
            });

            var collapsibles = $(".collapsible", subcontainer).first();

            $(".toggle", subcontainer).on("click", function() {
                collapsibles.each(function() {
                    var collapsible = $(this);

                    if (collapsible.hasClass("collapsed")) {
                        loader.loadUrl(
                            collapsible.data('icingaUrl'),
                            collapsible,
                            undefined,
                            undefined,
                            undefined,
                            true
                        );
                    } else {
                        unbackupSubContainer(collapsible);

                        collapsible.empty();
                    }
                });

                collapsibles.toggleClass("collapsed");
            });
        });
    }

    function backupSubContainer(collapsible) {
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        subContainerBackups[backupPath.columnId][backupPath.subcontainerId] = collapsible;
    }

    function unbackupSubContainer(collapsible) {
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        delete subContainerBackups[backupPath.columnId][backupPath.subcontainerId];
    }

    function restoreSubContainer(collapsible) {
        var backupPath = getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        if (typeof subContainerBackups[backupPath.columnId][backupPath.subcontainerId] !== "undefined") {
            collapsible.replaceWith(subContainerBackups[backupPath.columnId][backupPath.subcontainerId]);
        }
    }

    function getPathToBackup(collapsible) {
        var backupPath = {columnId: null, subcontainerId: null};

        collapsible.parents().each(function() {
            var parent = $(this);

            if (parent.hasClass("subcontainer")) {
                if (backupPath.subcontainerId === null) {
                    backupPath.subcontainerId = parent.attr("id");
                }
            } else if (parent.hasClass("container") && columnContainerIdPattern.exec(parent.attr("id")) !== null
                && backupPath.columnId === null) {
                backupPath.columnId = parent.attr("id");
            }
        });

        return backupPath;
    }

    function SubContainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    SubContainer.prototype = Object.create(Icinga.EventListener.prototype);

    SubContainer.prototype.onRendered = function(event) {
        if ($(event.target).hasClass("collapsible")) {
            onRenderedCollapsible(event);
        } else {
            onRenderedDefault(event);
        }
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.SubContainer = SubContainer;

}) (Icinga, jQuery);
