<?php

namespace Icinga\Forms\Account;

use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Authentication\TwoFactor;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatForm;

class TwoFactorChooseMethodForm extends CompatForm
{
    public const TWO_FACTOR_METHOD_KEY = 'twofactor_method';

    protected function assemble(): void
    {
        $this->addElement('select', static::TWO_FACTOR_METHOD_KEY, [
            'class'   => 'autosubmit',
            'options' => array_merge(
                ['' => sprintf(' - %s - ', $this->translate('Please choose'))],
                array_combine(
                    array_map(fn(TwoFactor $method) => $method->getName(), TwoFactorHook::all()),
                    array_map(fn(TwoFactor $method) => $method->getDisplayName(), TwoFactorHook::all())
                )
            ),
            'value'   => TwoFactorHook::loadFromDb() ?? TwoFactorHook::all()[0] ?? ''
        ]);

        if ($twoFactor = TwoFactorHook::fromName($this->getPopulatedValue(self::TWO_FACTOR_METHOD_KEY) ?? '')) {

        }
    }
}
