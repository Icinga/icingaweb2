/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.SubContainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    "use strict";

    var columnContainerIdPattern = /^col/;

    function SubContainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    SubContainer.prototype = Object.create(Icinga.EventListener.prototype);

    SubContainer.prototype.onRendered = function(e) {
        if ($(e.target).hasClass("collapsible")) {
            SubContainer.prototype.onRenderedCollapsible.call(this, e);
        } else {
            SubContainer.prototype.onRenderedDefault.call(this, e);
        }
    };

    SubContainer.prototype.onRenderedCollapsible = function(e) {
        SubContainer.prototype.backupSubContainer($(e.target));
    };

    SubContainer.prototype.onRenderedDefault = function(e) {
        var loader = icinga.loader;

        $(".subcontainer", $(e.target)).each(function() {
            var subcontainer = $(this);

            $(".collapsible", subcontainer).first().each(function() {
                SubContainer.prototype.restoreSubContainer($(this));
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
                        SubContainer.prototype.unbackupSubContainer(collapsible);

                        collapsible.empty();
                    }
                });

                collapsibles.toggleClass("collapsed");
            });
        });
    };

    SubContainer.prototype.backupSubContainer = function(collapsible) {
        var backupPath = SubContainer.prototype.getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        SubContainer.prototype.provisionPathToBackup(backupPath);

        window.SubContainerBackups[backupPath.columnId][backupPath.subcontainerId] = collapsible;
    };

    SubContainer.prototype.unbackupSubContainer = function(collapsible) {
        var backupPath = SubContainer.prototype.getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        SubContainer.prototype.provisionPathToBackup(backupPath);

        delete window.SubContainerBackups[backupPath.columnId][backupPath.subcontainerId];
    };

    SubContainer.prototype.restoreSubContainer = function(collapsible) {
        var backupPath = SubContainer.prototype.getPathToBackup(collapsible);

        if (backupPath.subcontainerId === null || backupPath.columnId === null) {
            return;
        }

        SubContainer.prototype.provisionPathToBackup(backupPath);

        if (typeof window.SubContainerBackups[backupPath.columnId][backupPath.subcontainerId] !== "undefined") {
            collapsible.replaceWith(window.SubContainerBackups[backupPath.columnId][backupPath.subcontainerId]);
        }
    };

    SubContainer.prototype.provisionPathToBackup = function(backupPath) {
        if (typeof window.SubContainerBackups === 'undefined') {
            window.SubContainerBackups = Object.create(null);
        }

        if (typeof window.SubContainerBackups[backupPath.columnId] === "undefined") {
            window.SubContainerBackups[backupPath.columnId] = Object.create(null);
        }
    };

    SubContainer.prototype.getPathToBackup = function(collapsible) {
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
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.SubContainer = SubContainer;

}) (Icinga, jQuery);
