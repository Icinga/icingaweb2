
// {{LICENSE_HEADER}}
// {{LICENSE_HEADER}}
var should = require("should");
var rjsmock = require("requiremock.js");
var asyncMock = require("asyncmock.js");

GLOBAL.document = $('body');


describe('The async module', function() {
    it("Allows to react on specific headers", function(done) {
        rjsmock.purgeDependencies();
        rjsmock.registerDependencies({
            'icinga/container' : {
                updateContainer : function() {},
                createPopupContainer: function() {}
            }
        });

        requireNew("icinga/util/async.js");
        var async = rjsmock.getDefine();
        var headerValue = null;
        asyncMock.setNextAsyncResult(async, "result", false, {
            'X-Dont-Care' : 'Ignore-me',
            'X-Test-Header' : 'Testme123'
        });
        async.registerHeaderListener("X-Test-Header", function(value, header) {
            should.equal("Testme123", value);
            done();
        },this);
        var test = async.createRequest(); 
    });

});
