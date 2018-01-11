<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Security;

use LogicException;
use Zend_Form_Element;
use Icinga\Application\Icinga;
use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\Util\StringHelper;

/**
 * Form for managing roles
 */
class RoleForm extends ConfigForm
{
    /**
     * Provided permissions by currently loaded modules
     *
     * @var array
     */
    protected $providedPermissions;

    /**
     * Provided restrictions by currently loaded modules
     *
     * @var array
     */
    protected $providedRestrictions;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->providedPermissions = array('General' =>
            array(
                'all'       => $this->translate('Everything'),
                'admin'     => $this->translate('Grant admin permissions, e.g. manage announcements'),
                'config/*'  => $this->translate('Config access')
            ));

        $this->providedPermissions['Application'] =
            array(
                'application/share/navigation'  => $this->translate(
                    'Share navigation items'
                ),
                'application/stacktraces'       => $this->translate(
                    'Adjust in the preferences whether to show stacktraces'
                ),
                'application/log'               => $this->translate(
                    'View the application log'
                )
            );

        $helper = new Zend_Form_Element('bogus');
        $this->providedRestrictions = array(
            $helper->filterName('application/share/users') => array(
                'name'          => 'application/share/users',
                'description'   => $this->translate(
                    'Restrict which users this role can share items and information with'
                )
            ),
            $helper->filterName('application/share/groups') => array(
                'name'          => 'application/share/groups',
                'description'   => $this->translate(
                    'Restrict which groups this role can share items and information with'
                )
            )
        );

        $this->providedPermissions['Module'] = array();
        $mm = Icinga::app()->getModuleManager();
        foreach ($mm->listInstalledModules() as $moduleName) {
            $modulePermission = $mm::MODULE_PERMISSION_NS . $moduleName;
            $this->providedPermissions['Module'][$modulePermission] = sprintf(
                $this->translate('Allow access to module %s'),
                $moduleName
            );

            $module = $mm->getModule($moduleName, false);
            $modulePermissions = array();
            foreach ($module->getProvidedPermissions() as $permission) {
                /** @var object $permission */
                $modulePermissions[$permission->name] = $permission->description;
            }
            foreach ($module->getProvidedRestrictions() as $restriction) {
                /** @var object $restriction */
                // Zend only permits alphanumerics, the underscore, the circumflex and any ASCII character in range
                // \x7f to \xff (127 to 255)
                $name = $helper->filterName($restriction->name);
                while (isset($this->providedRestrictions[$name])) {
                    // Because Zend_Form_Element::filterName() replaces any not permitted character with the empty
                    // string we may have duplicate names, e.g. 're/striction' and 'restriction'
                    $name .= '_';
                }
                $this->providedRestrictions[$name] = array(
                    'description' => $restriction->description,
                    'name'        => $restriction->name
                );
            }

            if ($modulePermissions) {
                $this->providedPermissions[$moduleName] = $modulePermissions;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $generalRoleForm = new GeneralRoleForm();
        $this->addSubForm($generalRoleForm->create($formData));
        $generalRoleForm->setTitle($this->translate('General Information'));
        $generalRoleForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        $permissionRoleForm = new PermissionsRoleForm();
        $this->addSubForm($permissionRoleForm->create($formData));
        $permissionRoleForm->setTitle($this->translate('Permissions'));
        $permissionRoleForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        $restrictionRoleForm = new RestrictionsRoleForm();
        $this->addSubForm($restrictionRoleForm->create($formData));
        $restrictionRoleForm->setTitle($this->translate('Restrictions'));
        $restrictionRoleForm->addDecorator(
            'Description',
            array('tag' => 'h2', 'class' => 'form-sub-header', 'placement' => 'prepend')
        );

        return $this;
    }

    /**
     * Load a role
     *
     * @param   string  $name           The name of the role
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @throws  NotFoundError           If the given role does not exist
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function load($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t load role \'%s\'. Config is not set', $name));
        }
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError(
                $this->translate('Can\'t load role \'%s\'. Role does not exist'),
                $name
            );
        }
        $role = $this->config->getSection($name)->toArray();
        $role['permissions'] = ! empty($role['permissions'])
            ? StringHelper::trimSplit($role['permissions'])
            : null;
        $role['name'] = $name;
        $restrictions = array();
        foreach ($this->providedRestrictions as $name => $spec) {
            if (isset($role[$spec['name']])) {
                // Translate restriction names to filtered element names
                $restrictions[$name] = $role[$spec['name']];
                unset($role[$spec['name']]);
            }
        }
        $role = array_merge($role, $restrictions);
        $this->populate($role);
        return $this;
    }

    /**
     * Add a role
     *
     * @param   string  $name           The name of the role
     * @param   array   $values
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @throws  AlreadyExistsException  If the role to add already exists
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function add($name, array $values)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t add role \'%s\'. Config is not set', $name));
        }
        if ($this->config->hasSection($name)) {
            throw new AlreadyExistsException(
                $this->translate('Can\'t add role \'%s\'. Role already exists'),
                $name
            );
        }
        $this->config->setSection($name, $values);
        return $this;
    }

    /**
     * Remove a role
     *
     * @param   string  $name           The name of the role
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @throws  NotFoundError           If the role does not exist
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function remove($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t remove role \'%s\'. Config is not set', $name));
        }
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError(
                $this->translate('Can\'t remove role \'%s\'. Role does not exist'),
                $name
            );
        }
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Update a role
     *
     * @param   string  $name           The possibly new name of the role
     * @param   array   $values
     * @param   string  $oldName        The name of the role to update
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @throws  NotFoundError           If the role to update does not exist
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function update($name, array $values, $oldName)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t update role \'%s\'. Config is not set', $name));
        }
        if ($name !== $oldName) {
            // The permission got a new name
            $this->remove($oldName);
            $this->add($name, $values);
        } else {
            if (! $this->config->hasSection($name)) {
                throw new NotFoundError(
                    $this->translate('Can\'t update role \'%s\'. Role does not exist'),
                    $name
                );
            }
            $this->config->setSection($name, $values);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = static::transformEmptyValuesToNull(parent::getValues($suppressArrayNotation));
        if (isset($values['permissions'])) {
            $values['permissions'] = implode(', ', $values['permissions']);
        }
        $restrictions = array();
        foreach ($this->providedRestrictions as $name => $spec) {
            if (isset($values[$name])) {
                // Translate filtered element names to restriction names
                $restrictions[$spec['name']] = $values[$name];
                unset($values[$name]);
            }
        }
        $values = array_merge($values, $restrictions);
        return $values;
    }
}
