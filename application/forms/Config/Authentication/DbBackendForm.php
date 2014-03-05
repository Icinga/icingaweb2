<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Icinga\Authentication\Backend\DbUserBackend;
use \Zend_Config;

/**
 * Form class for adding/modifying database authentication backends
 */
class DbBackendForm extends BaseBackendForm
{
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
     * Create this form and add all required elements
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_modify_backend');
        $name = $this->filterName($this->getBackendName());

        $this->addElement(
            'text',
            'backend_' . $name . '_name',
            array(
                'required'      => true,
                'allowEmpty'    =>  false,
                'label'         => 'Backend Name',
                'helptext'      => 'The name of this authentication provider',
                'value'         => $this->getBackendName()
            )
        );

        $this->addElement(
            'select',
            'backend_' . $name . '_resource',
            array(
                'label'         =>  'Database Connection',
                'required'      =>  true,
                'allowEmpty'    =>  false,
                'helptext'      => 'The database connection to use for authenticating with this provider',
                'value'         =>  $this->getBackend()->get('resource'),
                'multiOptions'  =>  $this->getDatabaseResources()
            )
        );

        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'value'     => '1',
                'escape'    => false,
                'class'     => 'btn btn-cta btn-wide',
                'label'     => '<i class="icinga-icon-save"></i> Save Backend'
            )
        );
    }

    /**
     * Return the datatbase authentication backend configuration for this form
     *
     * @return  array
     *
     * @see     BaseBackendForm::getConfig()
     */
    public function getConfig()
    {
        $name = $this->getBackendName();
        $prefix = 'backend_' . $this->filterName($name) . '_';

        $section = $this->getValue($prefix . 'name');
        $cfg = array(
            'backend'   =>  'db',
            'target'    =>  'user',
            'resource'  =>  $this->getValue($prefix . 'resource'),
        );
        return array(
            $section => $cfg
        );
    }

    /**
     * Validate the current configuration by creating a backend and requesting the user count
     *
     * @return bool True when the backend is valid, false otherwise
     * @see BaseBackendForm::isValidAuthenticationBackend
     */
    public function isValidAuthenticationBackend()
    {
        // @TODO fix validation of authentication backends (AK #5712)
        return true;
        try {
            $name = $this->getBackendName();
            $dbBackend = new DbUserBackend(
                new Zend_Config(
                    array(
                        'backend'   =>  'db',
                        'target'    =>  'user',
                        'resource'  =>  $this->getValue('backend_' . $this->filterName($name) . '_resource'),
                    )
                )
            );
            $dbBackend->connect();
            if ($dbBackend->getUserCount() < 1) {
                $this->addErrorMessage("No users found under the specified database backend");
                return false;
            }
        } catch (\Exception $e) {
            $this->addErrorMessage("Using the specified backend failed: " . $e->getMessage());
            return false;
        } catch (\Zend_Db_Statement_Exception $e) {
            $this->addErrorMessage("Using the specified backend failed: " . $e->getMessage());
            return false;
        }
        return true;
    }
}
