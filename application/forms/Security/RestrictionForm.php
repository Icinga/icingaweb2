<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Security;

use InvalidArgumentException;
use LogicException;
use Icinga\Application\Icinga;
use Icinga\Form\ConfigForm;

/**
 * Form for setting and removing user and group restrictions
 */
class RestrictionForm extends ConfigForm
{
    /**
     * Provided restrictions by currently loaded modules
     *
     * @var array
     */
    protected $providedRestrictions = array();

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Form::init() For the method documentation.
     */
    public function init()
    {
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            foreach ($module->getProvidedRestrictions() as $restriction) {
                /** @var object $restriction */
                $this->providedRestrictions[$restriction->name] = $restriction->name . ': ' . $restriction->description;
            }
        }
        $this->setSubmitLabel(t('Set Restriction'));
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
                    'label'         => t('Name'),
                    'description'   => t('The name of the restriction')
                ),
            ),
            array(
                'textarea',
                'users',
                array(
                    'label'         => t('Users'),
                    'description'   => t('Comma-separated list of users who are subject to the restriction')
                ),
            ),
            array(
                'textarea',
                'groups',
                array(
                    'label'         => t('Groups'),
                    'description'   => t('Comma-separated list of groups that are subject to the restriction')
                ),
            ),
            array(
                'select',
                'restriction_name',
                array(
                    'required'      => true,
                    'label'         => t('Restriction Name'),
                    'description'   => t('The restriction to set'),
                    'multiOptions'  => $this->providedRestrictions
                )
            ),
            array(
                'text',
                'restriction_definition',
                array(
                    'required'      => true,
                    'label'         => t('Restriction'),
                    'description'   => t('The restriction definition. Most likely a URL filter')
                ),
            ),
        ));
        return $this;
    }

    /**
     * Load a restriction
     *
     * @param   string  $name           The name of the restriction
     *
     * @return  $this
     *
     * @throws  LogicException          If the config is not set
     * @see     ConfigForm::setConfig() For setting the config.
     */
    public function load($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t load restriction \'%s\'. Config is not set', $name));
        }
        if (! isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t load restriction \'%s\'. Restriction does not exist'),
                $name
            ));
        }
        $restriction = $this->config->{$name}->toArray();
        $restriction['restriction_name'] = $restriction['name'];
        $restriction['name'] = $name;
        $restriction['restriction_definition'] = $restriction['restriction'];
        unset($restriction['restriction']);
        $this->populate($restriction);
        return $this;
    }

    /**
     * Add a restriction
     *
     * @param   string  $name               The name of the restriction
     * @param   array   $values
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the restriction to add already exists
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function add($name, array $values)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t add restriction \'%s\'. Config is not set', $name));
        }
        if (isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t add restriction \'%s\'. Restriction already exists'),
                $name
            ));
        }
        $this->config->{$name} = $values;
        return $this;
    }

    /**
     * Remove a restriction
     *
     * @param   string  $name               The name of the restriction
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the restriction does not exist
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function remove($name)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t remove restriction \'%s\'. Config is not set', $name));
        }
        if (! isset($this->config->{$name})) {
            throw new InvalidArgumentException(sprintf(
                t('Can\'t remove restriction \'%s\'. Restriction does not exist'),
                $name
            ));
        }
        unset($this->config->{$name});
        return $this;
    }

    /**
     * Update a restriction
     *
     * @param   string  $name               The possibly new name of the restriction
     * @param   array   $values
     * @param   string  $oldName            The name of the restriction to update
     *
     * @return  $this
     *
     * @throws  LogicException              If the config is not set
     * @throws  InvalidArgumentException    If the restriction to update does not exist
     * @see     ConfigForm::setConfig()     For setting the config.
     */
    public function update($name, array $values, $oldName)
    {
        if (! isset($this->config)) {
            throw new LogicException(sprintf('Can\'t update restriction \'%s\'. Config is not set', $name));
        }
        if ($name !== $oldName) {
            // The restriction got a new name
            $this->remove($oldName);
            $this->add($name, $values);
        } else {
            if (! isset($this->config->{$name})) {
                throw new InvalidArgumentException(sprintf(
                    t('Can\'t update restriction \'%s\'. Restriction does not exist'),
                    $name
                ));
            }
            $this->config->{$name} = $values;
        }
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::getValues() For the method documentation.
     */
    public function getValues($suppressArrayNotation = false)
    {
        return array(
            'users'         => $this->getElement('users')->getValue(),
            'groups'        => $this->getElement('groups')->getValue(),
            'name'          => $this->getElement('restriction_name')->getValue(),
            'restriction'   => $this->getElement('restriction_definition')->getValue()
        );
    }
}
