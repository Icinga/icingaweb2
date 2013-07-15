/**
*
*   Regression test for #4408
#   History api double encodes and causes messy behaviour
*
**/

var i2w = require('./i2w-config');
var casper = i2w.getTestEnv();
var URL = "http://localhost:12999";
var firstLink = "/fragments/testFragment1.html?c[test]=test_test";
var secondLink = "/fragments/testFragment3.html?this=is_a_param";
casper.start(URL+"/generic.html");


casper.then(function() {
    casper.page.evaluate(i2w.setupRequireJs, {icinga: true});
});

casper.then(function() {
    casper.page.evaluate(function() {
        requirejs(["icinga/icinga"], function(icinga) {
            icinga.loadUrl("/fragments/testFragment1.html?c[test]=test_test");
        });
    });
    casper.waitForSelector("div#icinga-main a", onFirstCall); 
 
});

/**
*   First call of the loadUrl
**/
var onFirstCall = function() {
    this.test.assertUrlMatch(URL+firstLink);
    casper.page.evaluate(function() {
        requirejs(["icinga/icinga"], function(icinga) {
            icinga.loadUrl("/fragments/testFragment3.html?this=is_a_param", "icinga-detail");
        }); 
    });
    this.wait(400, function() {
        var expected =
            URL +
            firstLink+"&c[icinga-detail]=" +
            secondLink;
        this.test.assertUrlMatch(expected);
    });
};

casper.run(function() {
    this.test.done();
});
