<?php

namespace Icinga\Forms\Config\General;

use Icinga\Application\Hook;
use Icinga\Web\Form;

/**
 * Configuration form for password policy selection
 *
 * This form is not used directly but as subform for the {@link GeneralConfigForm}.
 */
class PasswordPolicyConfigForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        $this->setName('form_config_general_password_policy');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'checkbox',
            'global_password_policy',
            array(
                'label'       => $this->translate('Password Policy'),
                'value'       => true,
                'description' => $this->translate(
                    'Enforce strong password requirements for new passwords'
                ),
            )
        );

        $passwordPolicies = [];

        foreach (Hook::all('passwordpolicy') as $class => $policy) {
            $passwordPolicies[$class] = $policy->getName();
        }

        asort($passwordPolicies);
        $this->addElement(
            'select',
            'global_password_policy',
            array(
                'description' => $this->translate(
                    'Enforce strong password requirements for new passwords'
                ),
                'label'       => $this->translate('Password Policy'),
                'multiOptions' => array_merge(
                    ['' => sprintf(
                        ' - %s - ',
                        $this->translate('No Password Policy')
                    )],
                    $passwordPolicies
                ),
            )
        );

        return $this;
    }
}
