<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\User;
use Icinga\Web\Form;

/**
 * Form for managing navigation items
 */
class NavigationConfigForm extends ConfigForm
{
    /**
     * The secondary configuration to write
     *
     * This is always the reduced configuration and is only written to
     * disk once the main configuration has been successfully written.
     *
     * @var Config
     */
    protected $secondaryConfig;

    /**
     * The navigation item to load when displaying the form for the first time
     *
     * @var string
     */
    protected $itemToLoad;

    /**
     * The user for whom to manage navigation items
     *
     * @var User
     */
    protected $user;

    /**
     * The user's navigation configuration
     *
     * @var Config
     */
    protected $userConfig;

    /**
     * The shared navigation configuration
     *
     * @var Config
     */
    protected $shareConfig;

    /**
     * The available navigation item types
     *
     * @var array
     */
    protected $itemTypes;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_navigation');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Set the user for whom to manage navigation items
     *
     * @param   User    $user
     *
     * @return  $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Return the user for whom to manage navigation items
     *
     * @return  User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user's navigation configuration
     *
     * @param   Config  $config
     *
     * @return  $this
     */
    public function setUserConfig(Config $config)
    {
        $this->userConfig = $config;
        return $this;
    }

    /**
     * Return the user's navigation configuration
     *
     * @return  Config
     */
    public function getUserConfig()
    {
        if ($this->userConfig === null) {
            $this->userConfig = $this->getUser()->loadNavigationConfig();
        }

        return $this->userConfig;
    }

    /**
     * Set the shared navigation configuration
     *
     * @param   Config  $config
     *
     * @return  $this
     */
    public function setShareConfig(Config $config)
    {
        $this->shareConfig = $config;
        return $this;
    }

    /**
     * Return the shared navigation configuration
     *
     * @return  Config
     */
    public function getShareConfig()
    {
        return $this->shareConfig;
    }

    /**
     * Set the available navigation item types
     *
     * @param   array   $itemTypes
     *
     * @return  $this
     */
    public function setItemTypes(array $itemTypes)
    {
        $this->itemTypes = $itemTypes;
        return $this;
    }

    /**
     * Return the available navigation item types
     *
     * @return  array
     */
    public function getItemTypes()
    {
        return $this->itemTypes ?: array();
    }

    /**
     * Populate the form with the given navigation item's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no navigation item with the given name is found
     */
    public function load($name)
    {
        if ($this->getConfigForItem($name) === null) {
            throw new NotFoundError('No navigation item called "%s" found', $name);
        }

        $this->itemToLoad = $name;
        return $this;
    }

    /**
     * Add a new navigation item
     *
     * The navigation item to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a navigation item name
     * @throws  IcingaException             In case a navigation item with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $config = $this->getUserConfig();
        if ((isset($data['users']) && $data['users']) || (isset($data['groups']) && $data['groups'])) {
            if ($this->getUser()->can('application/share/navigation')) {
                $data['owner'] = $this->getUser()->getUsername();
                $config = $this->getShareConfig();
            } else {
                unset($data['users']);
                unset($data['groups']);
            }
        }

        $itemName = $data['name'];
        if ($config->hasSection($itemName)) {
            throw new IcingaException(
                $this->translate('A navigation item with the name "%s" does already exist'),
                $itemName
            );
        }

        unset($data['name']);
        $config->setSection($itemName, $data);
        $this->setIniConfig($config);
        return $this;
    }

    /**
     * Edit a navigation item
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no navigation item with the given name is found
     */
    public function edit($name, array $data)
    {
        $config = $this->getConfigForItem($name);
        if ($config === null) {
            throw new NotFoundError('No navigation item called "%s" found', $name);
        }

        $itemConfig = $config->getSection($name);

        if ($this->hasBeenShared($name)) {
            if ((! isset($data['users']) || !$data['users']) && (! isset($data['groups']) || !$data['groups'])) {
                // It is shared but shouldn't anymore
                $config = $this->unshare($name)->config; // unshare() calls setIniConfig()
            }
        } elseif ((isset($data['users']) && $data['users']) || (isset($data['groups']) && $data['groups'])) {
            if ($this->getUser()->can('application/share/navigation')) {
                // It is not shared yet but should be
                $config->removeSection($name);
                $this->secondaryConfig = $config;
                $config = $this->getShareConfig();
                $data['owner'] = $this->getUser()->getUsername();
            } else {
                unset($data['users']);
                unset($data['groups']);
            }
        }

        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $itemConfig->merge($data);
        foreach ($itemConfig->toArray() as $k => $v) {
            if ($v === null) {
                unset($itemConfig->$k);
            }
        }

        $config->setSection($name, $itemConfig);
        $this->setIniConfig($config);
        return $this;
    }

    /**
     * Remove a navigation item
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function delete($name)
    {
        $config = $this->getConfigForItem($name);
        if ($config === null) {
            throw new NotFoundError('No navigation item called "%s" found', $name);
        }

        $config->removeSection($name);
        $this->setIniConfig($config);
        return $this;
    }

    /**
     * Unshare the given navigation item
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no navigation item with the given name is found
     */
    public function unshare($name)
    {
        $config = $this->getShareConfig();
        if (! $config->hasSection($name)) {
            throw new NotFoundError('No navigation item called "%s" found', $name);
        }

        $itemConfig = $config->getSection($name);
        $config->removeSection($name);
        $this->secondaryConfig = $config;

        if (! $itemConfig->owner || $itemConfig->owner === $this->getUser()->getUsername()) {
            $config = $this->getUserConfig();
        } else {
            $owner = new User($itemConfig->owner);
            $config = $owner->loadNavigationConfig();
        }

        unset($itemConfig->owner);
        unset($itemConfig->users);
        unset($itemConfig->groups);

        $config->setSection($name, $itemConfig);
        $this->setIniConfig($config);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $itemTypes = $this->getItemTypes();
        $itemType = isset($formData['type']) ? $formData['type'] : key($itemTypes);

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Name'),
                'description'   => $this->translate(
                    'The name of this navigation item that is used to differentiate it from others'
                )
            )
        );

        if ($this->getUser()->can('application/share/navigation')) {
            $checked = isset($formData['shared']) ? null : (isset($formData['users']) || isset($formData['groups']));

            $this->addElement(
                'checkbox',
                'shared',
                array(
                    'autosubmit'    => true,
                    'ignore'        => true,
                    'value'         => $checked,
                    'label'         => $this->translate('Shared'),
                    'description'   => $this->translate('Tick this box to share this item with others')
                )
            );

            if ($checked || (isset($formData['shared']) && $formData['shared'])) {
                $this->addElement(
                    'text',
                    'users',
                    array(
                        'label'         => $this->translate('Users'),
                        'description'   => $this->translate(
                            'Comma separated list of usernames to share this item with'
                        )
                    )
                );
                $this->addElement(
                    'text',
                    'groups',
                    array(
                        'label'         => $this->translate('Groups'),
                        'description'   => $this->translate(
                            'Comma separated list of group names to share this item with'
                        )
                    )
                );
            }
        }

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Type'),
                'description'   => $this->translate('The type of this navigation item'),
                'multiOptions'  => $itemTypes
            )
        );

        $this->addSubForm($this->getItemForm($itemType)->create($formData), 'item_form');
    }

    /**
     * Populate the configuration of the navigation item to load
     */
    public function onRequest()
    {
        if ($this->itemToLoad) {
            $data = $this->getConfigForItem($this->itemToLoad)->getSection($this->itemToLoad)->toArray();
            $data['name'] = $this->itemToLoad;
            $this->populate($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        $valid = true;
        if (isset($formData['users']) && $formData['users']) {
            $parsedUserRestrictions = array();
            foreach (Auth::getInstance()->getRestrictions('application/share/users') as $userRestriction) {
                $parsedUserRestrictions[] = array_map('trim', explode(',', $userRestriction));
            }

            if (! empty($parsedUserRestrictions)) {
                $desiredUsers = array_map('trim', explode(',', $formData['users']));
                array_unshift($parsedUserRestrictions, $desiredUsers);
                $forbiddenUsers = call_user_func_array('array_diff', $parsedUserRestrictions);
                if (! empty($forbiddenUsers)) {
                    $valid = false;
                    $this->getElement('users')->addError(
                        $this->translate(sprintf(
                            'You are not permitted to share this navigation item with the following users: %s',
                            implode(', ', $forbiddenUsers)
                        ))
                    );
                }
            }
        }

        if (isset($formData['groups']) && $formData['groups']) {
            $parsedGroupRestrictions = array();
            foreach (Auth::getInstance()->getRestrictions('application/share/groups') as $groupRestriction) {
                $parsedGroupRestrictions[] = array_map('trim', explode(',', $groupRestriction));
            }

            if (! empty($parsedGroupRestrictions)) {
                $desiredGroups = array_map('trim', explode(',', $formData['groups']));
                array_unshift($parsedGroupRestrictions, $desiredGroups);
                $forbiddenGroups = call_user_func_array('array_diff', $parsedGroupRestrictions);
                if (! empty($forbiddenGroups)) {
                    $valid = false;
                    $this->getElement('groups')->addError(
                        $this->translate(sprintf(
                            'You are not permitted to share this navigation item with the following groups: %s',
                            implode(', ', $forbiddenGroups)
                        ))
                    );
                }
            }
        }

        return $valid;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues();
        $values = array_merge($values, $values['item_form']);
        unset($values['item_form']);
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function writeConfig(Config $config)
    {
        parent::writeConfig($config);

        if ($this->secondaryConfig !== null) {
            $this->config = $this->secondaryConfig; // Causes the config being displayed to the user in case of an error
            parent::writeConfig($this->secondaryConfig);
        }
    }

    /**
     * Return the navigation configuration the given item is a part of
     *
     * @param   string  $name
     *
     * @return  Config|null     In case the item is not part of any configuration
     */
    protected function getConfigForItem($name)
    {
        if ($this->getUserConfig()->hasSection($name)) {
            return $this->getUserConfig();
        } elseif ($this->getShareConfig()->hasSection($name)) {
            if (
                $this->getShareConfig()->get($name, 'owner') === $this->getUser()->getUsername()
                || $this->getUser()->can('config/application/navigation')
            ) {
                return $this->getShareConfig();
            }
        }
    }

    /**
     * Return whether the given navigation item has been shared
     *
     * @param   string  $name
     *
     * @return  bool
     */
    protected function hasBeenShared($name)
    {
        return $this->getConfigForItem($name) === $this->getShareConfig();
    }

    /**
     * Return the form for the given type of navigation item
     *
     * @param   string  $type
     *
     * @return  Form
     */
    protected function getItemForm($type)
    {
        // TODO: Load form classes dynamically
        return new NavigationItemForm();
    }
}
