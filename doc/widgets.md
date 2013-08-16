# Widgets

Widgets are reusable UI components that are able to render themselves and return HTML to be included in your template.

## Basic interface

The interface needed for implementing widgets can be found under library/Icinga/Web/Widget/Widget.php. This is a rather
simple interface, only providing a `render()` method that takes a view and returns HTML:

    interface Widget
    {
        public function render(Zend_View_Abstract $view);
    }

When implementing own Widgets you just have to make sure that you provide this render method.

## Using widgets

Widgets are normally created in the controller and added to the view:

    // in your Controller

    public function myControllerAction()
    {
        $this->view->myWidget = new MyWidget();
    }

The HTML is then rendered in the template using the `render()` method described above. As the '$this' scope in a view is
a reference to your current view, you can just pass it to the `render()` method:

    // in your template

    <div>
        <h4>Look at my beautiful widget</h4>
        <?= $this->myWidget->render($this); ?>
    </div>

## The 'Tabs' widget

The Tabs `\Icinga\Web\Widgets\Tabs` widget handles creation of Tab bars and allows you to create and add single tabs to
this view. To create an empty Tab bar, you just have to call:

    $tabbar = new Tabs();

> **Note**: Controllers subclassing `\Icinga\Web\Controller\ActionController` (which all existing controller do so and
> yours should too) have already an empty tabs object created under `$this->view->tabs`. This is done in the
> `preDispatch` function.

### Adding tabs

Afterwards you can add tabs by calling the `add($name, $tab)` function, whereas `$name` is the name of your tab and
`$tab` is either an array with tab parameters or an existing Tab object.

    // Add a tab

    $tabbar->add(
        'myTab',
        array(
            'title'     => 'My hosts',                  // Displayed as the tab text
            'iconCls'   => 'myicon',                    // icon-myicon will be used as an icon in a <i> tag
            'url'       => '/my/url',                   // The url to use
            'urlParams' => array('host' => 'localhost') // Will be used as GET parameter
        )
    );

### Adding tabs to the dropdown list

Sometimes you want additional actions to be displayed in your tabbar. This can be accomplished with the
`addAsDropdown()` method. This one is similar to the `add()` method, but displays your tab in a dropdown list on the
right side of the tabbar.

## Using tabextensions

Often you find yourself adding the same tabs over and over again. You can write a Tabextension that does this for you
and just apply them on your tabs. Tabextensions are located at Icinga/Web/Widgets/Tabextension/ and they use the simple
Tabextension interface that just defines `apply(Tabs $tab)`. A simple example is the DashboardAction Tabextender which
just adds a new field to the dropdown list:

    class DashboardAction implements Tabextension
    {
        /**
         * @see Tabextension::apply()
         */
        public function apply(Tabs $tabs)
        {
            $tabs->addAsDropdown(
                'dashboard',
                array(
                    'title'     => 'Add to Dashboard',
                    'iconCls'   => 'dashboard',
                    'url'       => Url::fromPath('dashboard/addurl'),
                    'urlParams' => array(
                        'url' => Url::fromRequest()->getRelativeUrl()
                    )
                )
            );
        }
    }

You can now either extend your Tabs object using the DashboardAction's `apply()` method or by calling the Tabs
`extend()` method (which is more fluent):

    $tabs->extend(new DashboardAction());
