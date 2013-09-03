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


namespace Icinga\Module\Monitoring\Form\Config;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    /**
     * The value of the target to remove
     *
     * @var string
     */
    private $removeTarget;

    /**
     * The name of the target parameter to remove
     *
     * @var string
     */
    private $targetName;

    /**
     * Set the remove target in this field to be a hidden field with $name and value $target
     *
     * @param string $name      The name to be set in the hidden field
     * @param string $target    The value to be set in the hidden field
     */
    public function setRemoveTarget($name, $target)
    {
        $this->targetName = $name;
        $this->removeTarget = $target;
    }

    /**
     * Create the confirmation form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->addElement(
            'hidden',
            $this->targetName,
            array(
                'value'     => $this->removeTarget,
                'required'  => true
            )
        );
        $this->setSubmitLabel('{{REMOVE_ICON}} Confirm Removal');
    }
}
