# Hooks

Modules provide hook implementations by extending one of the base classes in
`Icinga\Application\Hook\`. The class goes in the module's `ProvidedHook` namespace:

```
library/<ModuleName>/ProvidedHook/<HookName>.php
```

For Icinga Web to call a hook, it needs to be registered. This happens in the module's `run.php`:

```php
<?php

use Icinga\Module\Acme\ProvidedHook\MyHook;

MyHook::register();
```

## Writing a Hook <a id="hooks-writing-hook"></a>

Modules can define their own hook types for other modules to implement. The abstract base class goes in
`library/<ModuleName>/Hook/<HookName>.php`. Use the `HookEssentials` trait and implement `getHookName()`,
which returns a string that uniquely identifies the hook type:

```php
<?php

namespace Icinga\Module\Acme\Hook;

use Icinga\Application\Hook\HookEssentials;

abstract class TicketHook
{
    use HookEssentials;

    protected static function getHookName(): string
    {
        return 'Acme/Ticket';
    }

    abstract public function createTicket(string $title): string;
}
```

The trait provides `all()` and `first()` for retrieving registered implementations,
`isRegistered()` to check if a hook of this type is already registered, and `register()`
for implementors to register themselves.

By default, an implementation is skipped for users without the `module/<module-name>` permission of the
providing module. Override `isAlwaysRun()` to return `true` if the hook should run regardless:

```php
protected static function isAlwaysRun(): bool
{
    return true;
}
```

### Implement the new Hook <a id="hooks-implement-new-hook"></a>

Other modules can now provide implementations of the hook. Registration works
as described earlier, but the class goes in a subnamespace of the implementing
module's `ProvidedHook` namespace:

```
library/<ModuleName>/ProvidedHook/Acme/<HookName>.php
```

Don't forget to register the hook in the `run.php`.

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
