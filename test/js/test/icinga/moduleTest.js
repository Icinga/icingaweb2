/**
*   Test cases for the module loading implementation 
*
*
**/


// {{LICENSE_HEADER}}
// {{LICENSE_HEADER}}
var should = require("should");
var rjsmock = require("requiremock.js");

var BASE = "../../../../public/js/";
require(BASE+"icinga/module.js");

var module = rjsmock.getDefine(); 
GLOBAL.document = $('body');
/**
* Test module that only uses eventhandlers and 
* no custom logic
**/
var eventOnlyModule = function() {
    var thiz = this;
    this.moduleLinkClick = false;
    this.formFocus = false;
    
    var onModuleLinkClick = function() {
        thiz.moduleLinkClick = true;
    };
    var onFormFocus = function() {
        thiz.formFocus = true;
    };
    this.eventHandler = {
        '.myModule a' : {
           'click': onModuleLinkClick
        },
        '.myModule div.test input' : {
            'focus' : onFormFocus
        }
    };
};
/**
*   Module that defines an own enable and disable function
*   that is called additionally to the eventhandler setup
*
**/
var customLogicModule = function() {
    var thiz = this;
    this.clicked = false;
    this.customEnable = false;
    this.customDisable = false;

    var onClick = function() {
        thiz.clicked = true;
    };
    
    this.enable = function() {
        thiz.customEnable = true;
    };

    this.disable = function() {
        thiz.customDisable = true;
    };

    this.eventHandler = {
        '.myModule a' : {
            'click' : onClick
        }
    };
};

var setupTestDOM = function() {
    tearDownTestDOM();
    $('body').append($('<div class="myModule" />')
             .append($('<a href="test">funkey</a>'))
             .append($('<div class="test" />')
             .append('<input type="button" />')));
};

var tearDownTestDOM = function() {
    $('body').off();
    $('body').empty();
};

describe('Module loader', function() {
    var err = null;
    var errorCallback = function(error) {
        err = error;
    };


    it('Should call the errorCallback when module isn\'t found', function() {
        err = null;
        rjsmock.purgeDependencies();
        module.resetHard();
        module.enableModule("testModule", errorCallback);
        should.exist(err);
    });


    it('Should register model event handlers when an \'eventHandler\' attribute exists', function() {
        rjsmock.purgeDependencies();
        var testModule = new eventOnlyModule();
        rjsmock.registerDependencies({
            testModule: testModule
        });
        err = null;
        var toTest = null;
        
        // Test event handler
        setupTestDOM();

        // Enable the module and asser it is recognized and enabled
        module.enableModule("testModule", errorCallback, function(enabled) {
            toTest = enabled;
        });
        should.not.exist(err, "An error occured during loading: "+err);
        should.exist(toTest, "The injected test module didn't work!");
        should.exist(toTest.enable, "Implicit enable method wasn't created"); 
        $('.myModule a').click();
        should.equal(toTest.moduleLinkClick, true, "Click on link should trigger handler");
        
        $('.myModule div.test input').focus();
        should.equal(toTest.formFocus, true, "Form focus should trigger handler");

        tearDownTestDOM();
    });

    it('Should be able to deregister events handlers when disable() is called', function() {
        rjsmock.purgeDependencies();
        var testModule = new eventOnlyModule();
        rjsmock.registerDependencies({
            testModule: testModule
        });
        err = null;
        var toTest = null;
        
        setupTestDOM(); 

        module.enableModule("testModule", errorCallback, function(enabled) {
            toTest = enabled;
        });
        should.not.exist(err, "An error occured during loading: "+err);
        should.exist(toTest, "The injected test module didn't work!");
        should.exist(toTest.enable, "Implicit enable method wasn't created"); 
        
        $('.myModule a').click();
        should.equal(toTest.moduleLinkClick, true, "Click on link should trigger handler");
        toTest.moduleLinkClick = false;
        
        module.disableModule("testModule"); 
        $('.myModule a').click();
        should.equal(toTest.moduleLinkClick, false, "Click on link shouldn't trigger handler when module is disabled");
        tearDownTestDOM();
        $('body').unbind();
    });

    it('Should additionally call custom enable and disable functions', function() {

        rjsmock.purgeDependencies();
        var testModule = new customLogicModule();
        rjsmock.registerDependencies({
            testModule: testModule
        });
        err = null;
        var toTest = null;
        
        // Test event handler
        setupTestDOM(); 

        module.enableModule("testModule", errorCallback, function(enabled) {
            toTest = enabled;
        });
        should.not.exist(err, "An error occured during loading: "+err);
        should.exist(toTest, "The injected test module didn't work!");
        should.exist(toTest.enable, "Implicit enable method wasn't created"); 
        should.equal(toTest.customEnable, true, "Custom enable method wasn't called"); 
        $('.myModule a').click();
        should.equal(toTest.clicked, true, "Click on link should trigger handler");
        toTest.clicked = false;
                
        module.disableModule("testModule"); 
        should.equal(toTest.customDisable, true, "Custom disable method wasn't called"); 
        $('.myModule a').click();
        should.equal(toTest.clicked, false, "Click on link shouldn't trigger handler when module is disabled");
        tearDownTestDOM();
        $('body').unbind();
    });
});


describe('The icinga module bootstrap', function() {
    it("Should automatically load all enabled modules", function() {
        rjsmock.purgeDependencies();
        var testClick = false;
        rjsmock.registerDependencies({
            "icinga/module": module,
            "modules/test/test" : {
                eventHandler: {
                    "a.test" : {
                        click : function() {
                            testClick = true;
                        }
                    }  
                }
            },
            "icinga/container" : {
                registerAsyncMgr: function() {},
                initializeContainers: function() {}
            },
            "modules/list" : [
                { name: 'test' },   
                { name: 'test2'} // this one fails 
            ]
        });
        tearDownTestDOM();
        require(BASE+"icinga/icinga.js");
        var icinga = rjsmock.getDefine();
        $('body').append($("<a class='test'></a>"));
        $('a.test').click();
        should.equal(testClick, true, "Module wasn't automatically loaded!");
        icinga.getFailedModules().should.have.length(1);
        should.equal(icinga.getFailedModules()[0].name, "test2");
        tearDownTestDOM(); 
    });
});
