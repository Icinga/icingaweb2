# The Container Component (app/container)

The container component is the most basic building block for icingaweb. Even when displaying an empty controller,
you always have at least two containers in your viewport which are implicitly created: The main and the detail container.

Container handle the following tasks:

* Updating the url part responsible for the container
* Handling Url changes like they occur when the browser history is used by synchronizing their content with the
  associated Url part
* Informing subcomponents about changes in the container


## The Container Api

You can find the sourcecode for containers along with jsdoc comments  at *./public/js/icinga/components/container.js*.
Here we will discuss the most important calls and their synopsis:

### Accessing Containers:

The container component returns a 'Container' object which allows you to access responsible containers for dom nodes via
the following methods:

* using `new Container($myDomNodes)` which returns a stateless container object wrapping the container responsible for
  the first node in $myDomNodes
* using `Container.getMainContainer()` or `Container.getDetailContainer()` which remove the main or detail container
  (this one is stateful with a few notes, read on)

**Note:** `new Container($('#icingamain')) != Container.getMainContainer()`, but
`(new Container($('#icingamain'))).containerDom == Container.getMainContainer().containerDom`

** Example #1 getting the container responsible for a dom node **

**HTML**

    <div id="icingamain">
        <div class="myNode">
            Some kind of node
        </div>
        <div id="somecontainer" data-icinga-component="app/container">
            <div class="mySecondNode">
                Some other kind of node
                <p>
                    Insert your lorem ipsum here
                </p>
            </div>
        </div>
    </div>

**JS**:

    require(['jquery', 'app/container'], function($, Container) {
        var firstContainer = new Container($('.myNode')); // firstContainer wraps '#icingamain'
        var mainContainer = Container.getMainContainer(); // also wraps '#icingamain'
        var secondContainer = new Container($('.myNode p')); // #somecontainer is wrapped by secondContainer

        firstContainer.someProperty = 'What a nice property!';
        mainContainer.someState = 'I have some state';
        console.log(firstContainer.someProperty);              // return 'What a nice property'
        console.log(main.someProperty);                        // return 'undefined'
        console.log(Container.getMainContainer().someState)    // return 'I have some state' when page hasn't been refreshed
    });

## Containers And The Browser Url

As noted before (and indicated by the `getMainContainer()` and `getDetailContainer()` function), the main and detail
container have a special role. Considering the following Url:

    http://my.monitoringhost.org/icingaweb/monitoring/list/host?page=4&detail=%2Fmonitoring%2Fshow%2Fhost%3Fhost%3Dlocalhost

This URL displays the 4th page of your host list in the main container (monitoring/list/host?page=4) and the host information
for localhost in the detail container (monitoring/show/host?host=localhost). When you split this Url up in logical pieces
it looks like this:

    http://my.monitoringhost.org/icingaweb/monitoring/list/host?page=4&detail=%2Fmonitoring%2Fshow%2Fhost%3Fhost%3Dlocalhost
    \___________  _______________________/\_________  ______________/ \_  ____/\________________  _______________________/
                \/                                  \/                  \/                      \/
            1. Base URL              2.Main container URL and Query   3.Detail param       4. Encoded detail URL and params

1.  **Base URL** :  I don't think this needs much explanation.
2.  **Main container URL and query** : This is the *normal* part of your Url and denotes the controller route that is
    being displayed in your main container
3.  **Detail parameter**: This parameter will be ignored by the main container and used for rendering the detail container,
    if omitted there's simple no detail view to be displayed
4   **Encoded detail URL**: The value of the "detail" parameter is the Url (without the base Url) that returns the content
    of the detail area


### Updating A Container's Url

If you want your container to display content from a different Url, you can use the *replaceDomFromUrl()* on your
Container object:

**Example #2 Updating A Containers URL**

**HTML:**

    <div id="icingamain">
        <div id"mainSub"></div>
    </div>
    <div id="icingadetail">
        <div id"detailSub"></div>
    </div>

**JS:**

    // this loads the page with the new main container
    require(['jquery', 'app/container'], function($, Container) {
        new Container('#mainSub').replaceDomFormUrl('/another/url');
    }

    // this loads the page with the new detail container
    require(['jquery', 'app/container'], function($, Container) {
        new Container('#detailSub').replaceDomFormUrl('/another/url');
    }

    // this does NOT work:
    require(['jquery', 'app/container'], function($, Container) {
        Container.getMainContainer().replaceDomFormUrl('/another/url');
        // will never be reached due to a reload
        Container.getMainContainer().replaceDomFormUrl('/another/url2');
    }

    // this loads the page with both main and detail changed (this is rarely needed and should be avoided)
    require(['icinga', 'jquery', 'app/container'], function('Icinga', $, Container) {
        // it's better to use this:
        var mainContainer = Container.getMainContainer();
        var detailContainer = Container.getDetailContainer();

        mainContainer.updateContainerHref('/another/url'); // first update the main container href
        detailContainer.updateContainerHref('/another/url2');   // update the detail href

        var url = mainContainer.getContainerHref(detailContainer.getContainerHref()); // fetch the new url
        Icinga.replaceBodyFromUrl(url); // and update manual
    }

This results in the URL changing to './another/url?detail=%2Fanother%2Fdetail%2Furl.
The advantage of using a Container instance with the subelements (i.e. '\#mainSub') over calling getMain/DetailContainer
directly is that you don't need to know in what container your view is displayed - when you move 'mainSub' into the
detail container, the detail container would be updated afterwards.

**NOTE**: You should read the '...' section in order to understand why you shouldn't do it like in this example

### How container refresh states are handled

If you refresh containers content (load url or replace dom), the container displaya a loading
mask as default behaviour. To disable this mask and handle it yourself, you can register own events:

**Example #3 Load indicator events**

    require(['icinga', 'jquery', 'app/container'], function('Icinga', $, Container) {
        var mainContainer = Container.getMainContainer();

        // Detach the default behaviour from container
        mainContainer.removeDefaultLoadIndicator();

        var showFunction = function() {
            console.log('container is loading');
        };

        var hideFunction = function() {
            console.log('container content refreshed');
        };

        // Install new handlers
        mainContainer.registerOnShowLoadIndicator(showFunction);
        mainContainer.registerOnHideLoadIndicator(hideFunction);
    };

**Example #4 Use this for your components**

Please have a look into [components documentation](components.md) for detailed information about components.

    define(['components/app/container', 'jquery', 'logging', 'URIjs/URI', 'URIjs/URITemplate'],
    function(Container, $, logger, URI) {
        "use strict";

        /**
         * Master/Detail grid component handling history, link behaviour, selection (@TODO 3788) and updates of
         * grids
         *
         * @param {HTMLElement} The outer element to apply the behaviour on
         */
        return function(gridDomNode) {
            /**
             * Constructor method for this component
             */
            this.construct = function(target) {
                // Container object for the component
                this.container = new Container(target);

                // Detach default handlers
                this.container.removeDefaultLoadIndicator();
            };

            this.construct(gridDomNode);
        };
    };