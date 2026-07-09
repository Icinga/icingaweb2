# Hooks

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

## PasswordPolicyHook <a id="hooks-password-policy"></a>

The `PasswordPolicyHook` allows modules to provide custom [password policies](05-Authentication.md#authentication-password-policy).
This hook always runs, regardless of the providing module's `module/<module-name>` permission.
Extend it and implement the following methods:

Method                         | Description
-------------------------------|-------------------------------------------------------------------
`getDisplayName(): string`     | Human-readable name shown in the policy selector in the UI.
`getName(): string`            | Machine-readable identifier used in the configuration file. Must be unique within the providing module, and must not change.
`getDescription(): ?ValidHtml` | Description of the requirements shown in the password change form when this policy is active. Return `null` to show nothing.
`validate(): array`            | Validates the new password. Returns a list of violation messages, or an empty array if the password is valid. `$oldPassword` may be `null` when unavailable..

Icinga Web derives a **canonical name** in the format `<module>/<name>` (e.g. `mypasswordpolicy/my-custom-policy`)
from the providing module and `getName()`. This is what gets stored in the configuration file and must be unique
across all registered policies.

Hook example:

```php
<?php

namespace Icinga\Module\Mypasswordpolicy\ProvidedHook;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\User;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use SensitiveParameter;

class PasswordPolicy extends PasswordPolicyHook
{
    use Translation;

    public function getDisplayName(): string
    {
        return $this->translate('My Custom Policy');
    }

    public function getName(): string
    {
        return 'my-custom-policy';
    }

    public function getDescription(): ?ValidHtml
    {
        return new Text(
            $this->translate('At least 8 characters, at least 1 number, and must differ from the last password'),
        );
    }

    public function validate(
        User $user,
        #[SensitiveParameter] string $newPassword,
        #[SensitiveParameter] ?string $oldPassword = null,
    ): array {
        $violations = [];

        if (mb_strlen($newPassword) < 8) {
            $violations[] = $this->translate('Password must be at least 8 characters long');
        }

        if (! preg_match('/[0-9]/', $newPassword)) {
            $violations[] = $this->translate('Password must contain at least one number');
        }

        if ($oldPassword !== null && hash_equals($oldPassword, $newPassword)) {
            $violations[] = $this->translate('New password must differ from the old password');
        }

        return $violations;
    }
}
```
