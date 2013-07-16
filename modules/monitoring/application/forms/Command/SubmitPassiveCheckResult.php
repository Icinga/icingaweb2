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

use Icinga\Exception\ProgrammingError;

/**
 * Form for command SubmitPassiveCheckResult
 */
class SubmitPassiveCheckResult extends AbstractCommand
{
    /**
     * Type constant for host form
     */
    const TYPE_HOST = 'host';

    /**
     * Type constant for service form
     */
    const TYPE_SERVICE = 'service';

    private static $options = array();

    /**
     * Type of form
     * @var string
     */
    private $type;

    /**
     * Setup plugin states
     */
    public function init()
    {
        if (!count(self::$options)) {
            self::$options = array(
                self::TYPE_HOST => array(
                    0 => t('UP'),
                    1 => t('DOWN'),
                    2 => t('UNREACHABLE')
                ),
                self::TYPE_SERVICE => array(
                    0 => t('OK'),
                    1 => t('WARNING'),
                    2 => t('CRITICAL'),
                    3 => t('UNKNOWN')
                )
            );
        }

        parent::init();
    }


    /**
     * Setter for type
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Getter for type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return array of options
     * @return array
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function getOptions()
    {
        if (in_array($this->getType(), array(self::TYPE_HOST, self::TYPE_SERVICE)) === false) {
            throw new ProgrammingError('Type is not valid');
        }

        return self::$options[$this->getType()];
    }

    /**
     * Interface method to build the form
     * @see Form::create()
     */
    protected function create()
    {

        $this->addElement(
            'select',
            'pluginstate',
            array(
                'label'        => t('Plugin state'),
                'multiOptions' => $this->getOptions()
            )
        );

        $this->addElement(
            'textarea',
            'checkoutput',
            array(
                'label' => t('Check output'),
                'rows'  => 2
            )
        );

        $this->addElement(
            'textarea',
            'performancedata',
            array(
                'label' => t('Performance data'),
                'rows' => 2
            )
        );

        $this->setSubmitLabel(t('Submit passive check result'));

        parent::create();
    }
}
