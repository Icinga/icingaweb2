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

## TwoFactorHook <a id="hooks-two-factor"></a>

The `TwoFactorHook` allows modules to provide a two-factor authentication method,
such as TOTP or email token. Icinga Web asks every registered method whether a user
is enrolled, and if so, requires the second factor after the primary login succeeds.
This hook always runs, regardless of the `module/<module-name>` permission.

Icinga Web derives a **canonical name** in the format `<module>/<name>`
(e.g. `mymodule/totp`) from the providing module and `getName()`, so two modules
may register a method using the same `getName()` without colliding.

If the module providing a user's enrolled method is disabled, that method is no
longer registered. The user's enrollment is then ignored and they log in without
a second factor. This is expected when an administrator disables a 2FA module.

The example below calls `loadSecret()`, `checkToken()`, `generateSecret()`,
`storeSecret()`, and `deleteSecret()`. These are placeholders that neither
`TwoFactorHook` nor this example provides. A real implementation must supply
them, including secure secret generation, token verification, and protected
persistent storage of the per-user secret.

Hook example:

```php
<?php

namespace Icinga\Module\Mymodule\ProvidedHook;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\User;
use ipl\Html\FormElement\FieldsetElement;
use ipl\I18n\Translation;
use SensitiveParameter;

class TotpTwoFactor extends TwoFactorHook
{
    use Translation;

    public function getName(): string
    {
        return 'totp';
    }

    public function getDisplayName(): string
    {
        return $this->translate('TOTP');
    }

    public function isEnrolled(User $user): bool
    {
        return $this->loadSecret($user) !== null;
    }

    public function verify(User $user, #[SensitiveParameter] string $token): bool
    {
        return $this->checkToken($this->loadSecret($user), $token);
    }

    public function assembleEnrollmentFormElements(User $user, FieldsetElement $fieldset): void
    {
        $fieldset->addElement('text', 'secret', [
            'readonly' => true,
            'value'    => $this->generateSecret(),
        ]);

        $fieldset->addElement('text', 'token', [
            'label'    => $this->translate('Verification Code'),
            'required' => true,
        ]);
    }

    public function enroll(User $user, FieldsetElement $fieldset): bool
    {
        $secret = $fieldset->getValue('secret');
        if (! $this->checkToken($secret, $fieldset->getValue('token'))) {
            $fieldset->getElement('token')->addMessage($this->translate('Invalid code'));

            return false;
        }

        $this->storeSecret($user, $secret);

        return true;
    }

    public function unenroll(User $user): void
    {
        $this->deleteSecret($user);
    }
}
```
