<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Security;

use Icinga\Application\Icinga;
use Icinga\Application\Modules\Manager;
use Icinga\Application\Web;
use Icinga\Authentication\AdmissionLoader;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Updatable;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryMode;
use Icinga\Util\StringHelper;
use Icinga\Web\Form\RepositoryForm;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\FormattedString;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\FormElement\TextElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;
use ipl\Web\Widget\Icon;

/**
 * Form for managing roles
 */
class RoleForm extends RepositoryForm
{
    /** @var string The name to use instead of `*` */
    public const WILDCARD_NAME = 'allAndEverything';

    /** @var string The prefix used to deny a permission */
    public const DENY_PREFIX = 'no-';

    /** @var string The suffix used for the fieldset names */
    public const FIELDSET_SUFFIX = '_elements';

    /**
     * Provided permissions by currently installed modules
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected array $providedPermissions;

    /**
     * Provided restrictions by currently installed modules
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected array $providedRestrictions;

    /**
     * Create a new RoleForm
     *
     * In addition to the interface {@see RepositoryForm::__construct()}
     * requires for $mode, the repository must implement {@see Updatable}
     * when in {@see RepositoryMode::Delete} to clear the parent references
     * on roles that inherit from the deleted one.
     *
     * @param Repository $repository The repository to work with
     * @param RepositoryMode $mode How to interact with the repository
     * @param ?string $identifier The identifier of the entry to handle
     *   Required for {@see RepositoryMode::Update} and {@see RepositoryMode::Delete}.
     *
     * @throws InvalidArgumentException If $repository does not meet $mode's
     *   interface requirements, in particular if it is not also {@see Updatable}
     *   in {@see RepositoryMode::Delete}
     */
    public function __construct(Repository $repository, RepositoryMode $mode, ?string $identifier = null)
    {
        parent::__construct($repository, $mode, $identifier);

        if ($this->mode === RepositoryMode::Delete && ! ($this->repository instanceof Updatable)) {
            throw new InvalidArgumentException(sprintf(
                'Repository "%s" does not implement %s, which RoleForm requires in %s mode',
                $this->repository::class,
                Updatable::class,
                RepositoryMode::Delete->name,
            ));
        }

        $this->getAttributes()
            ->set('name', 'repo_form_role')
            ->add('class', 'role-form');

        [$this->providedPermissions, $this->providedRestrictions] = static::collectProvidedPrivileges();
    }

    protected function createFilter(): Filter
    {
        return Filter::where('name', $this->getIdentifier());
    }

    protected function assembleCommonElements(): void
    {
        $this->addElement('text', 'name', [
            'description' => $this->translate('The name of the role'),
            'label'       => $this->translate('Role Name'),
            'required'    => true,
        ]);

        $this->addElement('select', 'parent', [
            'description'  => $this->translate('Choose a role from which to inherit privileges'),
            'label'        => $this->translate('Inherit From'),
            'multiOptions' => array_merge(
                ['' => $this->translate('None', 'parent role')],
                $this->collectRoles(),
            ),
            'value'        => '',
        ]);

        $this->addElement('textarea', 'users', [
            'description' => $this->translate('Comma-separated list of users that are assigned to the role'),
            'label'       => $this->translate('Users'),
            'rows'        => 3,
        ]);

        $this->addElement('textarea', 'groups', [
            'description' => $this->translate('Comma-separated list of groups that are assigned to the role'),
            'label'       => $this->translate('Groups'),
            'rows'        => 3,
        ]);

        $this->addElement('checkbox', self::WILDCARD_NAME, [
            'class'       => 'autosubmit',
            'description' => $this->translate('Everything is allowed'),
            'label'       => $this->translate('Administrative Access'),
        ]);

        $this->addElement('checkbox', 'unrestricted', [
            'checkedValue'   => '1',
            'class'          => 'autosubmit',
            'description'    => $this->translate('Access to any data is completely unrestricted'),
            'label'          => $this->translate('Unrestricted Access'),
            'uncheckedValue' => null,
        ]);

        /** @var CheckboxElement $wildcardCheckbox */
        $wildcardCheckbox = $this->getElement(self::WILDCARD_NAME);
        $hasAdminPerm = $wildcardCheckbox->isChecked();

        /** @var CheckboxElement $unrestrictedCheckbox */
        $unrestrictedCheckbox = $this->getElement('unrestricted');
        $isUnrestricted = $unrestrictedCheckbox->isChecked();

        foreach ($this->providedPermissions as $moduleName => $permissionList) {
            $this->sortPermissions($permissionList);

            $anythingGranted = false;
            $anythingRefused = false;
            $anythingRestricted = false;

            $fieldset = new FieldsetElement($moduleName . static::FIELDSET_SUFFIX);

            // Pass on the form's element decorators to the fieldset
            $fieldset->setDefaultElementDecorators($this->getDefaultElementDecorators());
            $fieldset->addElementDecoratorLoaderPaths($this->elementDecoratorLoaderPaths);

            $this->registerElement($fieldset);

            $details = new HtmlElement('details', new Attributes(['class' => 'collapsible']));

            $details->addHtml(new HtmlElement(
                'div',
                null,
                new HtmlElement('h4', null, new Text($this->translate('Permissions'))),
                new HtmlElement('i', new Attributes([
                    'aria-label' => $this->translate('Grant access by toggling a switch below'),
                    'class'      => 'icon-ok',
                    'title'      => $this->translate('Grant access by toggling a switch below'),
                    'role'       => 'img',
                ])),
                new HtmlElement('i', new Attributes([
                    'aria-label' => $this->translate('Deny access by toggling a switch below'),
                    'class'      => 'icon-cancel',
                    'title'      => $this->translate('Deny access by toggling a switch below'),
                    'role'       => 'img',
                ])),
            ));

            $hasFullPerm = false;
            foreach ($permissionList as $name => $spec) {
                $elementName = $this->convertToElementName($name);

                if ($hasFullPerm || $hasAdminPerm) {
                    // Add a hidden element to preserve the configured permission value
                    $fieldset->addElement('hidden', $elementName);
                    $elementName .= '_fake';
                }

                $denyCheckbox = null;
                if (! isset($spec['isFullPerm']) && ! str_starts_with($name, self::DENY_PREFIX)) {
                    /** @var CheckboxElement $denyCheckbox */
                    $denyCheckbox = $fieldset->createElement(
                        'checkbox',
                        $this->convertToElementName(self::DENY_PREFIX . $name)
                    );
                    $fieldset->registerElement($denyCheckbox);

                    if ($denyCheckbox->isChecked()) {
                        $anythingRefused = true;
                    }
                }

                /** @var CheckboxElement $grantCheckbox */
                $grantCheckbox = $fieldset->createElement('checkbox', $elementName, [
                    'class'       => isset($spec['isFullPerm']) ? 'autosubmit' : null,
                    'description' => $spec['description'] ?? $name,
                    'disabled'    => $hasFullPerm || $hasAdminPerm,
                    'ignore'      => $hasFullPerm || $hasAdminPerm,
                    'label'       => $spec['label'] ?? $this->buildPrivilegeLabel($name),
                    'checked'     => $hasFullPerm || $hasAdminPerm,
                ]);
                $fieldset->registerElement($grantCheckbox);
                $details->addHtml($grantCheckbox);

                if ($grantCheckbox->isChecked()) {
                    $anythingGranted = true;
                }

                if ($denyCheckbox !== null) {
                    /** @var CheckboxElement $checkboxElement */
                    $checkboxElement = $fieldset->getElement($elementName);
                    $checkboxElement->getDecorators()
                        ->addDecorator('DenyToggle', new class ($denyCheckbox) implements FormElementDecoration {
                            public function __construct(private readonly CheckboxElement $denyCheckbox)
                            {
                            }

                            public function decorateFormElement(
                                DecorationResult $result,
                                FormElement $formElement,
                            ): void {
                                if (! $formElement instanceof CheckboxElement) {
                                    return;
                                }

                                /** @var Web $app */
                                $app = Icinga::app();
                                $denyId = $app->getRequest()->protectId($this->denyCheckbox->getName());
                                $this->denyCheckbox->getAttributes()
                                    ->set('id', $denyId)
                                    ->add('class', 'sr-only');

                                $classes = ['toggle-switch'];
                                if ($this->denyCheckbox->getAttributes()->get('disabled')->getValue()) {
                                    $classes[] = 'disabled';
                                }

                                /** @var HtmlDocument $wrapper */
                                $wrapper = $formElement->getWrapper();
                                $wrapper->addHtml($this->denyCheckbox, new HtmlElement(
                                    'label',
                                    new Attributes(['class' => $classes, 'aria-hidden' => 'true', 'for' => $denyId]),
                                    new HtmlElement('span', new Attributes(['class' => 'toggle-slider'])),
                                ));
                            }
                        });
                }

                $grantCheckbox->applyDecoration();

                if (isset($spec['isFullPerm'])) {
                    $hasFullPerm = $grantCheckbox->isChecked();
                }
            }

            if (isset($this->providedRestrictions[$moduleName])) {
                $details->addHtml(new HtmlElement('h4', null, new Text($this->translate('Restrictions'))));

                foreach ($this->providedRestrictions[$moduleName] as $name => $spec) {
                    /** @var TextElement $restrictionElement */
                    $restrictionElement = $fieldset->createElement('text', $this->convertToElementName($name), [
                        'class'       => $isUnrestricted ? 'unrestricted-role' : '',
                        'description' => $spec['description'],
                        'label'       => $spec['label'] ?? $this->buildPrivilegeLabel($name),
                        'readonly'    => $isUnrestricted ?: null,
                    ]);
                    $fieldset->registerElement($restrictionElement);
                    $restrictionElement->applyDecoration();
                    $details->addHtml($restrictionElement);

                    if (! Str::isEmpty($restrictionElement->getValue())) {
                        $anythingRestricted = true;
                    }
                }
            }

            $privilegePreview = new HtmlElement('span', new Attributes(['class' => 'privilege-preview']));

            if ($hasAdminPerm || $anythingGranted) {
                $privilegePreview->addHtml(new Icon('check-circle', ['class' => 'granted']));
            }
            if ($anythingRefused) {
                $privilegePreview->addHtml(new Icon('times-circle', ['class' => 'refused']));
            }
            if (! $isUnrestricted && $anythingRestricted) {
                $privilegePreview->addHtml(new Icon('filter', ['class' => 'restricted']));
            }

            $summary = new HtmlElement(
                'summary',
                new Attributes(['class' => 'collapsible-control']),
                new HtmlElement('span', null, $moduleName !== 'application'
                    ? new FormattedString('%s %s', [
                        new Text($moduleName),
                        new HtmlElement('em', null, new Text($this->translate('Module'))),
                    ])
                    : new Text('Icinga Web 2')),
                $privilegePreview,
                new Icon('angles-down', ['class' => 'collapse-icon']),
                new Icon('angles-left', ['class' => 'expand-icon']),
            );

            $details->prependHtml($summary);
            $fieldset->addHtml($details);
            $this->addHtml($fieldset);
        }
    }

    protected function assembleInsertElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('submit', 'submit_add', ['label' => $this->translate('Create Role')]);
    }

    protected function assembleUpdateElements(): void
    {
        $this->assembleCommonElements();

        $this->addElement('submit', 'submit_update', ['label' => $this->translate('Update Role')]);
    }

    protected function assembleDeleteElements(): void
    {
        $this->addElement('submit', 'submit_remove', ['label' => $this->translate('Confirm Removal')]);
    }

    /**
     * Get the name of the role to handle
     *
     * @return ?string Narrower than the inherited contract, as this form
     *   accepts string identifiers only. Null only in
     *   {@see RepositoryMode::Insert} mode, where none is required
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Apply the requested mode on the repository and update inheriting roles
     *
     * After applying the mode, updates the `parent` field on all roles that
     * reference this role by name, reflecting a rename or removal. Only applies
     * in {@see RepositoryMode::Update} and {@see RepositoryMode::Delete} mode.
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        parent::onSuccess();

        $newName = $this->getValue('name');
        $isUpdateOrDeleteMode = in_array($this->mode, [RepositoryMode::Update, RepositoryMode::Delete]);
        if ($isUpdateOrDeleteMode && $this->getIdentifier() !== $newName) {
            // Update/remove the parent reference on roles that inherit from the
            // renamed or removed one.
            $repo = $this->getUpdatableRepository();
            $repo->update(
                $repo->getBaseTable(),
                ['parent' => $newName],
                Filter::where('parent', $this->getIdentifier()),
            );
        }
    }

    /**
     * Fetch and transform the stored role into form-ready values
     *
     * In addition to fetching the raw entry, maps stored `permissions` and
     * `refusals` CSV strings to fieldset checkbox values (`'y'`), migrates
     * legacy permissions, and copies restriction values into their respective
     * fieldset entries.
     *
     * @return object|false The transformed role as a plain object, or false if
     *   no matching entry exists
     */
    protected function fetchEntry(): object|false
    {
        $role = parent::fetchEntry();
        if ($role === false) {
            return false;
        }

        $hasEveryPermission = $role->permissions && preg_match('~(?>^|,)\*(?>$|,)~', $role->permissions);
        $values = [
            'parent'            => $role->parent,
            'name'              => $role->name,
            'users'             => $role->users,
            'groups'            => $role->groups,
            'unrestricted'      => $role->unrestricted,
            self::WILDCARD_NAME => $hasEveryPermission ? 'y' : 'n',
        ];

        if (! empty($role->permissions) || ! empty($role->refusals)) {
            $permissions = StringHelper::trimSplit($role->permissions);
            $refusals = StringHelper::trimSplit($role->refusals);

            [$permissions, $newRefusals] = AdmissionLoader::migrateLegacyPermissions($permissions);
            if (! empty($newRefusals)) {
                array_push($refusals, ...$newRefusals);
            }

            foreach ($this->providedPermissions as $moduleName => $permissionList) {
                $fieldsetName = $moduleName . static::FIELDSET_SUFFIX;
                $hasFullPerm = false;
                foreach ($permissionList as $name => $spec) {
                    if (in_array($name, $permissions, true)) {
                        $values[$fieldsetName][$this->convertToElementName($name)] = 'y';

                        if (isset($spec['isFullPerm'])) {
                            $hasFullPerm = true;
                        }
                    }

                    if (in_array($name, $refusals, true)) {
                        $values[$fieldsetName][$this->convertToElementName(self::DENY_PREFIX . $name)] = 'y';
                    }
                }

                if ($hasFullPerm) {
                    $modulePermission = Manager::MODULE_PERMISSION_NS . $moduleName;
                    unset($values[$fieldsetName][$this->convertToElementName($modulePermission)]);
                }
            }
        }

        foreach ($this->providedRestrictions as $moduleName => $restrictionList) {
            foreach (array_keys($restrictionList) as $name) {
                if (isset($role->$name)) {
                    $values[$moduleName . static::FIELDSET_SUFFIX][$this->convertToElementName($name)] = $role->$name;
                }
            }
        }

        return (object) $values;
    }

    /**
     * Get the submitted role values in repository format
     *
     * Transforms the raw fieldset values returned by the parent into a flat
     * structure suitable for persisting via the repository: collapses permission
     * checkboxes into a comma-separated `permissions` string, deny checkboxes into
     * `refusals`, lifts restriction values to top-level keys, and auto-includes
     * the general module access permission (`module/<name>`) whenever the full
     * module permission (`<name>/*`) is set.
     *
     * @return array<string, ?string> Both `permissions` and `refusals` are
     *   `null` when empty. In {@see RepositoryMode::Delete} mode the parent's
     *   values are returned unchanged, since no role elements are assembled
     */
    public function getValues(): array
    {
        $values = parent::getValues();
        if ($this->mode === RepositoryMode::Delete) {
            return $values;
        }

        foreach ($this->providedRestrictions as $moduleName => $restrictionList) {
            $fieldsetKey = $moduleName . static::FIELDSET_SUFFIX;
            foreach (array_keys($restrictionList) as $name) {
                $elementName = $this->convertToElementName($name);
                if (array_key_exists($elementName, $values[$fieldsetKey])) {
                    $values[$name] = $values[$fieldsetKey][$elementName];
                    unset($values[$fieldsetKey][$elementName]);
                }
            }
        }

        $permissions = [];
        if (isset($values[self::WILDCARD_NAME]) && $values[self::WILDCARD_NAME] === 'y') {
            $permissions[] = '*';
        }

        $refusals = [];
        foreach ($this->providedPermissions as $moduleName => $permissionList) {
            $fieldsetKey = $moduleName . static::FIELDSET_SUFFIX;
            $hasFullPerm = false;
            foreach ($permissionList as $name => $spec) {
                $elementName = $this->convertToElementName($name);
                if (isset($values[$fieldsetKey][$elementName]) && $values[$fieldsetKey][$elementName] === 'y') {
                    $permissions[] = $name;

                    if (isset($spec['isFullPerm'])) {
                        $hasFullPerm = true;
                    }
                }

                $denyName = $this->convertToElementName(self::DENY_PREFIX . $name);
                if (isset($values[$fieldsetKey][$denyName]) && $values[$fieldsetKey][$denyName] === 'y') {
                    $refusals[] = $name;
                }

                unset($values[$fieldsetKey][$elementName], $values[$fieldsetKey][$denyName]);
            }

            $modulePermission = Manager::MODULE_PERMISSION_NS . $moduleName;
            if ($hasFullPerm && ! in_array($modulePermission, $permissions, true)) {
                $permissions[] = $modulePermission;
            }

            unset($values[$fieldsetKey]);
        }

        unset($values[self::WILDCARD_NAME]);
        $values['refusals']    = $refusals ? implode(',', $refusals) : null;
        $values['permissions'] = $permissions ? implode(',', $permissions) : null;

        return $values;
    }

    /**
     * Convert a name to a form element name
     *
     * Removes every character that is not valid in an element name, keeping only
     * letters, digits, underscores and high bytes.
     *
     * @param string $value Permission or restriction name to convert
     *
     * @return string
     */
    protected function convertToElementName(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '', $value);
    }

    /**
     * Sort the given permissions by name in place
     *
     * Full module access sorts first, general module access second, and the
     * remaining permissions follow, natural-sorted by their first differing
     * path segment.
     *
     * @param array<string, array<string, mixed>> $permissions Permission specs keyed by name, sorted in place
     *
     * @return void
     */
    protected function sortPermissions(array &$permissions): void
    {
        uksort($permissions, function ($a, $b) use ($permissions): int {
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

    /**
     * Collect the names of roles that may be set as parent
     *
     * Excludes the role currently being handled and all of its descendants, so
     * that selecting a parent cannot introduce an inheritance loop.
     *
     * @return array<string, string> Role names, each mapped to itself
     */
    protected function collectRoles(): array
    {
        // Function to get all connected children. Used to avoid reference loops
        $getChildren = function ($name, $children = []) use (&$getChildren): array {
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

    /**
     * Collect permissions and restrictions provided by Icinga Web and modules
     *
     * Returns a two-element list: index 0 holds the permissions, index 1 the
     * restrictions. Both are nested the same way:
     *
     * module name => privilege name => spec
     *
     * @return array{
     *     0: array<string, array<string, array{
     *         isFullPerm?: true,
     *         isUsagePerm?: true,
     *         label?: string,
     *         description?: string
     *     }>>,
     *     1: array<string, array<string, array{
     *         label?: string,
     *         description?: string
     *     }>>
     * }
     */
    public static function collectProvidedPrivileges(): array
    {
        $providedPermissions['application'] = [
            'application/announcements'    => ['description' => t('Allow to manage announcements')],
            'application/log'              => ['description' => t('Allow to view the application log')],
            'config/*'                     => ['description' => t('Allow full config access')],
            'config/general'               => ['description' => t('Allow to adjust the general configuration')],
            'config/security'              => ['description' => t('Allow to adjust the security configuration')],
            'config/modules'               => ['description' => t('Allow to enable/disable and configure modules')],
            'config/resources'             => ['description' => t('Allow to manage resources')],
            'config/navigation'            => ['description' => t('Allow to view and adjust shared navigation items')],
            'config/access-control/*'      => ['description' => t('Allow to fully manage access-control')],
            'config/access-control/users'  => ['description' => t('Allow to manage user accounts')],
            'config/access-control/groups' => ['description' => t('Allow to manage user groups')],
            'config/access-control/roles'  => ['description' => t('Allow to manage roles')],
            'user/*'                       => ['description' => t('Allow all account related functionalities')],
            'user/password-change'         => ['description' => t('Allow password changes in the account preferences')],
            'user/application/stacktraces' => [
                'description' => t('Allow to adjust in the preferences whether to show stacktraces'),
            ],
            'user/share/navigation'        => ['description' => t('Allow to share navigation items')],
            'application/sessions'         => ['description' => t('Allow to manage user sessions')],
            'application/migrations'       => ['description' => t('Allow to apply pending application migrations')],
        ];

        $providedRestrictions['application'] = [
            'application/share/users'  => [
                'description' => t('Restrict which users this role can share items and information with'),
            ],
            'application/share/groups' => [
                'description' => t('Restrict which groups this role can share items and information with'),
            ],
        ];

        $mm = Icinga::app()->getModuleManager();
        foreach ($mm->listInstalledModules() as $moduleName) {
            $modulePermission = Manager::MODULE_PERMISSION_NS . $moduleName;
            $providedPermissions[$moduleName][$modulePermission] = [
                'isUsagePerm' => true,
                'label'       => t('General Module Access'),
                'description' => sprintf(t('Allow access to module %s'), $moduleName),
            ];

            $module = $mm->getModule($moduleName, false);
            $permissions = $module->getProvidedPermissions();

            $providedPermissions[$moduleName][$moduleName . '/*'] = [
                'isFullPerm' => true,
                'label'      => t('Full Module Access'),
            ];

            foreach ($permissions as $permission) {
                /** @var object $permission */
                $providedPermissions[$moduleName][$permission->name] = ['description' => $permission->description];
            }

            foreach ($module->getProvidedRestrictions() as $restriction) {
                $providedRestrictions[$moduleName][$restriction->name] = ['description' => $restriction->description];
            }
        }

        return [$providedPermissions, $providedRestrictions];
    }

    /**
     * Build label content for a permission or restriction name
     *
     * The name is split into path segments. The first segment is wrapped in
     * `<em>`. Each following segment is preceded by a slash and a zero-width
     * space so browsers can break the line at each slash, and the segment
     * text is wrapped in `<span class="no-wrap">`.
     *
     * @param string $name Permission or restriction name
     *
     * @return HtmlDocument
     */
    protected function buildPrivilegeLabel(string $name): HtmlDocument
    {
        $segments = preg_split('~(/[^/]+)~', $name, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $label = new HtmlDocument();
        foreach ($segments as $segment) {
            if ($segment[0] === '/') {
                // Zero-width space after slash lets browsers break onto newlines
                $label->addHtml(
                    new Text('/' . "\u{200B}"),
                    new HtmlElement('span', Attributes::create(['class' => 'no-wrap']), new Text(substr($segment, 1)))
                );
            } else {
                $label->addHtml(new HtmlElement('em', null, new Text($segment)));
            }
        }

        return $label;
    }
}
