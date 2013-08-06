<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form;

use Icinga\Web\Form;
use Icinga\Web\Session;
use Icinga\Web\Notification;
use Icinga\Config\Config;

/**
 * Class SettingsForm
 * @package Icinga\Web\Form
 */
class SettingsForm extends Form
{
    /**
     *
     */
    public function onSuccess()
    {
        $session = Session::getInstance();
        $values = $this->getValues();
        $session->language = $values['language'];
        $session->backend = $values['backend'];
        $session->show_benchmark = (bool)$values['show_benchmark'];
    }

    /**
     * @return array
     */
    public function elements()
    {

        $all_backends = Config::getInstance()->listAll('backends');
        $language = \Icinga\Web\Session::getInstance()->language;
        if (!$language) {
            $language = 'en_US';
        }
        return array(
            'backend' => array(
                'select',
                array(
                    'label' => 'Backend',
                    'required' => true,
                    'value' => Session::getInstance()->backend,
                    'multiOptions' => array_combine($all_backends, $all_backends)
                )
            ),
            'language' => array(
                'select',
                array(
                    'label' => 'Language',
                    'required' => true,
                    'value' => $language,
                    'multiOptions' => array(
                        'de_DE' => 'Deutsch',
                        'en_US' => 'Englisch'
                    )
                )
            ),
            'show_benchmark' => array(
                'checkbox',
                array(
                    'label' => 'Show Benchmarks'
                )
            ),
            'submit' => array(
                'submit',
                array(
                    'label' => t('Apply')
                )
            )

        );
    }
}
