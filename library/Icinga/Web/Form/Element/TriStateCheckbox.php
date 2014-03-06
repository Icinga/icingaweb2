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

namespace Icinga\Web\Form\Element;

use \Icinga\Web\Form\Validator\TriStateValidator;
use \Zend_Form_Element_Xhtml;

/**
 * A checkbox that can display three different states:
 * true, false and mixed. When there is no JavaScript
 * available to display the checkbox properly, a radio
 * button-group with all three possible states will be
 * displayed.
 */
class TriStateCheckbox extends Zend_Form_Element_Xhtml
{
    /**
     * Name of the view helper
     *
     * @var string
     */
    public $helper = 'formTriStateCheckbox';

    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);

        $this->triStateValidator = new TriStateValidator($this->patterns);
        $this->addValidator($this->triStateValidator);
    }
}
