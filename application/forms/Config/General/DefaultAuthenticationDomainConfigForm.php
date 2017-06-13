<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Web\Form;

/**
 * Configuration form for the default domain for authentication
 *
 * This form is not used directly but as subform to the {@link GeneralConfigForm}.
 */
class DefaultAuthenticationDomainConfigForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_general_authentication');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'authentication_default_domain',
            array(
                'label'         => $this->translate('Default Login Domain'),
                'description'   => $this->translate(
                    'If a user logs in without specifying any domain (e.g. "jdoe" instead of "jdoe@example.com"),'
                    . ' this default domain will be assumed for the user. Note that if none your LDAP authentication'
                    . ' backends are configured to be responsible for this domain or if none of your authentication'
                    . ' backends holds usernames with the domain part, users will not be able to login.'
                )
            )
        );

        return $this;
    }
}
