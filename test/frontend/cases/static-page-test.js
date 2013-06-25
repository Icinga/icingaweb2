/**
*
*   This test simply checks the icinga build server and tests 
*   if the title is correct
**/
i2w = require('./i2w-config');

var casper = i2w.getTestEnv();

casper.start("http://localhost:12999/empty.html");


casper.then(function() {
    casper.log(this.test);
    this.test.assertTitle("Just an empty page");
});

  
casper.run(function() {
    this.test.done();
});

