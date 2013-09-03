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


namespace Icinga\Module\Monitoring\Form\Config\Instance;

use \Icinga\Web\Form;
use \Zend_Config;

/**
 * Form for creating new instances
 *
 * @see EditInstanceForm
 */
class CreateInstanceForm extends EditInstanceForm
{

    /**
     * Create the form elements
     *
     * @see EditInstanceForm::create()
     */
    public function create()
    {
        $this->setInstanceConfiguration(new Zend_Config(array()));
        $this->addElement(
            'text',
            'instance_name',
            array(
                'label'     =>  'Instance Name',
                'helptext'  =>  'Please enter the name for the instance to create'
            )
        );
        parent::create();
    }

    /**
     * Return the name of the instance to be created
     *
     * @return string The name of the instance as entered in the form
     */
    public function getInstanceName()
    {
        return $this->getValue('instance_name');
    }
}
