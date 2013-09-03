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

/**
 * Extended EditBackendForm for creating new Backends
 *
 * @see EditBackendForm
 */
class CreateBackendForm extends EditBackendForm
{
    /**
     * Create this form
     *
     * @see EditBackendForm::create()
     */
    public function create()
    {
        $this->setBackendConfiguration(new Zend_Config(array('type' => 'ido')));
        $this->addElement(
            'text',
            'backend_name',
            array(
                'label'     =>  'Backend Name',
                'required'  =>  true,
                'helptext'  =>  'This will be the identifier of this backend'
            )
        );
        parent::create();
    }

    /**
     * Return the name of the backend that is to be created
     *
     * @return string The name of the backend as entered in the form
     */
    public function getBackendName()
    {
        return $this->getValue('backend_name');
    }
}
