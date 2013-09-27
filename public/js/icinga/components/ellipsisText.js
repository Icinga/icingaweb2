/*global Icinga:false, Modernizr: false, document: false, History: false, define:false require:false base_url:false console:false */
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 */
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Icinga app/ellipsisText component
 *
 * This component adds ellipsis with expand functionality
 * to content.
 *
 * Example:
 *
 * <pre>
 * <code>
 *   <span data-icinga-component="app/ellipsisText">
 *     A very long example text
 *    </span>
 * </code>
 * </pre>
 */
define(['jquery'],
    function($, logger, componentLoader, URI) {
        "use strict";

        /**
         * Test if a css3 ellipsis is avtive
         *
         * @param {Element} element
         * @returns {boolean}
         */
        var activeEllipsis = function(element) {
            return (element.offsetWidth < element.scrollWidth);
        };

        /**
         * Add classes to element to create a css3 ellipsis
         *
         * Parent elements width is used to calculate containing width
         * and set target element width to a fixed one.
         *
         * @param {Element} target
         * @constructor
         */
        var EllipsisText = function(target) {
            var parentWidth = $(target).parent().width();

            $(target).width(parentWidth)
                .css('overflow', 'hidden')
                .css('text-overflow', 'ellipsis')
                .css('display', 'block')
                .css('white-space', 'nowrap');

            if (activeEllipsis(target)) {
                $(target).wrap('<a></a>')
                    .css('cursor', 'pointer');

                $(target).parent()
                    .attr('data-icinga-ellipsistext', 'true')
                    .attr('data-content', $(target).html())
                    .popover({
                        trigger : 'manual',
                        html : true,
                        placement : 'auto'
                    })
                    .click(function(e) {
                        e.stopImmediatePropagation();
                        $('a[data-icinga-ellipsistext=\'true\']').popover('hide');
                        $(e.currentTarget).popover('toggle');
                    });
            }
        };

        return EllipsisText;
    }
);