/**
 * SubmitPassiveCheckResult is always type service
 *
 * As a user I want to be able to choose between host
 * check results when no services are passed in.
 *
 * This test performs the following steps
 *
 * - Login using the provided credentials
 * - Open the form to submit passive check results
 * - Check whether it is possible to choose between result types for hosts,
 *   if no services are given
 * - Check whether it is possible to choose between result types for services
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
 * Open the command dialog with only a host pattern and ensure that the form exists
 */
casper.thenOpen('/monitoring/command/submitpassivecheckresult?host=*', function() {
    if (this.exists('.alert')) {
        this.test.info('Skipping test; See issue #4666 for more details');
        return;
    }

    this.test.assertExists(
        '#form_submit_passive_checkresult',
        'Test whether the form to submit passive checkresults is available'
    );
    this.test.assertExists(
        '#form_submit_passive_checkresult select#pluginstate',
        'Ensure that the result type input exists'
    );
});

/**
 * Check whether the input contains the checkresult types for hosts
 */
casper.then(function() {
    if (this.exists('.alert')) {
        this.test.info('Skipping test; See issue #4666 for more details');
        return;
    }

    var options = this.evaluate(function() {
        var elements = document.querySelector(
            '#form_submit_passive_checkresult select#pluginstate'
        ).options;

        var options = [];
        for (var i = 0; i < elements.length; i++)
            options.push(elements[i].text);
        return options;
    });

    if (options.indexOf('UP') == -1)
    {
        this.test.fail('UP not available as checkresult type for hosts');
    }
    else if (options.indexOf('DOWN') == -1)
    {
        this.test.fail('DOWN not available as checkresult type for hosts');
    }
    else if (options.indexOf('UNREACHABLE') == -1)
    {
        this.test.fail('UNREACHABLE not available as checkresult type for hosts');
    }
    else
    {
        this.test.pass('Found all checkresult types for hosts');
    }
});

/**
 * Open the command dialog with a host and a service pattern as well and
 * check whether the input contains the checkresult types for services
 */
casper.thenOpen('/monitoring/command/submitpassivecheckresult?host=*&service=*', function() {
    if (this.exists('.alert')) {
        this.test.info('Skipping test; See issue #4666 for more details');
        return;
    }

    var options = this.evaluate(function() {
        var elements = document.querySelector(
            '#form_submit_passive_checkresult select#pluginstate'
        ).options;

        var options = [];
        for (var i = 0; i < elements.length; i++)
            options.push(elements[i].text);
        return options;
    });

    if (options.indexOf('OK') == -1)
    {
        this.test.fail('OK not available as checkresult type for services');
    }
    else if (options.indexOf('WARNING') == -1)
    {
        this.test.fail('WARNING not available as checkresult type for services');
    }
    else if (options.indexOf('CRITICAL') == -1)
    {
        this.test.fail('CRITICAL not available as checkresult type for services');
    }
    else if (options.indexOf('UNKNOWN') == -1)
    {
        this.test.fail('UNKNOWN not available as checkresult type for services');
    }
    else
    {
        this.test.pass('Found all checkresult types for services');
    }
});

/**
 * Run the tests
 */
casper.run(function() {
    this.test.done();
});
