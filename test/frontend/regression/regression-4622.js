/**
 * Config: Warn about unsaved changes before leaving current dialog (Bug #4622)
 *
 * It's not possible to test if the confirmation dialog really appears, but as this is a rather simple
 * event listener (that is also tested with mocha), the state preservation is tested here
 *
 * This test performs the following steps
 * - Log in and open the logging dialog form
 * - Wait for component initialisation
 * - Modify the first input field and test for the data-icinga-form-modified flag to be set
 * - Modify the an autosubmit field (debug log), wait for the textfield to appear or disappear and test if
 *   the modified attribute is set on the server side.
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
 * Login to the instance
 */
icinga.performLogin();

/**
 * Test if the modified attribute is correctly set when altering input
 */
casper.thenOpen('./config/logging', function() {
    "use strict";

    this.test.assertExists('form[name=form_config_logging]', 'Assert the logging configuration form being displayed');
    this.test.assertEquals(
        this.getElementAttribute('form[name=form_config_logging]', 'data-icinga-form-modified'),
        "",
        'Assert a form to initially have no modified flag'
    );
    // Wait for the component to initialize
    this.wait(1000, function() {
        this.sendKeys('form[name=form_config_logging] input#logging_app_target', 'somewhere');
        this.test.assertEquals(
            this.getElementAttribute('form[name=form_config_logging]', 'data-icinga-form-modified'),
            "true",
            'Assert a form to initially be marked as modified when changed'
        );
    });
});


/**
 * Test if the modified flag will be set on the server side
 */
casper.then(function() {
    "use strict";

    var checkbox = this.getElementAttribute('form[name=form_config_logging] input#logging_debug_enable', 'value');
    this.click('form[name=form_config_logging] input#logging_debug_enable');
    // determine whether the text input field appears after the click or not
    var waitFn = (checkbox === '0' ? this.waitForSelector : this.waitWhileSelector).bind(this);
    waitFn('form[name=form_config_logging] input#logging_debug_target', function() {
        this.test.assertEquals(
            this.getElementAttribute('form[name=form_config_logging]', 'data-icinga-form-modified'),
            "true",
            'Assert modify flag to be set on the server side if using an autosubmit field'
        );
    }, function() {
        this.test.fail('Debug textfield appearcance didn\'t occur after click');
    });

});

/**
 * Run the tests
 */
casper.run(function() {
    "use strict";

    this.test.done();
}); 