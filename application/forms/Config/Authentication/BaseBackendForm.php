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

namespace Icinga\Form\Config\Authentication;

use \Zend_Config;
use \Icinga\Web\Form\Decorator\HelpText;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Web\Form;

/**
 * Base form for authentication backend forms
 */
abstract class BaseBackendForm extends Form
{
    /**
     * The name of the backend currently displayed in this form
     *
     * Will be the section in the authentication.ini file
     *
     * @var string
     */
    private $backendName = '';

    /**
     * The backend configuration as a Zend_Config object
     *
     * @var Zend_Config
     */
    private $backend;

    /**
     * The resources to use instead of the factory provided ones (use for testing)
     *
     * @var Zend_Config
     */
    private $resources;

    /**
     * Set the name of the currently displayed backend
     *
     * @param string $name The name to be stored as the section when persisting
     */
    public function setBackendName($name)
    {
        $this->backendName = $name;
    }

    /**
     * Return the backend name of this form
     *
     * @return string
     */
    public function getBackendName()
    {
        return $this->backendName;
    }

    /**
     * Return the backend configuration or a empty Zend_Config object if none is given
     *
     * @return Zend_Config
     */
    public function getBackend()
    {
        return ($this->backend !== null) ? $this->backend : new Zend_Config(array());
    }

    /**
     * Set the backend configuration for initial population
     *
     * @param Zend_Config $backend The backend to display in this form
     */
    public function setBackend(Zend_Config $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     * (used for testing)
     *
     * @param array $resources The resources to use for populating the db selection field
     */
    public function setResources(array $resources)
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
     * Add checkbox at the beginning of the form which allows to skip logic connection validation
     */
    private function addForceCreationCheckbox()
    {
        $checkbox = new \Zend_Form_Element_Checkbox(
            array(
                'name'      =>  'backend_force_creation',
                'label'     =>  'Force Changes',
                'helptext'  =>  'Check this box to enforce changes without connectivity validation',
                'order'     =>  0
            )
        );
        $checkbox->addDecorator(new HelpText());
        $this->addElement($checkbox);
    }

    /**
     * Validate this form with the Zend validation mechanism and perform a logic validation of the connection.
     *
     * If logic validation fails, the 'backend_force_creation' checkbox is prepended to the form to allow users to
     * skip the logic connection validation.
     *
     * @param array $data       The form input to validate
     *
     * @return bool             True when validation succeeded, false if not
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }
        if ($this->getRequest()->getPost('backend_force_creation')) {
            return true;
        }
        if (!$this->isValidAuthenticationBackend()) {
            $this->addForceCreationCheckbox();
            return false;
        }
        return true;
    }

    /**
     * Return an array containing all sections defined by this form as the key and all settings
     * as an key-value sub-array
     *
     * @return array
     */
    abstract public function getConfig();

    /**
     * Validate the configuration state of this backend with the concrete authentication backend.
     *
     * An implementation should not throw any exception, but use the add/setErrorMessages method of
     * Zend_Form. If the 'backend_force_creation' checkbox is set, this method won't be called.
     *
     * @return bool         True when validation succeeded, otherwise false
     */
    abstract public function isValidAuthenticationBackend();
}
