<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Security;

use Icinga\Application\Hook\ConfigFormEventsHook;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Manager;
use Icinga\Authentication\AdmissionLoader;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\ConfigForm;
use Icinga\Forms\RepositoryForm;
use Icinga\Util\StringHelper;
use Icinga\Web\Notification;
use ipl\Web\Widget\Icon;

/**
 * Form for managing roles
 */
class RoleForm extends RepositoryForm
{
    /**
     * The name to use instead of `*`
     */
    const WILDCARD_NAME = 'allAndEverything';

    /**
     * The prefix used to deny a permission
     */
    const DENY_PREFIX = 'no-';

    /**
     * Provided permissions by currently installed modules
     *
     * @var array
     */
    protected $providedPermissions;

    /**
     * Provided restrictions by currently installed modules
     *
     * @var array
     */
    protected $providedRestrictions;

    public function init()
    {
        $this->setAttrib('class', self::DEFAULT_CLASSES . ' role-form');

        list($this->providedPermissions, $this->providedRestrictions) = static::collectProvidedPrivileges();
    }

    protected function createFilter()
    {
        return Filter::where('name', $this->getIdentifier());
    }

    public function filterName($value, $allowBrackets = false)
    {
        return parent::filterName($value, $allowBrackets) . '_element';
    }

    public function createInsertElements(array $formData = array())
    {
        $this->addElement(
            'text',
            'name',
            [
                'required'      => true,
                'label'         => $this->translate('Role Name'),
                'description'   => $this->translate('The name of the role')
            ]
        );
        $this->addElement(
            'select',
            'parent',
            [
                'label'         => $this->translate('Inherit From'),
                'description'   => $this->translate('Choose a role from which to inherit privileges'),
                'value'         => '',
                'multiOptions'  => array_merge(
                    ['' => $this->translate('None', 'parent role')],
                    $this->collectRoles()
                )
            ]
        );
        $this->addElement(
            'textarea',
            'users',
            [
                'label'         => $this->translate('Users'),
                'description'   => $this->translate('Comma-separated list of users that are assigned to the role')
            ]
        );
        $this->addElement(
            'textarea',
            'groups',
            [
                'label'         => $this->translate('Groups'),
                'description'   => $this->translate('Comma-separated list of groups that are assigned to the role')
            ]
        );
        $this->addElement(
            'checkbox',
            self::WILDCARD_NAME,
            [
                'autosubmit'    => true,
                'label'         => $this->translate('Administrative Access'),
                'description'   => $this->translate('Everything is allowed')
            ]
        );
        $this->addElement(
            'checkbox',
            'unrestricted',
            [
                'autosubmit'        => true,
                'uncheckedValue'    => null,
                'label'             => $this->translate('Unrestricted Access'),
                'description'       => $this->translate('Access to any data is completely unrestricted')
            ]
        );

        $hasAdminPerm = isset($formData[self::WILDCARD_NAME]) && $formData[self::WILDCARD_NAME];
        $isUnrestricted = isset($formData['unrestricted']) && $formData['unrestricted'];
        foreach ($this->providedPermissions as $moduleName => $permissionList) {
            $this->sortPermissions($permissionList);

            $anythingGranted = false;
            $anythingRefused = false;
            $anythingRestricted = false;

            $elements = [$moduleName . '_header'];
            // The actual element is added last

            $elements[] = 'permission_header';
            $this->addElement('note', 'permission_header', [
                'decorators'    => [['Callback', ['callback' => function () {
                    return '<h4>' . $this->translate('Permissions') . '</h4>'
                        . $this->getView()->icon('ok', $this->translate(
                            'Grant access by toggling a switch below'
                        ))
                        . $this->getView()->icon('cancel', $this->translate(
                            'Deny access by toggling a switch below'
                        ));
                }]], ['HtmlTag', ['tag' => 'div']]]
            ]);

            $hasFullPerm = false;
            foreach ($permissionList as $name => $spec) {
                $elementName = $this->filterName($name);

                if (isset($formData[$elementName]) && $formData[$elementName]) {
                    $anythingGranted = true;
                }

                if ($hasFullPerm || $hasAdminPerm) {
                    $elementName .= '_fake';
                }

                $denyCheckbox = null;
                if (! isset($spec['isFullPerm'])
                    && substr($name, 0, strlen(self::DENY_PREFIX)) !== self::DENY_PREFIX
                ) {
                    $denyCheckbox = $this->createElement('checkbox', $this->filterName(self::DENY_PREFIX . $name), [
                        'decorators'    => ['ViewHelper']
                    ]);
                    $this->addElement($denyCheckbox);
                    $this->removeFromIteration($denyCheckbox->getName());

                    if (isset($formData[$denyCheckbox->getName()]) && $formData[$denyCheckbox->getName()]) {
                        $anythingRefused = true;
                    }
                }

                $elements[] = $elementName;
                $this->addElement(
                    'checkbox',
                    $elementName,
                    [
                        'ignore'        => $hasFullPerm || $hasAdminPerm,
                        'autosubmit'    => isset($spec['isFullPerm']),
                        'disabled'      => $hasFullPerm || $hasAdminPerm ?: null,
                        'value'         => $hasFullPerm || $hasAdminPerm,
                        'label'         => isset($spec['label'])
                            ? $spec['label']
                            : join('', iterator_to_array(call_user_func(function ($segments) {
                                foreach ($segments as $segment) {
                                    if ($segment[0] === '/') {
                                        // Adds a zero-width char after each slash to help browsers break onto newlines
                                        yield '/&#8203;';
                                        yield '<span class="no-wrap">' . substr($segment, 1) . '</span>';
                                    } else {
                                        yield '<em>' . $segment . '</em>';
                                    }
                                }
                            }, preg_split(
                                '~(/[^/]+)~',
                                $name,
                                -1,
                                PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                            )))),
                        'description'   => isset($spec['description']) ? $spec['description'] : $name,
                        'decorators'    => array_merge(
                            array_slice(self::$defaultElementDecorators, 0, 3),
                            [['Callback', ['callback' => function () use ($denyCheckbox) {
                                return $denyCheckbox ? $denyCheckbox->render() : '';
                            }]]],
                            array_slice(self::$defaultElementDecorators, 3)
                        )
                    ]
                )
                    ->getElement($elementName)
                    ->getDecorator('Label')
                    ->setOption('escape', false);

                if ($hasFullPerm || $hasAdminPerm) {
                    // Add a hidden element to preserve the configured permission value
                    $this->addElement('hidden', $this->filterName($name));
                }

                if (isset($spec['isFullPerm'])) {
                    $filteredName = $this->filterName($name);
                    $hasFullPerm = isset($formData[$filteredName]) && $formData[$filteredName];
                }
            }

            if (isset($this->providedRestrictions[$moduleName])) {
                $elements[] = 'restriction_header';
                $this->addElement('note', 'restriction_header', [
                    'value'         => '<h4>' . $this->translate('Restrictions') . '</h4>',
                    'decorators'    => ['ViewHelper']
                ]);

                foreach ($this->providedRestrictions[$moduleName] as $name => $spec) {
                    $elementName = $this->filterName($name);

                    if (isset($formData[$elementName]) && $formData[$elementName]) {
                        $anythingRestricted = true;
                    }

                    $elements[] = $elementName;
                    $this->addElement(
                        'text',
                        $elementName,
                        [
                            'label'         => isset($spec['label'])
                                ? $spec['label']
                                : join('', iterator_to_array(call_user_func(function ($segments) {
                                    foreach ($segments as $segment) {
                                        if ($segment[0] === '/') {
                                            // Add zero-width char after each slash to help browsers break onto newlines
                                            yield '/&#8203;';
                                            yield '<span class="no-wrap">' . substr($segment, 1) . '</span>';
                                        } else {
                                            yield '<em>' . $segment . '</em>';
                                        }
                                    }
                                }, preg_split(
                                    '~(/[^/]+)~',
                                    $name,
                                    -1,
                                    PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
                                )))),
                            'description'   => $spec['description'],
                            'class'         => $isUnrestricted ? 'unrestricted-role' : '',
                            'readonly'      => $isUnrestricted ?: null
                        ]
                    )
                        ->getElement($elementName)
                        ->getDecorator('Label')
                        ->setOption('escape', false);
                }
            }

            $this->addElement(
                'note',
                $moduleName . '_header',
                [
                    'decorators'    => ['ViewHelper'],
                    'value'         => '<summary class="collapsible-control">'
                        . '<span>' . ($moduleName !== 'application'
                            ? sprintf('%s <em>%s</em>', $moduleName, $this->translate('Module'))
                            :  'Icinga Web 2'
                        ) . '</span>'
                        . '<span class="privilege-preview">'
                        . ($hasAdminPerm || $anythingGranted ? new Icon('check-circle', ['class' => 'granted']) : '')
                        . ($anythingRefused ? new Icon('times-circle', ['class' => 'refused']) : '')
                        . (! $isUnrestricted && $anythingRestricted
                            ? new Icon('filter', ['class' => 'restricted'])
                            : ''
                        )
                        . '</span>'
                        . new Icon('angles-down', ['class' => 'collapse-icon'])
                        . new Icon('angles-left', ['class' => 'expand-icon'])
                        . '</summary>'
                ]
            );

            $this->addDisplayGroup($elements, $moduleName . '_elements', [
                'decorators'    => [
                    'FormElements',
                    ['HtmlTag', [
                        'tag'   => 'details',
                        'class' => 'collapsible'
                    ]],
                    ['Fieldset']
                ]
            ]);
        }
    }

    protected function createDeleteElements(array $formData)
    {
    }

    public function fetchEntry()
    {
        $role = parent::fetchEntry();
        if ($role === false) {
            return false;
        }

        $values = [
            'parent'            => $role->parent,
            'name'              => $role->name,
            'users'             => $role->users,
            'groups'            => $role->groups,
            'unrestricted'      => $role->unrestricted,
            self::WILDCARD_NAME => $role->permissions && preg_match('~(?>^|,)\*(?>$|,)~', $role->permissions)
        ];

        if (! empty($role->permissions) || ! empty($role->refusals)) {
            $permissions = StringHelper::trimSplit($role->permissions);
            $refusals = StringHelper::trimSplit($role->refusals);

            list($permissions, $newRefusals) = AdmissionLoader::migrateLegacyPermissions($permissions);
            if (! empty($newRefusals)) {
                array_push($refusals, ...$newRefusals);
            }

            foreach ($this->providedPermissions as $moduleName => $permissionList) {
                $hasFullPerm = false;
                foreach ($permissionList as $name => $spec) {
                    if (in_array($name, $permissions, true)) {
                        $values[$this->filterName($name)] = 1;

                        if (isset($spec['isFullPerm'])) {
                            $hasFullPerm = true;
                        }
                    }

                    if (in_array($name, $refusals, true)) {
                        $values[$this->filterName(self::DENY_PREFIX . $name)] = 1;
                    }
                }

                if ($hasFullPerm) {
                    unset($values[$this->filterName(Manager::MODULE_PERMISSION_NS . $moduleName)]);
                }
            }
        }

        foreach ($this->providedRestrictions as $moduleName => $restrictionList) {
            foreach ($restrictionList as $name => $spec) {
                if (isset($role->$name)) {
                    $values[$this->filterName($name)] = $role->$name;
                }
            }
        }

        return (object) $values;
    }

    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);

        foreach ($this->providedRestrictions as $moduleName => $restrictionList) {
            foreach ($restrictionList as $name => $spec) {
                $elementName = $this->filterName($name);
                if (isset($values[$elementName])) {
                    $values[$name] = $values[$elementName];
                    unset($values[$elementName]);
                }
            }
        }

        $permissions = [];
        if (isset($values[self::WILDCARD_NAME]) && $values[self::WILDCARD_NAME]) {
            $permissions[] = '*';
        }

        $refusals = [];
        foreach ($this->providedPermissions as $moduleName => $permissionList) {
            $hasFullPerm = false;
            foreach ($permissionList as $name => $spec) {
                $elementName = $this->filterName($name);
                if (isset($values[$elementName]) && $values[$elementName]) {
                    $permissions[] = $name;

                    if (isset($spec['isFullPerm'])) {
                        $hasFullPerm = true;
                    }
                }

                $denyName = $this->filterName(self::DENY_PREFIX . $name);
                if (isset($values[$denyName]) && $values[$denyName]) {
                    $refusals[] = $name;
                }

                unset($values[$elementName], $values[$denyName]);
            }

            $modulePermission = Manager::MODULE_PERMISSION_NS . $moduleName;
            if ($hasFullPerm && ! in_array($modulePermission, $permissions, true)) {
                $permissions[] = $modulePermission;
            }
        }

        unset($values[self::WILDCARD_NAME]);
        $values['refusals'] = join(',', $refusals);
        $values['permissions'] = join(',', $permissions);
        return ConfigForm::transformEmptyValuesToNull($values);
    }

    protected function getInsertMessage($success)
    {
        return $success ? $this->translate('Role created') : $this->translate('Role creation failed');
    }

    protected function getUpdateMessage($success)
    {
        return $success ? $this->translate('Role updated') : $this->translate('Role update failed');
    }

    protected function getDeleteMessage($success)
    {
        return $success ? $this->translate('Role removed') : $this->translate('Role removal failed');
    }

    protected function sortPermissions(&$permissions)
    {
        return uksort($permissions, function ($a, $b) use ($permissions) {
            if (isset($permissions[$a]['isUsagePerm'])) {
                return isset($permissions[$b]['isFullPerm']) ? 1 : -1;
            } elseif (isset($permissions[$b]['isUsagePerm'])) {
                return isset($permissions[$a]['isFullPerm']) ? -1 : 1;
            }

            $aParts = explode('/', $a);
            $bParts = explode('/', $b);

            do {
                $a = array_shift($aParts);
                $b = array_shift($bParts);
            } while ($a === $b);

            return strnatcmp($a ?? '', $b ?? '');
        });
    }

    protected function collectRoles()
    {
        // Function to get all connected children. Used to avoid reference loops
        $getChildren = function ($name, $children = []) use (&$getChildren) {
            foreach ($this->repository->select()->where('parent', $name) as $child) {
                if (isset($children[$child->name])) {
                    // Don't follow already established loops here,
                    // the user should be able to solve such in the UI
                    continue;
                }

                $children[$child->name] = true;
                $children = $getChildren($child->name, $children);
            }

            return $children;
        };

        $children = $this->getIdentifier() !== null ? $getChildren($this->getIdentifier()) : [];

        $names = [];
        foreach ($this->repository->select() as $role) {
            if ($role->name !== $this->getIdentifier() && ! isset($children[$role->name])) {
                $names[] = $role->name;
            }
        }

        return array_combine($names, $names);
    }

    public function isValid($formData)
    {
        $valid = parent::isValid($formData);

        if ($valid && ConfigFormEventsHook::runIsValid($this) === false) {
            foreach (ConfigFormEventsHook::getLastErrors() as $msg) {
                $this->error($msg);
            }

            $valid = false;
        }

        return $valid;
    }

    public function onSuccess()
    {
        if (parent::onSuccess() === false) {
            return false;
        }

        if ($this->getIdentifier() && ($newName = $this->getValue('name')) !== $this->getIdentifier()) {
            $this->onRenameSuccess($this->getIdentifier(), $newName);
        }

        if (ConfigFormEventsHook::runOnSuccess($this) === false) {
            Notification::error($this->translate(
                'Configuration successfully stored. Though, one or more module hooks failed to run.'
                . ' See logs for details'
            ));
        }
    }

    /**
     * Update child roles of role $oldName, set their parent to $newName
     *
     * @param string $oldName
     * @param string $newName
     */
    protected function onRenameSuccess(string $oldName, ?string $newName): void
    {
        $this->repository->update($this->getBaseTable(), ['parent' => $newName], Filter::where('parent', $oldName));
    }

    /**
     * Collect permissions and restrictions provided by Icinga Web 2 and modules
     *
     * @return array[$permissions, $restrictions]
     */
    public static function collectProvidedPrivileges()
    {
        $providedPermissions['application'] = [
            'application/announcements' => [
                'description' => t('Allow to manage announcements')
            ],
            'application/log' => [
                'description' => t('Allow to view the application log')
            ],
            'config/*' => [
                'description' => t('Allow full config access')
            ],
            'config/general' => [
                'description' => t('Allow to adjust the general configuration')
            ],
            'config/modules' => [
                'description' => t('Allow to enable/disable and configure modules')
            ],
            'config/resources' => [
                'description' => t('Allow to manage resources')
            ],
            'config/navigation' => [
                'description' => t('Allow to view and adjust shared navigation items')
            ],
            'config/access-control/*' => [
                'description' => t('Allow to fully manage access-control')
            ],
            'config/access-control/users' => [
                'description' => t('Allow to manage user accounts')
            ],
            'config/access-control/groups' => [
                'description' => t('Allow to manage user groups')
            ],
            'config/access-control/roles' => [
                'description' => t('Allow to manage roles')
            ],
            'user/*' => [
                'description' => t('Allow all account related functionalities')
            ],
            'user/password-change' => [
                'description' => t('Allow password changes in the account preferences')
            ],
            'user/application/stacktraces' => [
                'description' => t('Allow to adjust in the preferences whether to show stacktraces')
            ],
            'user/share/navigation' => [
                'description' => t('Allow to share navigation items')
            ],
            'application/sessions' => [
                'description' => t('Allow to manage user sessions')
            ],
            'application/migrations' => [
                'description' => t('Allow to apply pending application migrations')
            ]
        ];

        $providedRestrictions['application'] = [
            'application/share/users' => [
                'description'   => t('Restrict which users this role can share items and information with')
            ],
            'application/share/groups' => [
                'description'   => t('Restrict which groups this role can share items and information with')
            ]
        ];

        $mm = Icinga::app()->getModuleManager();
        foreach ($mm->listInstalledModules() as $moduleName) {
            $modulePermission = Manager::MODULE_PERMISSION_NS . $moduleName;
            $providedPermissions[$moduleName][$modulePermission] = [
                'isUsagePerm'   => true,
                'label'         => t('General Module Access'),
                'description'   => sprintf(t('Allow access to module %s'), $moduleName)
            ];

            $module = $mm->getModule($moduleName, false);
            $permissions = $module->getProvidedPermissions();

            $providedPermissions[$moduleName][$moduleName . '/*'] = [
                'isFullPerm'    => true,
                'label'         => t('Full Module Access')
            ];

            foreach ($permissions as $permission) {
                /** @var object $permission */
                $providedPermissions[$moduleName][$permission->name] = [
                    'description'   => $permission->description
                ];
            }

            foreach ($module->getProvidedRestrictions() as $restriction) {
                $providedRestrictions[$moduleName][$restriction->name] = [
                    'description'   => $restriction->description
                ];
            }
        }

        return [$providedPermissions, $providedRestrictions];
    }
}
