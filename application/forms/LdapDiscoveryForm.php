<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Web\Form;

class LdapDiscoveryForm extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('form_ldap_discovery');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'domain',
            array(
                'label'         => $this->translate('Search Domain'),
                'description'   => $this->translate('Search this domain for records of available servers.'),
            )
        );

        return $this;
    }
}
