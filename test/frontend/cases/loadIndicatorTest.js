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
 *  loadIndicator test
 *
 *  Test for feature #4400
 *  https://dev.icinga.org/issues/4400
 *
 *  Steps:
 *  - Request host list
 *  - Click on a row
 *  - Test for load indicator
 *  - Test new url loaded
 *  - Test for non existing load indicator
 **/

/**
 *  The icinga util object
 *
 * @type object
 */
var icinga = require('./icingawebtest');

/**
 * The casperjs object
 *
 * @type Casper
 */
var casper = icinga.getTestEnv();

icinga.performLogin();

/**
 * Login with valid credentials
 */
casper.thenOpen('/monitoring/list/hosts', function() {
    this.test.assertExists('div#icingadetail');
    this.test.assertExists('div#icingamain');
});

casper.then(function() {
    this.waitForSelector('div[data-icinga-component="app/mainDetailGrid"]', function() {
        this.wait(1000, function() {
            this.test.assertExists('div[data-icinga-component="app/mainDetailGrid"] table.table-condensed tr:nth-child(2) td:nth-child(3) a');
            this.click('div[data-icinga-component="app/mainDetailGrid"] table.table-condensed tr:nth-child(2) td:nth-child(3) a')
        });
    }, function() {
        this.test.fail('mainDetailGrid not found');
    });
});

casper.then(function() {
    this.waitForSelector('div#icingadetail div.load-indicator', function() {
        this.test.assertExists('div#icingadetail div.load-indicator div.label');
        this.waitFor(function check() {
            return this.getCurrentUrl().search(/\?detail=/) > -1;
        }, function then() {
            this.test.assertDoesntExist('div.load-indicator');
        }, function timeout() {
            this.test.fail('Url did not changed detail pane');
        });
    })
});

/**
 * Run the tests
 */
casper.run(function() {
    this.test.done();
});
