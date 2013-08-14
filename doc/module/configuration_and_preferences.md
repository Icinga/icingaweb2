# Module Development: Configuration and Preferences Dialogs

When developing modules, you might want your module's configuration and preferences dialogs to appear in the Icinga Web
Configuration/Preferences interface. This is rather easy to accomplish and should be the preferred way to allow user's
to customize your module.

## Terminology

When talking about 'Configuration' and 'Preference', we have a clear distinction between those words:

- **Configurations** are application/module wide settings that affect every user when being changed. This could be
  the data backend of your module or other 'global' settings that affect everyone when being changed
- **Preferences** are settings a user can set for *his* account only, like the page size of pagination, entry points, etc.


## Usage

The two base classes for preferences and configurations are \Icinga\Web\Controller\BasePreferenceController for preferences and
 \Icinga\Web\Controller\BaseConfigController for configurations.

If you want to create a preference or configuration panel you have to create a ConfigController and/or PreferenceController
 in your Module's a controller directory and make it a subclass of BaseConfigController or BasePreferenceController.

Those controllers can be used like normal controllers, with two exceptions:

- If you want your module to appear as a tab in the applications configuration/preference interface you have to implement
  the static createProvidedTabs function that returns an array of tabs to be displayed
- The init() method of the base class must be called in order to make sure tabs are collected and the view's tabs variable
  is populated

## Example

We'll just provide an example for ConfigControllers here, as PreferenceController are the same with a different name

    use \Icinga\Web\Controller\BaseConfigController;
    use \Icinga\Web\Widget\Tab;
    use \Icinga\Web\Url;

    class My_ConfigController extends BaseConfigController {

        static public function createProvidedTabs()
        {
            return array(
                "myModuleTab" => new Tab(array(
                    "name"  => "myModuleTab",                       // the internal name of the tab
                    "iconCls"  => "myicon",                         // the icon to be displayed
                    "title" => "The tab title",                     // The title of the configuration's tab
                    "url"   => Url::fromPath("/myModule/config")    // The Url that will ne called (can also be just a path)
                ))
            );
        }

        public function indexAction()
        {
            // create the form here
        }
    }
