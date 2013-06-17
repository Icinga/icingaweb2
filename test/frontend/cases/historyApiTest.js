
var i2w = require('./i2w-config');
var casper = i2w.getTestEnv();

casper.start("http://localhost:12999/generic.html");

casper.then(function() {
    casper.page.evaluate(i2w.setupRequireJs, {icinga: true});
});


casper.then(function() {
    this.test.assertTitle("Icinga test page");
    casper.page.evaluate(function() {
        requirejs(["icinga/icinga"], function(icinga) {
            icinga.loadUrl("/fragments/testFragment1.html");
        });
    });
    /*this.waitFor(function() {
        return document.querySelectorAll("#icinga-main a") ;
    }, */
    casper.waitForSelector("div#icinga-main a", onFirstLink); 
});

var onFirstLink = function() {
    var links = casper.page.evaluate(function() {
        return document.querySelectorAll("div#icinga-main a");
    });
    // assert no reload
    this.test.assertTitle("Icinga test page");
    this.test.assertUrlMatch(/.*testFragment1.html/);
    this.test.assertEquals(links.length, 2); 
    casper.clickLabel('Fragment 2');
    casper.waitForText('Fragment 1', onSecondLink);
};

var onSecondLink = function() {
    var links = casper.page.evaluate(function() {
        return document.querySelectorAll("div#icinga-main a");
    });
    this.test.assertTitle("Icinga test page");
    this.test.assertUrlMatch(/.*testFragment2.html/);
    this.test.assertEquals(links.length, 2); 
    casper.page.evaluate(function() {
        requirejs(["icinga/icinga"], function(icinga) {
            icinga.loadUrl("/fragments/testFragment3.html?this=is_a_param", "icinga-detail");
            console.log(document.location.href);
        }); 
    });
    console.log(casper.page.evaluate(function() {
        return document.location.href;
    }));
};

casper.run(function() {
    this.test.done();
});
