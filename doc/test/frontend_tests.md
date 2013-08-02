# Frontend component tests

Frontend tests test your code from the users perspective: By opening a specific url, executing a few clicks, strokes, etc.
and expecting something to happen. We use [CasperJS](http://casperjs.org/) for frontend testing, which is basically a
headless Webkit browser.

## The current state of frontend testing

Currently frontend tests are not very advanced: We spawn a small - non php - server on port 12999 to test static files
and javascript behaviour. This will change in the future where we are going to test an installation (or use PHP 5.4
standalone server).

## Writing tests

### Test bootstrap

In order to make testing more comfortable, the i2w config provides a few helpers to make testing more straightforward.
In general you start your test by including i2w-config:

    var i2w = require('./i2w-config');

and afterward creating a testenvironment with the getTestEnv() method:

    var casper = i2w.getTestEnv();

You can then start the test by calling casper.start with the startpage (the servers root is always frontend/static, where
public is a symlink to the icingaweb public folder).

    casper.start("http://localhost:12999/generic.html");

As we use requirejs, this has to be set up for our testcases. i2w provides a setupRequireJs function that does everything for you.
You just have to run this method on your testpage (note that the tested JavaScript is isolated from your test case's JavaScript, if
you want to execute JavaScript you must use the casper.page.evaluate method).

    casper.then(function() {
        // Setup requirejs
        casper.page.evaluate(i2w.setupRequireJs, {icinga: true});
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
