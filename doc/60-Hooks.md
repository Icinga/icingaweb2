# Hooks

## ConfigFormEventsHook

The `ConfigFormEventsHook` allows developers to hook into the handling of configuration forms. It provides three methods:

* `appliesTo()`
* `isValid()`
* `onSuccess()`

`appliesTo()` determines whether the hook should run for a given configuration form.
Developers should use `instanceof` checks in order to decide whether the hook should run or not.
If `appliesTo()` returns `false`, `isValid()` and `onSuccess()` won't get called for this hook.

`isValid()` is called after the configuration form has been validated successfully.
An exception thrown here indicates form errors and prevents the config from being stored.
The exception's error message is shown in the frontend automatically.
If there are multiple hooks indicating errors, every error will be displayed.

`onSuccess()` is called after the configuration has been stored successfully.
Form handling can't be interrupted here. Any exception will be caught, logged and notified.

Hook example:

```php
namespace Icinga\Module\Acme\ProvidedHook;

use Icinga\Application\Hook\ConfigFormEventsHook;
use Icinga\Forms\ConfigForm;
use Icinga\Forms\Security\RoleForm;

class ConfigFormEvents extends ConfigFormEventsHook
{
    public function appliesTo(ConfigForm $form)
    {
        return $form instanceof RoleForm;
    }

    public function onSuccess(ConfigForm $form)
    {
        $this->updateMyModuleConfig();
    }

    protected function updateMyModuleConfig()
    {
        // ...
    }
}
```

## CspHook <a id="hooks-csp"></a>

The `CspHook` allows developers to add custom CSP directives to the Icinga Web 2 frontend.
It provides the methods `getCspForUser(User)` and `getCspForAllUsers()` which should return
a `Csp` instance with the directives the module wants to add. The difference between the two
methods is that `getCspForUser()` is called for a specific user instance and should return
the CSP directives that specific user requires. While `getCspForAllUsers()` is called for
all users and should return the CSP directives that any one user requires. The directives are
combined additively with the default directives, icingaweb2 generated ones and other
module-defined directives.

Hook example:

```php
namespace Icinga\Module\Acme\ProvidedHook;

use Icinga\Application\Hook\CspHook;
use ipl\Web\Common\Csp as CspInstance;
use Icinga\User;

class Csp extends CspHook
{
    public function getCspForAllUsers(): CspInstance
    {
        $csp = new CspInstance();
        $csp->add('img-src', ['cdn.example.com', 'usercontent.example.com']);
        $csp->add('style-src', 'cdn.example.com');

        // ...

        return $csp;
    }
    
    public function getCspForUser(User $user) : CspInstance
    {
        // ...
        
        return $csp;
    }
}
```
