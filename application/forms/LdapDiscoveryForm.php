<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Protocol\Ldap\Exception as LdapException;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Protocol\Dns;
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
                'label'         => t('Search Domain'),
                'description'   => t('Search this domain for records of available servers.'),
            )
        );

        if (false) {
            $this->addElement(
                'note',
                'additional_description',
                array(
                    'value' => t('No Ldap servers found on this domain.'
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
                    'label'         => t('Host'),
                    'description'   => t('IP or host name to search.'),
                )
            );

            $this->addElement(
                'text',
                'port',
                array(
                    'required'      => false,
                    'label'         => t('Port'),
                    'description'   => t('Port', 389),
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