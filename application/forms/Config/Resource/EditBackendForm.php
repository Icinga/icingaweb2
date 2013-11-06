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

namespace Icinga\Module\Monitoring\Form\Config\Backend;

use \Zend_Config;
use \Icinga\Web\Form;
use \Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;

/**
 * Form for modifying a monitoring backend
 */
class EditResourceForm extends Form
{
    /**
     * The currently edited resource.
     *
     * @var Zend_Config
     */
    private $resource;

    private $name;

    private function addDbForm()
    {

    }

    private function addStatusdatForm()
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

    private function addLdapForm()
    {
        $this->addElement(
            'text',
            'resource_' . $this->name . '_hostname',
            array(
                'label'         => 'LDAP Server Host',
                'allowEmpty'    =>  false,
                'value'         => $this->resource->get('hostname', 'localhost'),
                'helptext'      => 'The hostname or address of the LDAP server to use for authentication',
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'resource_' . $this->name . '_root_dn',
            array(
                'label'     => 'LDAP Root DN',
                'value'     => $this->resource->get('root_dn', 'ou=people,dc=icinga,dc=org'),
                'helptext'  => 'The path where users can be found on the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'resource_' . $this->name . '_bind_dn',
            array(
                'label'     => 'LDAP Bind DN',
                'value'     => $this->resource->get('bind_dn', 'cn=admin,cn=config'),
                'helptext'  => 'The user dn to use for querying the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'password',
            'resource_' . $this->name . '_bind_pw',
            array(
                'label'             => 'LDAP Bind Password',
                'renderPassword'    => true,
                'value'             => $this->resource->get('bind_pw', 'admin'),
                'helptext'          => 'The password to use for querying the ldap server',
                'required'          => true
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
            'resource_type',
            array(
                'label'         =>  'Resource Type',
                'value'         =>  $this->resource->type,
                'required'      =>  true,
                'helptext'      =>  'Choose the type of resource you want to create.',
                'multiOptions'  =>  array(
                    'db'            => 'SQL Database',
                    'ldap'          => 'Ldap',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus'
                )
            )
        );
        $this->enableAutoSubmit(array('resource_type'));
    }

    public function create()
    {
        $this->addTypeSelectionBox();
        switch ($this->getRequest()->getParam('resource_type', $this->resource->type)) {
            case 'db':
                break;
            case 'statusdat':
                break;
            case 'livestatus':
                break;
            case 'ldap':
                break;
        }
        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }
}
