/*global define:false */
/* bpapp.js */
// TODO: Will be moved to module, loader instead of Apache Match voodo is
//       still missing right now

define(['jquery', 'icinga/icinga'], function ($, Icinga) {
    'use strict';

    return {
        /**
         * Tell Icinga about our event handlers
         */
        eventHandlers: {
            'table.businessprocess th.bptitle': {
                'mouseover': 'titleMouseOver',
                'mouseout' : 'titleMouseOut'
            },
            'table.businessprocess th': {
                'click': 'titleClicked'
            }
        },
        /**
         * Add 'hovered' class to hovered title elements
         *
         * TODO: Skip on tablets
         */
        titleMouseOver: function (event) {
            event.stopPropagation();
            var el = $(event.delegateTarget);
            el.addClass('hovered');
        },
        /**
         * Remove 'hovered' class from hovered title elements
         *
         * TODO: Skip on tablets
         */
        titleMouseOut: function (event) {
            event.stopPropagation();
            var el = $(event.delegateTarget);
            el.removeClass('hovered');
        },
        /**
         * Handle clicks on operator or title element
         *
         * Title shows subelement, operator unfolds all subelements
         */
        titleClicked: function (event) {
            event.stopPropagation();
            var el       = $(event.delegateTarget),
                affected = [];
            if (el.hasClass('operator')) {
                affected = el.closest('table').children('tbody')
                    .children('tr.children').children('td').children('table');

                // Only if there are child BPs
                if (affected.find('th.operator').length < 1) {
                    affected = el.closest('table');
                }
            } else {
                affected = el.closest('table');
            }
            affected.each(function (key, el) {
                var bptable = $(el).closest('table');
                bptable.toggleClass('collapsed');
                if (bptable.hasClass('collapsed')) {
                    bptable.find('table').addClass('collapsed');
                }
            });
            el.closest('.icinga-container').data('icingaparams', {
                // TODO: remove namespace
                opened: Icinga.module('bpapp').listOpenedBps()
            });
        },
        /**
         * Get a list of all currently opened BPs.
         *
         * Only get the deepest nodes to keep requests as small as possible
         */
        listOpenedBps: function () {
            var ids = [];
            $('.bpapp').find('table').not('.collapsed').each(function (key, el) {
                if ($(el).find('table').not('.collapsed').length === 0) {
                    var search  = true,
                        this_id = $(el)[0].id,
                        cnt     = 0,
                        current = el,
                        parent;
                    while (search && cnt < 40) {
                        cnt++;
                        parent = $(current).parent().closest('table')[0];
                        if (!parent || $(parent).hasClass('bpapp')) {
                            search = false;
                        } else {
                            current = parent;
                            this_id = parent.id + '_' + this_id;
                        }
                    }
                    if (this_id) {
                        ids.push(this_id);
                    }
                }
            });
            return ids;
        }
    };
});

