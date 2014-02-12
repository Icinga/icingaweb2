<?php
// @codingStandardsIgnoreStart
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

class Zend_Controller_Action_Helper_DataFormatSwitch extends Zend_Controller_Action_Helper_ContextSwitch {

    protected $autoSerialization = true;

    public function setAutoJsonSerialization($value)
    {
        $this->autoSerialization = $value;
        $this->setAutoJsonSerialization($value);
    }

    public function __construct()
    {
          $this->setContexts(
            array(
                'pdf' => array(
                    'suffix'    => 'pdf',
                    'headers'   => array('Content-Type' => 'application/pdf'),
                    'callbacks' => array(
                        'init'  => 'removeStyles',
                        'post'  => 'postPdfContext'
                    )
                ),
                'json' => array(
                    'suffix'    => 'json',
                    'headers'   => array('Content-Type' => 'application/json'),
                    'callbacks' => array(
                         'init' => 'removeStyles',
                         'post' => 'postJsonContext'
                    )
                ),
                'xml' => array(
                    'suffix'    => 'xml',
                    'headers'   => array('Content-Type' => 'application/xml'),
                    'callbacks' => array(
                         'init' => 'removeStyles',
                         'post' => 'postXmlContext'
                    )
                )
            )
        );
    }

    private function postXmlContext()
    {
        if (!$this->autoSerialization) {
            return;
        }
    }

    private function postPdfContext()
    {
        if (!$this->autoSerialization) {
            return;
        }
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $helper = new Zend_View_Helper_Pdf();
        $this->getResponse()->setBody(
            $helper->pdf($viewRenderer->render())
        );
    }

    private function removeStyles()
    {
        if (!$this->getAutoJsonSerialization()) {
            return;
        }
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        if ($view instanceof Zend_View_Interface) {
            $viewRenderer->setNoRender(true);
        }
    }
}