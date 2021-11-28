;(function (Icinga, $) {
    'use strict';

    Icinga.Behaviors = Icinga.Behaviors || {};

    const NEW_DASHBOARD_FORM = '<div class="content-wrapper">\n' +
        '   <p class="create-new-dashboard-label">Pinned ' +
        '       <span class="count-pinned-items"></span> Dashlets.' +
        '   </p>\n' +
        '   <form id="homePaneForm" name="CreateNewDashboardFromPinnedDashlets" class="icinga-form icinga-controls inline" ' +
        '       action="dashboards/new-dashboard" method="post" data-base-target="_next">\n' +
        '       <input type="hidden" class="hidden-input" name="formUID" id="CreateNewDashboardFromPinnedDashlets">\n' +
        '       <input type="submit" class="create-new-dashboard" value="Create New Dashboard">\n' +
        '   </form>\n' +
        '</div>';

    var Dashboard = function (icinga) {
        Icinga.EventListener.call(this, icinga);

        this.icinga = icinga;

        this.on('rendered', '.dashboard > .container', this.onRendered, this);
        this.on('click', '.pin-dashlets', this.onPinDashlet, this);
        this.on('dblclick', '.pin-dashlets', this.onUnPinDashlet, this);
        this.on('click', '.create-new-dashboard', this.onCreateNewDashboard, this);
    };

    Dashboard.prototype = new Icinga.EventListener();

    Dashboard.prototype.onPinDashlet = function (event) {
        let $target = $(event.target);
        let $tr = $target.closest('tr');

        event.preventDefault();
        event.stopPropagation();

        if ($tr.hasClass('pinned-item')) {
            return;
        }

        $target.closest('i').addClass('pinned-icon')
        $tr.addClass('pinned-item');

        let _this = event.data.self;
        _this.renderUserHint();
    };

    Dashboard.prototype.onUnPinDashlet = function (event) {
        let $target = $(event.target);
        let $tr = $target.closest('tr');

        event.preventDefault();
        event.stopPropagation();

        if (! $tr.hasClass('pinned-item')) {
            return;
        }

        $target.closest('i').removeClass('pinned-icon')
        $tr.removeClass('pinned-item');

        let _this = event.data.self;
        _this.renderUserHint();
    };

    Dashboard.prototype.renderUserHint = function () {
        let $content = $('.content');
        let pinnedItems = $content.find('tr.pinned-item');

        if (! pinnedItems.length) {
            (pinnedItems.children('.pinned-icon')).removeClass('.pinned-icon');
            $content.children('.content-wrapper').remove();
        } else if (! $content.children('.content-wrapper').length) {
            let $wrapper = $(NEW_DASHBOARD_FORM);
            $wrapper.find('.count-pinned-items').text(pinnedItems.length);
            $content.prepend($wrapper);
        } else {
            $content
                .find('.count-pinned-items')
                .text(pinnedItems.length);
        }

        let dashlets = [];
        $('.common-table').find('.pinned-item').each((index, element) => {
            dashlets.push($(element).data('dashlet-name'))
        });

        $content.find('.content-wrapper > form').find('.hidden-input').val(dashlets);
    };

    Dashboard.prototype.onCreateNewDashboard = function (event) {
        let _this = event.data.self;
        let $form = $(event.currentTarget).closest('form');
        $form.attr('action', 'dashboards/new-dashboard?dashlets=' + $form.find('.hidden-input').val())

        _this.icinga.loader.submitForm($form);
    };

    Dashboard.prototype.onRendered = function (event) {

    };

    Icinga.Behaviors.Dashboard = Dashboard;

})(Icinga, jQuery);
