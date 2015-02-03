<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
                'required'      => true,
                'label'         => $this->translate('Search Domain'),
                'description'   => $this->translate('Search this domain for records of available servers.'),
            )
        );

        if (false) {
            $this->addElement(
                'note',
                'additional_description',
                array(
                    'value' => $this->translate('No Ldap servers found on this domain.'
                        . ' You can try to specify host and port and try again, or just skip this step and '
                        . 'configure the server manually.'
                    )
                )
            );
            $this->addElement(
                'text',
                'hostname',
                array(
                    'required'      => false,
                    'label'         => $this->translate('Host'),
                    'description'   => $this->translate('IP or hostname to search.'),
                )
            );

            $this->addElement(
                'text',
                'port',
                array(
                    'required'      => false,
                    'label'         => $this->translate('Port'),
                    'description'   => $this->translate('Port', 389),
                )
            );
        }
        return $this;
    }

    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }
        return true;
    }
}
