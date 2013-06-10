/**
*
*   This test simply checks the icinga build server and tests 
*   if the title is correct
**/
i2w = require('./i2w-config');

var casper = i2w.getTestEnv();

casper.start("http://build.icinga.org/jenkins");

casper.then(function() {
    this.test.assertTitle("icinga-web test [Jenkins]", "The jenkins page");
});
  
casper.run(function() {
    this.test.done();
});

