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

namespace Monitoring\Form\Command;

use Zend_Form_Element_Hidden;

/**
 * For for command CustomNotification
 */
class CustomNotification extends AbstractCommand
{
    /**
     * Interface method to build the form
     * @see Form::create()
     */
    protected function create()
    {
        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label' => t('Comment'),
                'rows'  => 4
            )
        );

        $this->addElement(
            'checkbox',
            'force',
            array(
                'label' => t('Forced')
            )
        );

        $this->addElement(
            'checkbox',
            'broadcast',
            array(
                'label' => t('Broadcast')
            )
        );

        $this->setSubmitLabel(t('Send custom notification'));

        parent::create();
    }
}
