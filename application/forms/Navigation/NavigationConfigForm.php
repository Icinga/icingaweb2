<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Navigation;

use InvalidArgumentException;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\Web\Form;

/**
 * Form for managing navigation items
 */
class NavigationConfigForm extends ConfigForm
{
    /**
     * The default item types provided by Icinga Web 2
     *
     * @var array
     */
    protected $defaultItemTypes = array(
        'menu-item',
        'dashlet'
    );

    /**
     * The navigation item to load when displaying the form for the first time
     *
     * @var string
     */
    protected $itemToLoad;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_navigation');
        $this->setSubmitLabel($this->translate('Save Changes'));
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
        if (! $this->config->hasSection($name)) {
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

        $itemName = $data['name'];
        if ($this->config->hasSection($itemName)) {
            throw new IcingaException(
                $this->translate('A navigation item with the name "%s" does already exist'),
                $itemName
            );
        }

        unset($data['name']);
        $this->config->setSection($itemName, $data);
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
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No navigation item called "%s" found', $name);
        }

        $itemConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
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

        $this->config->setSection($name, $itemConfig);
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
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Unshare the given navigation item
     *
     * @param   string  $name
     *
     * @return  bool
     *
     * @throws  NotFoundError   In case no navigation item with the given name is found
     */
    public function unshare($name)
    {
        throw new NotFoundError($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $itemTypes = $this->listItemTypes();
        $itemType = isset($formData['type']) ? $formData['type'] : reset($itemTypes);

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Name'),
                'description'   => $this->translate(
                    'The name of this navigation item that is used to differentiate it from others'
                ),
                'validators'    => array(
                    array(
                        'Regex',
                        false,
                        array(
                            'pattern'  => '/^[^\\[\\]:]+$/',
                            'messages' => array(
                                'regexNotMatch' => $this->translate(
                                    'The name cannot contain \'[\', \']\' or \':\'.'
                                )
                            )
                        )
                    )
                )
            )
        );

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
            $data = $this->config->getSection($this->itemToLoad)->toArray();
            $data['name'] = $this->itemToLoad;
            $this->populate($data);
        }
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
     * Return a list of available item types
     *
     * @return  array
     */
    protected function listItemTypes()
    {
        $types = $this->defaultItemTypes;
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            $moduleItems = $module->getNavigationItems();
            if (! empty($moduleItems)) {
                $types = array_merge($types, $moduleItems);
            }
        }

        return $types;
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
