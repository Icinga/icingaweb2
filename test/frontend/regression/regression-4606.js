/**
 * Configuration: Show message that changes were saved successfully
 *
 * As a user I want to see that configuration changes were successful.
 *
 * This test performs the following steps
 *
 * - Login using the provided credentials
 * - Open the configuration dialog and change the timezone
 * - Save and test for a success bubble to appear
 * - Open the authentication dialog
 * - Open the edit link of the first backend
 * - Hit save and test for a success bubble to apper
 * - Open the logging dialog, hit save and test for a success bubble to appear
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
 * Open the config dialog and test if the form exists
 */
casper.thenOpen('/config', function() {
    this.test.assertExists(
        '#form_config_general',
        'Test whether the general settings dialog exists in the general form'
    );
    this.test.assertExists(
        '#form_config_general select#timezone',
        'Assert the timezone input to exist'
    );
});

/**
 * Change the timezone and submit
 */
casper.then(function() {
    this.test.assertDoesntExist(
        'div.alert.alert-success',
        'Assert no success notice existing when no changes have been done in the general form'
    );
    this.echo("Changing the default timezone");
    this.fill('#form_config_general', {
        'timezone': 'Europe/Minsk'
    });
    this.click('#form_config_general input#btn_submit');
});

/**
 * Check for the 'Successfully Update' information bubble
 */
casper.then(function() {
    this.echo("Clicked on save button of the general form, waiting for success");
    this.waitForSelector('div.alert.alert-success', function() {
        this.test.assertSelectorHasText(
            'div.alert.alert-success',
            'Config Sucessfully Updated',
            'Assert a success text to appear in the general form'
        );
    }, function() {
        this.test.fail("No success text appeared in the general form");
    });
});

/**
 * Open the config dialog and click on the first 'Edit This Authentication Backend' Link
 */
casper.thenOpen('/config/authentication', function() {
    var link = this.evaluate(function() {
        var links = document.querySelectorAll('#icingamain a');
        for (var i=0; i<links.length; i++) {
            if (/.* Edit This Authentication/.test(links[i].text)) {
                document.location.href = links[i].getAttribute('href');
                return;
            }
        }
    });

    this.echo("Clicked on first authentication backend link");
});

/**
 * Submit the authenticaton backend without any changes and test for the success bubble
 */
casper.then(function() {
    this.waitForSelector('input#btn_submit', function() {
        this.click('input#btn_submit');
        this.echo("Submitted authentication form");

        this.waitForSelector('div.alert', function() {
            // Force creation when message bubbled
            if (this.exists('form#form_modify_backend input#backend_force_creation')) {
                this.echo("Backend persistence requires an additional confirmation in this case");
                this.fill('#form_modify_backend', {
                    'backend_force_creation' : '1'
                });
                this.click('input#btn_submit');
            }
            this.echo("Waiting for success feedback");
            this.waitForSelector('div.alert.alert-success', function() {
                this.test.assertExists('div.alert.alert-success', 'Assert a success message to exist');
            });
        }, function() {
            this.test.fail("Success message for authentication provider tests didn't pop up");
        });
    }, function() {
        this.test.fail('No submit button found when expected the "Edit this authentication provider" form');
    });

});

/**
 * Submit the logging dialog without any changes and test for the success bubble
 */
casper.thenOpen('/config/logging', function() {
    this.test.assertExists('form#form_config_logging', 'Asserting the logging form to exist');
    this.click('form#form_config_logging input#btn_submit');
    this.waitForSelector('div.alert.alert-success', function() {
        this.test.assertExists('div.alert.alert-success', 'Assert a success message to exist');
    }, function() {
        this.test.fail('No success message popped up when saving logging configuration');
    });
});

/**
 * Run the tests
 */
casper.run(function() {
    this.test.done();
});


