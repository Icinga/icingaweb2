/*! Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

;(function (Icinga, $) {

    'use strict';

    try {
        var Sortable = require('icinga/icinga-php-library/vendor/Sortable')
    } catch (e) {
        console.warn('Unable to provide Sortable. Library not available:', e);
        return;
    }

    /**
     * Possible type of widgets this behavior is being applied to
     *
     * @type {object}
     */
    const WIDGET_TYPES = { Dashlet : 'Dashlets', Dashboard : 'Dashboards', DashboardHome : 'Homes' };

    Icinga.Behaviors = Icinga.Behaviors || {};

    /**
     * Behavior for the enhanced Icinga Web 2 dashboards
     *
     * @param {Icinga} icinga The current Icinga Object
     *
     * @constructor
     */
    var Dashboard = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;

        this.on('rendered', '#main > .container', this.onRendered, this);
        this.on('end', '.dashboard-settings, .dashboard-list-control, .dashlet-list-item', this.elementDropped, this);
        // This is for the normal dashboard/dashlets view
        this.on('end', '.dashboard.content', this.elementDropped, this);
    };

    Dashboard.prototype = new Icinga.EventListener();

    /**
     * Get the widget type of the given element
     *
     * @param {HTMLElement} target
     *
     * @returns {null|string}
     */
    Dashboard.prototype.getTypeFor = function (target) {
        if (target.matches('.dashboard-settings')) {
            return WIDGET_TYPES.DashboardHome;
        } else if (target.matches('.dashboard-item-list')) {
            return WIDGET_TYPES.Dashboard;
        } else if (target.matches('.dashlet-item-list') || target.matches('.dashboard.content')) {
            return WIDGET_TYPES.Dashlet;
        }

        return null;
    };

    /**
     * Set up a request with the reordered widget and post the data to the controller
     *
     * @param event
     *
     * @returns {boolean}
     */
    Dashboard.prototype.elementDropped = function (event) {
        let _this = event.data.self,
            orgEvt = event.originalEvent,
            data = {};

        if (orgEvt.to === orgEvt.from && orgEvt.newIndex === orgEvt.oldIndex) {
            return false;
        }

        let item = orgEvt.item;
        switch (_this.getTypeFor(orgEvt.to)) {
            case WIDGET_TYPES.DashboardHome: {
                let home = item.dataset.icingaHome;
                data[home] = orgEvt.newIndex;
                break;
            }
            case WIDGET_TYPES.Dashboard: {
                let pane = item.dataset.icingaPane,
                    home = orgEvt.to.closest('.home-list-control').dataset.icingaHome;
                if (orgEvt.to !== orgEvt.from) {
                    data.originals = {
                        originalHome : orgEvt.from.closest('.home-list-control').dataset.icingaHome,
                        originalPane : pane
                    };
                }

                data[home] = { [pane] : orgEvt.newIndex };
                break;
            }
            case WIDGET_TYPES.Dashlet: {
                let dashlet = item.dataset.icingaDashlet,
                    pane,
                    home;

                if (orgEvt.to.matches('.dashboard.content')) {
                    let parentData = orgEvt.to.dataset.icingaPane.split('|', 2);
                    home = parentData.shift();
                    pane = parentData.shift();

                    data.redirectPath = 'dashboards';
                } else { // Dashboard manager view
                    let parent = orgEvt.to.closest('.dashboard-list-control');
                    pane = parent.dataset.icingaPane;
                    home = parent.closest('.home-list-control').dataset.icingaHome;

                    if (orgEvt.to !== orgEvt.from) {
                        let parent = orgEvt.from.closest('.dashboard-list-control');
                        data.originals = {
                            originalHome : parent.closest('.home-list-control').dataset.icingaHome,
                            originalPane : parent.dataset.icingaPane
                        }
                    }
                }

                dashlet = { [dashlet] : orgEvt.newIndex };
                data[home] = { [pane] : dashlet };
            }
        }

        if (Object.keys(data).length) {
            data.Type = _this.getTypeFor(orgEvt.to);
            if (! data.hasOwnProperty('originals')) {
                data.originals = null;
            }

            if (! data.hasOwnProperty('redirectPath')) {
                data.redirectPath = 'dashboards/settings';
            }

            data = { dashboardData : JSON.stringify(data) };
            let url = _this.icinga.config.baseUrl + '/dashboards/reorder-widgets';
            _this.icinga.loader.loadUrl(url, $('#col1'), data, 'post');
        }
    };

    /**
     * Get whether the given element is a valid target of the drag & drop events
     *
     * @param to
     * @param from
     * @param item
     * @param event
     *
     * @returns {boolean}
     */
    Dashboard.prototype.isValid = function (to, from, item, event) {
        if (typeof from.options.group === 'undefined' || typeof to.options.group === 'undefined') {
            return false;
        }

        return from.options.group.name === to.options.group.name;
    };

    Dashboard.prototype.onRendered = function (e) {
        let _this = e.data.self;
        $(e.target).find('.dashboard-settings, .dashboard.content, .dashboard-item-list, .dashlet-item-list').each(function () {
            let groupName = _this.getTypeFor(this),
                draggable,
                handle;

            switch (groupName) {
                case WIDGET_TYPES.DashboardHome: {
                    groupName = WIDGET_TYPES.DashboardHome;
                    draggable = '.home-list-control';
                    handle = '.home-list-control > h1';
                    break;
                }
                case WIDGET_TYPES.Dashboard: {
                    groupName = WIDGET_TYPES.Dashboard;
                    draggable = '.dashboard-list-control';
                    handle = '.dashboard-list-control > h1'
                    break;
                }
                case WIDGET_TYPES.Dashlet: {
                    groupName = WIDGET_TYPES.Dashlet;
                    if (this.matches('.dashboard.content')) {
                        draggable = '> .container';
                    } else {
                        draggable = '.dashlet-list-item';
                    }

                    handle = draggable;
                }
            }

            let options = {
                scroll     : true,
                invertSwap : true,
                delay      : 100,
                dataIdAttr : 'id',
                direction  : 'vertical',
                draggable  : draggable,
                handle     : handle,
                group      : {
                    name : groupName,
                    put  : _this['isValid'],
                }
            };

            Sortable.create(this, options);
        });
    };

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
