/*global Icinga:false, document: false, define:false require:false base_url:false console:false */

/**
 * ActionTable behaviour as described in
 * https://wiki.icinga.org/display/cranberry/Frontend+Components#FrontendComponents-ActionTable
 *
 * @TODO: Row selection
 */
define(['jquery','logging','icinga/util/async'],function($,log,async) {
    "use strict";

    var ActionTableBehaviour = function() {
        var onTableHeaderClick;

        var TABLE_BASE_MATCHER = '.icinga-container table.action';
        var linksInActionTable  = TABLE_BASE_MATCHER+" tbody tr > a";
        var actionTableRow      = TABLE_BASE_MATCHER+" tbody tr";
        var headerRow           = TABLE_BASE_MATCHER+" > th a";
        var searchField         = ".icinga-container .actiontable.controls input[type=search]";


        onTableHeaderClick = function (ev) {
            var target = ev.currentTarget,
                href = $(target).attr('href'),
                destination;
            if ($(target).parents('.layout-main-detail').length) {
                if ($(target).parents("#icinga-main").length) {
                    destination = 'icinga-main';
                }
                else {
                    destination = 'icinga-detail';
                }

            } else {
                destination = 'icinga-main';
            }
            async.loadToTarget(destination, href);
            ev.preventDefault();
            ev.stopImmediatePropagation();
            return false;
        };

        var onLinkTagClick = function(ev) {

            var target = ev.currentTarget,
                href = $(target).attr('href'),
                destination;
            if ($(target).parents('.layout-main-detail').length) {
                destination = 'icinga-detail';
            } else {
                destination = 'icinga-main';
            }
            async.loadToTarget(destination,href);
            ev.preventDefault();
            ev.stopImmediatePropagation();
            return false;

        };

        var onTableRowClick = function(ev) {
            ev.stopImmediatePropagation();

            var target = $(ev.currentTarget),
                href = target.attr('href'),
                destination;
            $('tr.active',target.parent('tbody').first()).removeClass("active");
            target.addClass('active');

            // When the tr has a href, act like it is a link
            if(href) {
                ev.currentTarget = target.first();
                return onLinkTagClick(ev);
            }
            // Try to find a designated row action
            var links = $("a.row-action",target);
            if(links.length) {
                ev.currentTarget = links.first();
                return onLinkTagClick(ev);
            }

            // otherwise use the first anchor tag
            links = $("a",target);
            if(links.length) {
                ev.currentTarget = links.first();
                return onLinkTagClick(ev);
            }

            log.debug("No target for this table row found");
            return false;
        };

        var onSearchInput = function(ev) {
            ev.stopImmediatePropagation();
            var txt = $(this).val();
        };

        this.eventHandler = {};
        this.eventHandler[linksInActionTable] = {
            'click' : onLinkTagClick
        };
        this.eventHandler[actionTableRow] = {
            'click' : onTableRowClick
        };
        this.eventHandler[headerRow] = {
            'click' : onTableHeaderClick
        };
        this.eventHandler[searchField] = {
            'keyup' : onSearchInput
        };

        this.enable = function() {

        };
    };

    return new ActionTableBehaviour();

});