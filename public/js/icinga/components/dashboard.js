/*global Icinga:false, document: false, define:false require:false base_url:false console:false */
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Dashboard container, uses freetile for layout
 *
 */
define(['jquery', 'logging', 'URIjs/URI', 'icinga/componentLoader'], function($, log, URI, components) {
    'use strict';
    return function(parent) {
        this.dom = $(parent);
        var dashboardContainer = this.dom.parent('div');
        dashboardContainer.freetile();
        this.container = this.dom.children('.container');
        this.dashboardUrl = this.dom.attr('data-icinga-url');
        var reloadTimeout = null;

        /**
         * Refresh the container content and layout
         */
        this.refresh = function () {
            $.ajax({
                url: this.dashboardUrl,
                context: this
            })
                .done(function (response) {
                    this.container.empty();
                    this.container.html(response);
                    components.load();
                })
                .fail(function (response, reason) {
                    this.container.html(response.responseText);
                })
                .always(function () {
                    dashboardContainer.freetile('layout');
                    $(window).on('layoutchange', function() {
                        dashboardContainer.freetile('layout');
                    });
                    this.triggerRefresh();
                })
        };

        this.triggerRefresh = function() {
            if (reloadTimeout) {
                clearTimeout(reloadTimeout);
            }
            setTimeout(this.refresh.bind(this), 10000);
        };
        this.refresh();
    };
});
