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

namespace Icinga\Module\Monitoring\Form\Config\Backend;

use \Zend_Config;
use \Icinga\Web\Form;
use \Icinga\Application\Icinga;
use \Icinga\Application\DbAdapterFactory;

/**
 * Form for modifying a monitoring backend
 */
class EditBackendForm extends Form
{
    /**
     * Database resources to use instead of the one's from DBAdapterFactory (used for testing)
     *
     * @var array
     */
    private $resources;

    /**
     * The Backend configuration to use for populating the form
     *
     * @var Zend_Config
     */
    private $backend;

    /**
     * Mapping from form fields to configuration fields
     *
     * @var array
     */
    private $propertyMapping = array(
        'livestatus' => array(
            'backend_livestatus_socket' => 'socket'
        ),
        'ido' => array(
            'backend_ido_resource' => 'resource'
        ),
        'statusdat' => array(
            'backend_statusdat_statusfile' => 'status_file',
            'backend_statusdat_objectfile' => 'object_file'
        )
    );

    /**
     * Set the configuration to be used for initial population of the form
     *
     * @param Zend_Form $config
     */
    public function setBackendConfiguration($config)
    {
        $this->backend = $config;
    }

    /**
     * Set a custom array of resources to be used in this form instead of the ones from DbAdapterFactory
     * (used for testing)
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * Return content of the resources.ini or previously set resources for displaying in the database selection field
     *
     * @return array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            return DbAdapterFactory::getResources();
        } else {
            return $this->resources;
        }
    }

    /**
     * Return a list of all database resource ready to be used as the multiOptions
     * attribute in a Zend_Form_Element_Select object
     *
     * @return array
     */
    private function getDatabaseResources()
    {
        $backends = array();
        foreach ($this->getResources() as $resname => $resource) {
            if ($resource['type'] !== 'db') {
                continue;
            }
            $backends[$resname] = $resname;
        }
        return $backends;
    }


    /**
     * Add form elements used for setting IDO backend parameters
     */
    private function addIdoBackendForm()
    {
        $this->addElement(
            'select',
            'backend_ido_resource',
            array(
                'label'         => 'IDO Connection',
                'value'         =>  $this->backend->resource,
                'required'      =>  true,
                'multiOptions'  =>  $this->getDatabaseResources(),
                'helptext'      =>  'The database to use for querying monitoring data',
            )
        );
    }

    /**
     * Add form elements used for setting status.dat backend parameters
     */
    private function addStatusDatForm()
    {
        $this->addElement(
            'text',
            'backend_statusdat_statusfile',
            array (
                'label'     =>  'Status.dat File',
                'value'     =>  $this->backend->status_file,
                'required'  =>  true,
                'helptext'  =>  'Location of your icinga status.dat file'
            )
        );
        $this->addElement(
            'text',
            'backend_statusdat_objectfile',
            array (
                'label'     => 'Objects.cache File',
                'value'     =>  $this->backend->status_file,
                'required'  =>  true,
                'helptext'  =>  'Location of your icinga objects.cache file'
            )
        );
    }

    /**
     * Add form elements used for setting Livestatus parameters
     */
    private function addLivestatusForm()
    {
        $this->addElement(
            'text',
            'backend_livestatus_socket',
            array(
                'label'     => 'Livestatus Socket Location',
                'required'  =>  true,
                'helptext'  =>  'The path to your livestatus socket used for querying monitoring data',
                'value'     =>  $this->backend->socket,
            )
        );
    }

    /**
     * Add a checkbox to disable this backends
     */
    private function addDisableButton()
    {
        $this->addElement(
            'checkbox',
            'backend_disable',
            array(
                'label'     => 'Disable This Backend',
                'required'  =>  true,
                'value'     =>  $this->backend->disabled
            )
        );
    }

    /**
     * Add a select box for choosing the type to use for this backend
     */
    private function addTypeSelectionBox()
    {
        $this->addElement(
            'select',
            'backend_type',
            array(
                'label'         =>  'Backend Type',
                'value'         =>  $this->backend->type,
                'required'      =>  true,
                'helptext'      =>  'The data source used for retrieving monitoring information',
                'multiOptions'  =>  array(
                    'ido'           =>  'IDO Backend',
                    'statusdat'     =>  'Status.dat',
                    'livestatus'    =>  'Livestatus'
                )
            )
        );
        $this->enableAutoSubmit(array('backend_type'));
    }

    /**
     * Create this form
     *
     * @see Icinga\Web\Form::create()
     */
    public function create()
    {
        $this->addTypeSelectionBox();
        switch ($this->getRequest()->getParam('backend_type', $this->backend->type)) {
            case 'ido':
                $this->addIdoBackendForm();
                break;
            case 'statusdat':
                $this->addStatusDatForm();
                break;
            case 'livestatus':
                $this->addLivestatusForm();
                break;
            default:
                $this->removeElement('backend_type');
                $this->addNote('Unknown Backend Type "' . $this->backend->type. '"');
                return;
        }
        $this->addDisableButton();
        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return Zend_Config The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $type = $values['backend_type'];

        $map = $this->propertyMapping[$type];
        $result = array(
            'type'      => $type,
            'disabled'  => $values['backend_disable']
        );
        foreach ($map as $formKey => $mappedKey) {
            $result[$mappedKey] = $values[$formKey];
        }
        return new Zend_Config($result);
    }
}
