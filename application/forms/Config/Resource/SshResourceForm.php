<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Web\Form;
use Icinga\Util\File;
use Zend_Validate_Callback;

/**
 * Form class for adding/modifying ssh identity resources
 */
class SshResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_ssh');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
        $this->addElement(
            'text',
            'user',
            array(
                'required'      => true,
                'label'         => $this->translate('User'),
                'description'   => $this->translate(
                    'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                    . ' possible for this user'
                )
            )
        );

        if ($this->getRequest()->getActionName() != 'editresource') {
            $callbackValidator = new Zend_Validate_Callback(function ($value) {
                if (openssl_pkey_get_private($value) === false) {
                    return false;
                }
                return true;
            });
            $callbackValidator->setMessage(
                $this->translate('The given SSH key is invalid'),
                Zend_Validate_Callback::INVALID_VALUE
            );

            $this->addElement(
                'textarea',
                'private_key',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Private Key'),
                    'description'   => $this->translate('The private key which will be used for the SSH connections'),
                    'class'         => 'resource ssh-identity',
                    'validators'    => array($callbackValidator)
                )
            );
        } else {
            $resourceName = $formData['name'];
            $this->addElement(
                'note',
                'private_key_note',
                array(
                    'escape'        => false,
                    'label'         => $this->translate('Private Key'),
                    'value'         => sprintf(
                        '<a href="%1$s" data-base-target="_next" title="%2$s" aria-label="%2$s">%3$s</a>',
                        $this->getView()->url('config/removeresource', array('resource' => $resourceName)),
                        sprintf($this->translate(
                            'Remove the %s resource'
                        ), $resourceName),
                        $this->translate('To modify the private key you must recreate this resource.')
                    )
                )
            );
        }

        return $this;
    }

    /**
     * Remove the assigned key to the resource
     *
     * @param ConfigObject $config
     *
     * @return bool
     */
    public static function beforeRemove(ConfigObject $config)
    {
        $file = $config->private_key;

        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }

    /**
     * Creates the assigned key to the resource
     *
     * @param ResourceConfigForm $form
     *
     * @return bool
     */
    public static function beforeAdd(ResourceConfigForm $form)
    {
        $configDir = Icinga::app()->getConfigDir();
        $user = $form->getElement('user')->getValue();

        $filePath = $configDir . '/ssh/' . $user;

        if (! file_exists($filePath)) {
            $file = File::create($filePath, 0600);
        } else {
            $form->error(
                sprintf($form->translate('The private key for the user "%s" is already exists.'), $user)
            );
            return false;
        }

        $file->fwrite($form->getElement('private_key')->getValue());

        $form->getElement('private_key')->setValue($configDir . '/ssh/' . $user);

        return true;
    }
}
