# Writing JavaScipt tests

JavaScript tests are executed using [mocha](http://visionmedia.github.io/mocha/) as a test framework and
[should.js](https://github.com/visionmedia/should.js/) as the assertion framework.

## Mocking require.js

As we use require.js for asynchronous dependency resolution in JavaScript, this can lead to problems in our node.js
environment. In order to avoid requirejs calls to cause issues, it has been mocked in the testlib/asyncmock.js class
and should be required in every testcase:

    var rjsmock = require("requiremock.js");

rjsmock now makes dependency management comfortable and provides the following most important methods:

        // remove all registered dependencies from the rjsmock cache
        rjsmock.purgeDependencies();

        // register the following objects underneath the following requirejs paths:
        rjsmock.registerDependencies({
            'icinga/container' : {
                updateContainer : function() {},
                createPopupContainer: function() {}
            }
        });
        // in your js code a require(['icinga/container], function(container) {}) would now have the above mock
        // object in the container variable

        requireNew("icinga/util/async.js"); // requires icinga/util/async.js file - and ignores the requirejs cache
        var async = rjsmock.getDefine(); // returns the last define, this way you can retrieve a specific javacript file

## Faking async responses

As we currently use the icinga/util/async.js class for all asynchronous requests, it's easy to fake responses. The asyncmock.js
class provides functions for this. To use it in your test, you first have to require it:

    var asyncMock = require("asyncmock.js");

You now can use asyncMock.setNextAsyncResult((async) asyncManager, (string) resultString, (bool) fails, (object) headers) to
let the next request of the passed asyncManager object return resultString as the response, with the headers provided as the
last parameter. If fails = true, the error callback of the request will be called.


## Example test

The following example describes a complete test, (which tests whether the registerHeaderListener method in the async class works) :

    var should = require("should");             //  require should.js for assertions
    var rjsmock = require("requiremock.js");    //  use the requiremock described above
    var asyncMock = require("asyncmock.js");    //  Helper class to fake async requests

    GLOBAL.document = $('body');                //  needed when our test accesses window.document


    describe('The async module', function() {   // start the test scenario
        it("Allows to react on specific headers", function(done) {  // Start a test case - when done is called it is finished
            rjsmock.purgeDependencies();        // Remove any dependency previously declared
            rjsmock.registerDependencies({      // Mock icinga/container, as this is a dependency for the following include
                'icinga/container' : {
                    updateContainer : function() {},
                    createPopupContainer: function() {}
                }
            });

            requireNew("icinga/util/async.js"); // This is the file we want to test, load it and all of it's dependencies
            var async = rjsmock.getDefine();    // Retrieve a reference to the loaded file

            // Use asyncMock.setNextAsyncResult to let the next async request return 'result' without failing and set
            // the response headers 'X-Dont-Care' and 'X-Test-Header'
            asyncMock.setNextAsyncResult(async, "result", false, {
                'X-Dont-Care' : 'Ignore-me',
                'X-Test-Header' : 'Testme123'
            });

            // register a listener for results with the X-Test-Header response
            async.registerHeaderListener("X-Test-Header", function(value, header) {
                // test for the correct header
                should.equal("Testme123", value);
                // call done to mark this test as succeeded
                done();
            },this);
            // run the faked request
            var test = async.createRequest();
        });

    });


