<?php

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;
use Icinga\Authentication\PasswordValidator;

use Icinga\Web\Form;

class PasswordPolicyHelper
{

    const DEFAULT_PASSWORD_POLICY = AnyPasswordPolicy::class;

    private $passwordPolicy;

    public function __construct()
    {
        $passwordPolicyClass = Config::app()->get(
            'global',
            'password_policy',
            self::DEFAULT_PASSWORD_POLICY
        );

        if (class_exists($passwordPolicyClass)) {
            $instance = new $passwordPolicyClass();

            if ($instance instanceof PasswordPolicyHook) {
                $this->passwordPolicy = $instance;
            }
        } else {
            $this->passwordPolicy = self::DEFAULT_PASSWORD_POLICY;
        }
    }

    public function addPasswordPolicyDescription(Form $form): void
    {
        $description = $this->passwordPolicy->getDescription();

        if ($description !== null) {
            $form->addDescription($description);
        }
    }

    public function getPasswordValidator(): PasswordValidator
    {
        return new PasswordValidator($this->passwordPolicy);
    }
}
