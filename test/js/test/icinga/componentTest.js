/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

require('should');
var rjsmock = require('requiremock.js');

GLOBAL.document = $('body');
var component;


/**
 * Set up the test fixture
 *
 * @param   registry    The optional registry mock that should be used.
 */
var setUp = function(registry)
{
    rjsmock.purgeDependencies();

    requireNew('icinga/componentRegistry.js');
    registry = registry || rjsmock.getDefine();

    rjsmock.registerDependencies({
        'icinga/componentRegistry': registry,

        /*
         * Available components
         */
        'modules/app/component1': function(cmp) {
            cmp.test = 'changed-by-component-1';
            this.type = function(){
                return "app/component1";
            };
        },
        'modules/app/component2': function(cmp) {
            cmp.test = 'changed-by-component-2';
            this.type = function(){
                return "app/component2";
            };
        },
        'modules/module/component3': function(cmp) {
            cmp.test = 'changed-by-component-3-from-module';
            this.type = function(){
                return "module/component3";
            };
        }
    });

    $('body').empty();

    requireNew('icinga/componentLoader.js');
    component = rjsmock.getDefine();
};

/**
 * Add a new component to the current test-DOM
 *
 * @param   type    {String}    The type of the component in the form: "<module>/<type>"
 * @param   id      {String}    The optional id of the component
 */
var addComponent = function(type,id) {
    var txt = '<div '+( id ? ( ' id= "'+id+'" ' ) : '' ) +
    ' data-icinga-component="'+type+'" >test</div>';

    $('body').append(txt);
};

describe('Component loader',function(){

    it('Component loaded with automatic id',function(){
        setUp();
        addComponent('app/component1');

        component.load(function(){
            // loading complete
            var cmpNode = $('#icinga-component-0');
            cmpNode.length.should.equal(1);
            cmpNode[0].test.should.equal('changed-by-component-1');
            component.getById('icinga-component-0').type().should.equal('app/component1');
        });
    });

    it('Component load with user-defined id',function(){
        setUp();
        addComponent('app/component2','some-id');

        component.load(function(){
            // loading complete
            var cmpNode = $('#some-id');
            cmpNode.length.should.equal(1);
            cmpNode[0].test.should.equal('changed-by-component-2');
            component.getById('some-id').type().should.equal('app/component2');
        });
    });

    it('Garbage collection removes deleted components',function(){
        setUp();
        addComponent('app/component1');
        addComponent('app/component2');
        addComponent('app/component2');
        addComponent('module/component3');

        component.load(function(){
            // loading complete
            var components = component.getComponents();
            components.length.should.equal(4);
            $('body').empty();
            component.load(function(){
                var components = component.getComponents();
                components.length.should.equal(0);
            });
        });
    });

    it('Component queries are delegated to the registry correctly',function(){
        var getByIdCalled = false;
        var getByTypeCalled = false;
        var getComponentsCalled = false;

        var registryMock = {
            getById: function(id){
                getByIdCalled = true;
                id.should.equal('some-id');
            },
            getByType: function(type){
                getByTypeCalled = true;
                type.should.equal('some-type');
            },
            getComponents: function(){
                getComponentsCalled = true;
            }
        };

        setUp(registryMock);

        component.getById('some-id');
        getByIdCalled.should.be.true;

        component.getByType('some-type');
        getByTypeCalled.should.be.true;

        component.getComponents();
        getComponentsCalled.should.be.true;
    });
});

