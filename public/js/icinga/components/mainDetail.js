/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

/**
 * Main-Detail layout behaviour as described in
 * https://wiki.icinga.org/display/cranberry/Frontend+Components#FrontendComponents-Behaviour
 *
 */
define(['jquery','logging','icinga/util/async'],function($,log,async) {
    "use strict";

    var MainDetailBehaviour = function() {

        var onOuterLinkClick = function(ev) {
            var a = $(ev.currentTarget),
                target = a.attr("target"),
                href = a.attr("href");
            ev.stopImmediatePropagation();
            collapseDetailView();
    async.loadToTarget("icinga-main",href);
    return false;
};

var onLinkTagClick = function(ev) {

    var a = $(ev.currentTarget),
        target = a.attr("target"),
        href = a.attr("href");

    // check for protocol://
    if(/^[A-Z]{2,10}\:\/\//i.test(href)) {
        window.open(href);
        ev.stopImmediatePropagation();
        return false;
    }

    // check for link in table header
    if(a.parents('th').length > 0) {
        ev.stopImmediatePropagation();
        return false;
    }

    if(typeof target === "undefined") {
        if(a.parents("#icinga-detail").length) {
            log.debug("Parent is detail, loading into detail");
            async.loadToTarget("icinga-detail",href);
        } else {
            log.debug("Parent is not detail, loading into main");
            async.loadToTarget("icinga-main",href);
        }
    } else {
        switch(target) {
            case "body":
                        async.loadToTarget("body", href);
                        break;
                    case "main":
                        async.loadToTarget("icinga-main",href);
                        collapseDetailView();
                        break;
                    case "detail":
                        log.debug("Target: detail");
                        async.loadToTarget("icinga-detail",href);
                        break;
                    case "popup":
                        log.debug("No target");
                        async.loadToTarget(null,href);
                        break;
                    default:
                        return true;
                }
            }
            ev.stopImmediatePropagation();
            return false;
        };

        var expandDetailView = function() {
            $("#icinga-detail").parents(".collapsed").removeClass('collapsed');
       };

        var collapseDetailView = function(elementInDetailView) {
            $("#icinga-detail").parents(".layout-main-detail").addClass('collapsed');
        };

        this.expandDetailView = expandDetailView;
        this.collapseDetailView = collapseDetailView;

        this.eventHandler = {
            '.layout-main-detail * a' : {
                'click' : onLinkTagClick
            },

            '.layout-main-detail .icinga-container#icinga-detail' : {
                'focus' : expandDetailView
            }
        };
    };
    return new MainDetailBehaviour();
});
