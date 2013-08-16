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
use \Icinga\Form\Config\Authentication\DbBackendForm;
use \Icinga\Form\Config\Authentication\LdapBackendForm;

use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Web\Form\Decorator\ConditionalHidden;
use \Zend_Config;
use \Zend_Form_Element_Text;
use \Zend_Form_Element_Select;
use \Zend_Form_Element_Button;

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
        foreach ($form->getElements() as $name => $element) {
            $this->addElement($element, $name);
        }

        $this->backendForms[] = $form;
    }





    public function addPriorityButtons($name, $order = array())
    {
        $formEls = array();
        $priorities = array(
            "up"    =>  join(',', self::moveElementUp($name, $order)),
            "down"  =>  join(',', self::moveElementDown($name, $order))
        );
        if ($priorities["up"] != join(',', $order)) {
            $this->addElement(
                'button',
                'priority' . $name . '_up',
                array(
                    'name'  => 'priority',
                    'label' => 'Move up in authentication order',
                    'value' =>  $priorities["up"],
                    'type'  => 'submit'
                )
            );
            $formEls[] = 'priority' . $name . '_up';
        }
        if ($priorities["down"] != join(',', $order)) {
            $this->addElement(
                'button',
                'priority' . $name . '_down',
                array(
                    'name'  => 'priority',
                    'label' => 'Move down in authentication order',
                    'value' =>  $priorities["down"],
                    'type'  => 'submit'
                )
            );
            $formEls[] = 'priority' . $name . '_down';
        }

        return $formEls;
    }


    public function populate(array $values)
    {
        $last_priority = $this->getValue('current_priority');
        parent::populate($values);
        $this->getElement('current_priority')->setValue($last_priority);

    }

    private function getAuthenticationOrder ()
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


    private function isMarkedForDeletion($backendName)
    {
        return intval($this->getRequest()->getParam('backend_' . $backendName . '_remove', 0)) === 1;
    }

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
        $this->setSubmitLabel('Save changes');
    }

    public function getConfig()
    {
        $result = array();
        foreach ($this->backendForms as $name) {

            $name->populate($this->getRequest()->getParams());
            $result += $name->getConfig();

        }
        return $result;
    }

    private function enableConditionalDecorator()
    {
        foreach ($this->getElements() as $element) {
            $element->addDecorator(new ConditionalHidden());
        }
    }

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
