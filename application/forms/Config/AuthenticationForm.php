<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\Icinga;
use \Icinga\Application\Logger;
use \Icinga\Application\DbAdapterFactory;

use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Web\Form\Decorator\ConditionalHidden;
use \Zend_Config;

/**
 * Form for modifying the authentication provider and order.
 *
 * This is a composite form from one or more forms under the Authentication folder
 */
class AuthenticationForm extends Form
{
    /**
     * The configuration to use for populating this form
     *
     * @var IcingaConfig
     */
    private $config = null;

    /**
     * The resources to use instead of the factory provided ones (use for testing)
     *
     * @var null
     */
    private $resources = null;

    /**
     * An array containing all provider subforms currently displayed
     *
     * @var array
     */
    private $backendForms = array();


    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     * (used for testing)
     *
     * @param array $resources              The resources to use for populating the db selection field
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Set the configuration to be used for this form
     *
     * @param IcingaConfig $cfg
     */
    public function setConfiguration($cfg)
    {
        $this->config = $cfg;
    }

    /**
     *  Add a hint to remove the backend identified by $name
     *
     *  The button will have the name "backend_$name_remove"
     *
     *  @param string $name                 The backend to add this button for
     *
     *  @return string                      The id of the added button
     */
    private function addRemoveHint($name)
    {
        $this->addElement(
            'checkbox',
            'backend_' . $name . '_remove',
            array(
                'name'          => 'backend_' . $name . '_remove',
                'label'         => 'Remove this authentication provider',
                'value'         => $name,
                'checked'       => $this->isMarkedForDeletion($name)
            )
        );
        $this->enableAutoSubmit(array('backend_' . $name . '_remove'));
        return 'backend_' . $name . '_remove';
    }

    /**
     *  Add the form for the provider identified by $name, with the configuration $backend
     *
     *  Supported backends are backends with a form found under \Icinga\Form\Config\Authentication.
     *  The backend name ist the (uppercase first) prefix with 'BackendForm' as the suffix.
     *
     *  Originally it was intended to add the provider as a subform. As this didn't really work with
     *  the Zend validation logic (maybe our own validation logic breaks it), we now create the form, but add
     *  all elements to this form explicitly.
     *
     *  @param string       $name           The name of the backend to add
     *  @param Zend_Config  $backend        The configuration of the backend
     */
    private function addProviderForm($name, $backend)
    {
        $type = ucfirst(strtolower($backend->get('backend')));
        $formClass = '\Icinga\Form\Config\Authentication\\' . $type . 'BackendForm';
        if (!class_exists($formClass)) {
            Logger::error('Unsupported backend found in authentication configuration: ' . $backend->get('backend'));
            return;
        }

        $form = new $formClass();
        $form->setBackendName($name);
        $form->setBackend($backend);

        if ($this->resources) {
            $form->setResources($this->resources);
        }

        // It would be nice to directly set the form via
        // this->setForm, but Zend doesn't handle form validation
        // properly if doing so.
        $form->create();
        foreach ($form->getElements() as $elName => $element) {
            if ($elName === 'backend_' . $this->filterName($name) . '_name') {
                continue;
            }
            $this->addElement($element, $elName);
        }
        $this->backendForms[] = $form;
    }

    /**
     * Add the buttons for modifying authentication priorities
     *
     * @param string    $name           The name of the backend to add the buttons for
     * @param array     $order          The current order which will be used to determine the changed order
     *
     * @return array                    An array containing the newly added form element ids as strings
     */
    public function addPriorityButtons($name, $order = array())
    {
        $formEls = array();
        $priorities = array(
            'up'    =>  join(',', self::moveElementUp($name, $order)),
            'down'  =>  join(',', self::moveElementDown($name, $order))
        );
        if ($priorities['up'] != join(',', $order)) {
            $this->addElement(
                'button',
                'priority' . $name . '_up',
                array(
                    'name'  => 'priority',
                    'label' => 'Move up in authentication order',
                    'value' =>  $priorities['up'],
                    'type'  => 'submit'
                )
            );
            $formEls[] = 'priority' . $name . '_up';
        }
        if ($priorities['down'] != join(',', $order)) {
            $this->addElement(
                'button',
                'priority' . $name . '_down',
                array(
                    'name'  => 'priority',
                    'label' => 'Move down in authentication order',
                    'value' =>  $priorities['down'],
                    'type'  => 'submit'
                )
            );
            $formEls[] = 'priority' . $name . '_down';
        }

        return $formEls;
    }

    /**
     * Overwrite for Zend_Form::populate in order to preserve the modified priority of the backends
     *
     * @param array $values                 The values to populate the form with
     *
     * @return void|\Zend_Form
     * @see Zend_Form::populate
     */
    public function populate(array $values)
    {
        $last_priority = $this->getValue('current_priority');
        parent::populate($values);
        $this->getElement('current_priority')->setValue($last_priority);

    }

    /**
     * Return an array containing all authentication providers in the order they should be used
     *
     * @return array            An array containing the identifiers (section names) of the authentication backend in
     *                          the order they should be persisted
     */
    private function getAuthenticationOrder()
    {
        $request = $this->getRequest();
        $order = $request->getParam(
            'priority',
            $request->getParam('current_priority', null)
        );

        if ($order === null) {
            $order = array_keys($this->config->toArray());
        } else {
            $order = explode(',', $order);
        }

        return $order;
    }

    /**
     * Return true if the backend should be deleted when the changes are persisted
     *
     * @param string $backendName              The name of the backend to check for being in a 'delete' state
     *
     * @return bool                            Whether this backend will be deleted on save
     */
    private function isMarkedForDeletion($backendName)
    {
        return intval($this->getRequest()->getParam('backend_' . $backendName . '_remove', 0)) === 1;
    }

    /**
     * Add persistent values to the form in hidden fields
     *
     * Currently this adds the 'current_priority' field to persist priority modifications. This prevents changes in the
     * authentication order to be lost as soon as other changes are submitted (like marking a backend for deletion)
     */
    private function addPersistentState()
    {
        $this->addElement(
            'hidden',
            'current_priority',
            array(
                'name'  =>  'current_priority',
                'value' =>  join(',', $this->getAuthenticationOrder())
            )
        );
    }

    /**
     * Create the authentication provider configuration form
     *
     * @see IcingaForm::create()
     */
    public function create()
    {
        $order = $this->getAuthenticationOrder();

        foreach ($order as $name) {
            $this->addElement(
                new Note(
                    array(
                        'escape' => false,
                        'name'  => 'title_backend_' . $name,
                        'value' => '<h4>Backend ' . $name . '</h4>'
                    )
                )
            );
            $this->addRemoveHint($this->filterName($name));
            $backend = $this->config->get($name, null);
            if ($backend === null) {
                continue;
            }
            if (!$this->isMarkedForDeletion($this->filterName($name))) {
                $this->addProviderForm($name, $backend);
                $this->addPriorityButtons($name, $order);
            }
        }

        $this->addPersistentState();
        $this->enableConditionalDecorator();
        $this->setSubmitLabel('Save Changes');
    }

    /**
     * Return the configuration state defined by this form
     *
     * @return array
     */
    public function getConfig()
    {
        $result = array();
        foreach ($this->backendForms as $name) {

            $name->populate($this->getRequest()->getParams());
            $result += $name->getConfig();

        }
        return $result;
    }

    /**
     *  Enable the "ConditionalHidden" Decorator for all elements in this form
     *
     *  @see ConditionalHidden
     */
    private function enableConditionalDecorator()
    {
        foreach ($this->getElements() as $element) {
            $element->addDecorator(new ConditionalHidden());
        }
    }

    /**
     * Static helper for moving an element in an array one slot up, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementUp('third', $array); // returns ['first', 'third', 'second']
     * </pre>
     *
     * @param   string    $key              The key to bubble up one slot
     * @param   array     $array            The array to work with
     *
     * @return  array                       The modified array
     */
    private static function moveElementUp($key, array $array)
    {
        $swap = null;
        for ($i=0; $i<count($array)-1; $i++) {
            if ($array[$i+1] !== $key) {
                continue;
            }
            $swap = $array[$i];
            $array[$i] = $array[$i+1];
            $array[$i+1] = $swap;
            return $array;
        }
        return $array;
    }

    /**
     * Static helper for moving an element in an array one slot up, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementDown('first', $array); // returns ['second', 'first', 'third']
     * </pre>
     *
     * @param   string    $key              The key to bubble up one slot
     * @param   array     $array            The array to work with
     *
     * @return  array                       The modified array
     */
    private static function moveElementDown($key, array $array)
    {
        $swap = null;
        for ($i=0; $i<count($array)-1; $i++) {
            if ($array[$i] !== $key) {
                continue;
            }
            $swap = $array[$i+1];
            $array[$i+1] = $array[$i];
            $array[$i] = $swap;
            return $array;
        }
        return $array;
    }
}
