/*global requireNew:false, describe: false, it:false */

/**
 * {{LICENSE_HEADER}}
 * {{LICENSE_HEADER}}
 */

/**
 * The assertion framework
 *
 * @type {should}
 */
var should  = require('should');

/**
 * RequireJS mocks for dynamically loading modules
 *
 * @type {requiremock}
 */
var rjsmock = require('requiremock.js');

/**
 * URIjs object for easier URL manipulation
 *
 * @type {URIjs}
 */
var URI     = require('URIjs');

// Setup required globals for this test
GLOBAL.document = $('body');
GLOBAL.History = require('historymock.js');
GLOBAL.Modernizr = {
    history: true
};

/**
 * Workaround as jQuery.contains doesn't work with the nodejs jQuery library on some test systems
 *
 * @returns {boolean}
 */
jQuery.contains = function() {
    'use strict';
    return true;
};

/**
 * Create a basic dom only containing a main and detail container
 *
 */
var createDOM = function() {
    'use strict';

    document.empty();
    document.append(
        $('<div>').attr('id', 'icingamain')
    ).append(
        $('<div>').attr('id', 'icingadetail')
    );
};

$.ajax = function(obj) {
    obj.success("<div></div>");

};
/**
 * Test case
 *
 */
describe('The container component', function() {
    'use strict';

    /**
     * Test dom selectors and instance creation
     */
    it('should provide access to the main and detail component', function() {
        createDOM();
        rjsmock.registerDependencies({
            'URIjs/URI' : URI
        });
        requireNew('icinga/components/container.js');
        var Container = rjsmock.getDefine();
        should.exist(Container.getMainContainer().containerDom, 'Assert that the main container has an DOM attached');
        should.exist(Container.getDetailContainer().containerDom, 'Assert that the detail container has an DOM attached');
        Container.getMainContainer().containerDom[0].should.equal(
            $('#icingamain')[0], 'Assert the DOM of the main container being #icingamain');
        Container.getDetailContainer().containerDom[0].should.equal(
            $('#icingadetail')[0], 'Assert the DOM of the detail container being #icingadetail');
    });

    /**
     * Test dynamic Url update
     */
    it('should automatically update its part of the URL if assigning a new URL', function() {
        rjsmock.registerDependencies({
            'URIjs/URI' : URI
        });
        requireNew('icinga/components/container.js');
        createDOM();
        var Container = rjsmock.getDefine();
        var url = Container.getMainContainer().updateContainerHref('/some/other/url?test');
        window.setWindowUrl(url);
        Container.getMainContainer().containerDom.attr('data-icinga-href').should.equal('/some/other/url?test');

        url.should.equal(
            '/some/other/url?test',
            'Assert the main container updating the url correctly');

        url = Container.getDetailContainer().updateContainerHref('/some/detail/url?test');
        window.setWindowUrl(url);

        Container.getDetailContainer().containerDom.attr('data-icinga-href').should.equal('/some/detail/url?test');
        url.should.equal(
            '/some/other/url?test&detail=' + encodeURIComponent('/some/detail/url?test'),
            'Assert the detail container only updating the "detail" portion of the URL'
        );

        url = Container.getMainContainer().updateContainerHref('/some/other2/url?test=test');

        window.setWindowUrl(Container.getMainContainer().getContainerHref(window.location.href));
        Container.getMainContainer().containerDom.attr('data-icinga-href').should.equal('/some/other2/url?test=test');
        url.should.equal(
            '/some/other2/url?test=test&detail=' + encodeURIComponent('/some/detail/url?test'),
            'Assert the main container keeping the detail portion untouched if being assigned a new URL'
        );
    });

});
