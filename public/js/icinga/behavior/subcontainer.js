/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

/**
 * Icinga.Behavior.SubContainer
 *
 * A toggleable container
 */
(function(Icinga, $) {

    "use strict";

    function SubContainer(icinga) {
        Icinga.EventListener.call(this, icinga);

        this.on('rendered', this.onRendered, this);
    }

    SubContainer.prototype = Object.create(Icinga.EventListener.prototype);

    SubContainer.prototype.onRendered = function(e) {
        var root = $(e.target);

        if (root.hasClass("collapsible")) {
            return;
        }

        var loader = icinga.loader;

        $(".subcontainer", root).each(function() {
            var subcontainer = $(this);
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
                        collapsible.empty();
                    }
                });

                collapsibles.toggleClass("collapsed");
            });
        });
    };

    Icinga.Behaviors = Icinga.Behaviors || {};

    Icinga.Behaviors.SubContainer = SubContainer;

}) (Icinga, jQuery);
