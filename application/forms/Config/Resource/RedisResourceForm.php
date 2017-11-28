<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Application\Platform;
use Icinga\Web\Form;

/**
 * Form class for adding/modifying Redis resources
 */
class RedisResourceForm extends Form
{
    public function init()
    {
        $this->setName('form_config_resource_redis');
    }

    public function createElements(array $formData)
    {
        $winNt = Platform::isWindows();

        $this->addElements(array(
            array(
                'text',
                'name',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Resource Name'),
                    'description'   => $this->translate('The unique name of this resource')
                )
            ),
            array(
                'text',
                'host',
                array(
                    'label'         => $this->translate('Host'),
                    'description'   => $winNt
                        ? $this->translate('The hostname of the database')
                        : $this->translate('The hostname of the database or a *nix socket'),
                    'required'      => true,
                    'value'         => 'localhost'
                )
            ),
            array(
                'number',
                'port',
                array(
                    'label'         => $this->translate('Port'),
                    'description'   => $winNt
                        ? $this->translate('The port to use (defaults to 6379)')
                        : $this->translate('The port to use (defaults to 6379 if the host is not a *nix socket)')
                )
            ),
            array(
                'number',
                'dbindex',
                array(
                    'label'         => $this->translate('Database Index'),
                    'description'   => $this->translate('The index of the database to use (defaults to 0)')
                )
            ),
            array(
                'password',
                'password',
                array(
                    'label'             => $this->translate('Password'),
                    'description'       => $this->translate('The password to use for authentication'),
                    'renderPassword'    => true
                )
            ),
            array(
                'checkbox',
                'persistent',
                array(
                    'description'   => $this->translate(
                        'Check this box for persistent database connections. Persistent connections are not closed at the'
                        . ' end of a request, but are cached and re-used'
                    ),
                    'label'         => $this->translate('Persistent')
                )
            )
        ));

        return $this;
    }
}
