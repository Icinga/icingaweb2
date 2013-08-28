# Frontend component tests

Frontend tests test your code from the users perspective: By opening a specific url, executing a few clicks, strokes, etc.
and expecting something to happen. We use [CasperJS](http://casperjs.org/) for frontend testing, which is basically a
headless Webkit browser.

**NOTE**: The 1.1.0DEV version does *NOT* work at this time as the api changed. Use the stable 1.0.3 branch instead.

In order to be able to run the frontend tests, you need a running instance of icingaweb. You should make sure that you
don't need this instance after running the tests, as they could change preferences or configuration

## Writing tests


### Test bootstrap - icingawebtest.js module

The icingawebtest.js module is required for proper testing, as this module eases casperjs usage. After importing the
module with:

    var icingawebtest = require('./icingawebtest');

You only need two methods for testing:

* *getTestEnv()*: This method returns a modified casperjs test environment. The difference to then normal casperjs object
                  is that all methods which take a URL are overloaded so you can add a relative URL if you want to (and
                  normally you don't want to hardcode your test URLs)
                  Example:

        var casper = icingawebtest.getTestEnv();

* performLogin():   This calls the login page of your icingaweb instance and tries to login with the supplied credentials

        icinga.performLogin();


Login is performed with the credentials from the CASPERJS_USER/CASPERJS_PASS environment (this can be set with the
./runtest --user %user% --pass %pass% arguments). The host, server and port are also represented as
CASPERJS_HOST, CASPERJS_PORT and CASPERJS_PATH environment settings. The default in runtest resembles the version that
works best in the vagrant development environment:

*   The default user is 'jdoe'
*   The default password is 'password'
*   The host and port are localhost:80
*   The default path is icinga2-web

### Writing the test code

Most tests will require you to login with the supplied credentials, this can be performed with a simple call

    icinga.performLogin();

You can then start the test by calling casper.thenOpen with the page you want to work

    casper.thenOpen("/mysite", function() {
        // perform tests
    });

### Testing

Afterwards, everything is like a normal CasperJS test, so you can wrap your assertions in a casper.then method:

    // assert a specific title
    casper.then(function() {
        this.test.assertTitle("Just an empty page");
    });

Note that asynchronous calls reuqire you to wait or provide a callback until the resource is loaded:

    // waitForSelector calls callbackFn as soon as the selector returns a non-empty set
    casper.waitForSelector("div#icinga-main a", callbackFn);

At the end of your test, you have to provide

    casper.run(function() {
        this.test.done();
    });

Otherwise the tests won't be executed.
