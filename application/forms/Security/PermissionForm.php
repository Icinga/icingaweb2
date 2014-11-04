<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Security;

use InvalidArgumentException;
use LogicException;
use Icinga\Application\Icinga;
use Icinga\Form\ConfigForm;
use Icinga\Util\String;

/**
 * Form for granting and revoking user and group permissions
 */
class PermissionForm extends ConfigForm
{
    /**
     * Provided permissions by currently loaded modules
     *
     * @var array
     */
    protected $providedPermissions = array();

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::init() For the method documentation.
     */
    public function init()
    {
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            foreach ($module->getProvidedPermissions() as $permission) {
                /** @var object $permission */
                $this->providedPermissions[$permission->name] = $permission->name . ': ' . $permission->description;
            }
        }
        $this->setSubmitLabel(t('Grant Permissions'));
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'text',
                'name',
                array(
                    'required'      => true,
                    'label'         => t('Permission Name'),
                    'description'   => t('The name of the permission')
                ),
            ),
            array(
                'textarea',
                'users',
                array(
                    'label'         => t('Users'),
                    'description'   => t('Comma-separated list of users that are granted the permissions')
                ),
            ),
            array(
                'textarea',
                'groups',
                array(
                    'label'         => t('Groups'),
                    'description'   => t('Comma-separated list of groups that are granted the permissions')
                ),
            ),
            array(
                'multiselect',
                'permissions',
                array(
                    'label'         => t('Permissions Set'),
                    'description'   => t('The permissions to grant. You may select more than one permission'),
                    'multiOptions'  => $this->providedPermissions
                )
            )
        ));
        return $this;
    }

    /**
     * Load a permission
     *
     * @param   string  $name   The name of the permission
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function load($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t load permission \'%s\'. Config is not set', $name));
        }
        if (! isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t load permission \'%s\'. Permission does not exist'),
                $name
            ));
        }
        $permission = $this->config->{$name}->toArray();
        $permission['permissions'] = ! empty($permission['permissions'])
            ? String::trimSplit($permission['permissions'])
            : null;
        $permission['name'] = $name;
        $this->populate($permission);
        return $this;
    }

    /**
     * Add a permission
     *
     * @param   string  $name               The name of the permission
     * @param   array   $values
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the permission to add already exists
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function add($name, array $values)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t add permission \'%s\'. Config is not set', $name));
        }
        if (isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t add permission \'%s\'. Permission already exists'),
                $name
            ));
        }
        $this->config->{$name} = $values;
        return $this;
    }


    /**
     * Update a permission
     *
     * @param   string  $name               The possibly new name of the permission
     * @param   array   $values
     * @param   string  $oldName            The name of the permission to update
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the permission to update does not exist
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function update($name, array $values, $oldName)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t update permission \'%s\'. Config is not set', $name));
        }
        if ($name !== $oldName) {
            // The permission got a new name
            $this->remove($oldName);
            $this->add($name, $values);
        } else {
            if (! isset($this->config->{$name})) {
                throw new InvalidArgumentException(sprintf(
                    t('Can\'t update permission \'%s\'. Permission does not exist'),
                    $name
                ));
            }
            $this->config->{$name} = $values;
        }
        return $this;
    }

    /**
     * Remove a permission
     *
     * @param   string  $name               The name of the permission
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the permission does not exist
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function remove($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t remove permission \'%s\'. Config is not set', $name));
        }
        if (! isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t remove permission \'%s\'. Permission does not exist'),
                $name
            ));
        }
        unset($this->config->{$name});
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::getValues() For the method documentation.
     */
    public function getValues($suppressArrayNotation = false)
    {
        $permissions = $this->getElement('permissions')->getValue();
        return array(
            'users'         => $this->getElement('users')->getValue(),
            'groups'        => $this->getElement('groups')->getValue(),
            'permissions'   => ! empty($permissions) ? implode(', ', $permissions) : null
        );
    }
}
