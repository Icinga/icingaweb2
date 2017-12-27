<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Security;

use Icinga\Application\Icinga;
use Icinga\Forms\ConfigForm;

class PermissionsRoleForm extends ConfigForm
{
    /**
     * Provided permissions by currently loaded modules
     *
     * @var array
     */
    protected $providedPermissions;

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
        // Role Permissions
        $elements = array();
        foreach ($this->providedPermissions as $name => $module) {
            foreach ($module as $location => $label) {
                $elements[] = $this->createElement(
                    'checkbox',
                    $label,
                    array(
                        'label' => $label,
                        'description' => $location
                    )
                );
            }
            if (!empty($elements)) {
                $name = ucfirst($name);
                $this->addDisplayGroup($elements, $name, array('class' => 'checkbox-group toggle-checkbox-content'));
                $this->getDisplayGroup($name)->removeDecorator('DtDdWrapper');
                $this->getDisplayGroup($name)->removeDecorator('HtmlTag');
                $this->getDisplayGroup($name)->addDecorator(
                    'ToggleCheckbox',
                    array(
                        'id' => $name,
                        'label' => $name
                    )
                );
                $this->getDisplayGroup($name)->addDecorator(
                    'HtmlTag',
                    array(
                        'tag' => 'div',
                        'class' => 'control-group toggle-checkbox'
                    )
                );
                $elements = array();
            }
        }

        return $this;
    }
}
