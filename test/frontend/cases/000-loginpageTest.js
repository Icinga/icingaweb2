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
 *  Test case for the login page
 *
 *  Steps:
 *  - Request application root path
 *  - Assert login page to appear
 *  - Enter invalid credentials
 *  - Enter valid credentials
 *  - Reload page without credentials
 *  - Logout
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

/**
 * Test whether the login form exists and has valid input elements
 *
 * @param {testing} The casperjs testing module to perform assertions
 */
var assertLoginFormExists = function(test) {

    test.assertExists(
        'form#form_login',
        'Test whether the login form exists'
    );
    test.assertExists(
        'form#form_login input#username',
        'Test whether a username input field exists'
    );
    test.assertExists(
        'form#form_login input#password',
        'Test whether a password input field exists'
    );
    test.assertExists(
        'form#form_login input#submit',
        'Test whether a submit input field exists'
    );
};

/**
 * Request the initial application path
 */
casper.start('/', function() {
    if (this.getCurrentUrl() === 'about:blank') {
        this.die('Url can\'t be accessed');
    }
    this.test.assertTitle(
        "Icinga Web Login",
        "Test whether the login page (" + this.getCurrentUrl() + ") has a correct title"
    );
    assertLoginFormExists(this.test);
    this.test.assertDoesntExist(
        '#icinga_app_username',
        'Test if no username is set in the frontend after initial page load'
    );
});

/**
 * Login with invalid credentials
 */
casper.then(function() {
    this.fill('form#form_login', {
        'username' : 'no',
        'password' : 'existing_user'
    });
    this.click('form#form_login input#submit');
});

/**
 * Test if login failed and feedback is given
 */
casper.then(function() {
    this.test.assertTextExists(
        'Please provide a valid username and password',
        'Test if the user gets a note that authorization failed if providing wrong credentials'
    );
    assertLoginFormExists(this.test);
    this.test.assertDoesntExist(
        '#icinga_app_username',
        'Test if no username is set in the frontend after entering wrong credentials'
    );

});

/**
 * Login with valid credentials
 */
casper.then(function() {
    this.fill('form#form_login', icinga.getCredentials());
    this.click('form#form_login input#submit');
});

/**
 * Test if the login suceeded and the username is shown in the navigation bar
 */
casper.then(function() {
    this.test.assertTextDoesntExist(
        'Please provide a valid username and password',
        'Test if valid credentials don\'t end cause a note that credentials are wrong to appear'
    );
    this.test.assertSelectorHasText(
        '#icinga_app_nav_username',
        icinga.getCredentials().username,
        'Test if the username is set in the frontend after successful login'
    );
});

/**
 * Test if session is persisted after reloading the page
 */
casper.thenOpen('/', function() {
    this.test.assertSelectorHasText(
        '#icinga_app_nav_username',
        icinga.getCredentials().username,
        'Test if the username is still set if reloading the page via GET'
    );

    this.test.assertExists(
        '#icinga_app_nav_logout',
        'Test if the logout button exists'
    );

    this.test.assertExists(
        '#icinga_app_nav_useraction',
        'Test whether the dropdown for user specific actions exists'
    );
});

/**
 * Test if logout button is displayed when username is clicked and test for correct logout
 */
casper.then(function() {
    this.test.assertNotVisible(
        '#icinga_app_nav_logout',
        'Test if the logout button is hidden when not clicked'
    );

    this.wait(500, function() { // wait until everything is initialized, sometimes this takes a while
        this.click('#icinga_app_nav_useraction');
        this.waitUntilVisible('#icinga_app_nav_logout', function() {
            this.click('#icinga_app_nav_logout a');
            this.waitForSelector('form#form_login', function() {
                this.test.assertDoesntExist(
                    '#icinga_app_username',
                    'Test if no username is set in the frontend after logout'
                );
                assertLoginFormExists(this.test);
            });
        }, function() {
            this.test.assertVisible(
                '#icinga_app_nav_logout',
                'Test if the logout button is visible when click on username occurs'
            );
        }, 500);
    });
});

/**
 * Run the tests
 */
casper.run(function() {
    this.test.done();
});
