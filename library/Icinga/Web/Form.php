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

namespace Icinga\Web;

use Zend_Form;
use Zend_Controller_Front as Front; // TODO: Get from App
use Zend_Controller_Action_HelperBroker as ZfActionHelper;

/**
 * Class Form
 * @package Icinga\Web
 */
class Form extends Zend_Form
{
    protected $request;

    /**
     * @param array $options[optional]
     * @internal param \Icinga\Web\Zend_Controller_Request_Abstract $request
     */
    public function __construct($options = null)
    {
        /*
        if (isset($options['prefill'])) {
            $this->_prefill = $options['prefill'];
            unset($options['prefill']);
        }
        */
        $this->request = Front::getInstance()->getRequest();
        // $this->handleRequest();
        foreach ($this->elements() as $key => $values) {
            $this->addElement($values[0], $key, $values[1]); // do it better!
        }

        // Should be replaced with button check:
        $this->addElement('hidden', '__submitted');
        $this->setDefaults(array('__submitted' => 'true'));

        parent::__construct($options);
        if ($this->getAttrib('action') === null) {
            $this->setAction($this->request->getRequestUri());
        }
        if ($this->getAttrib('method') === null) {
            $this->setMethod('post');
        }
        if ($this->hasBeenSubmitted()) {
            $this->handleRequest();
        }
    }

    public function redirectNow($url)
    {
        ZfActionHelper::getStaticHelper('redirector')
            ->gotoUrlAndExit($url);
    }

    public function handleRequest()
    {
        if ($this->isValid($this->request->getPost())) {
            $this->onSuccess();
        } else {
            $this->onFailure();
        }
    }

    public function onSuccess()
    {
    }

    public function onFailure()
    {
    }

    public function hasBeenSubmitted()
    {
        return $this->request->getPost('__submitted', 'false') === 'true';
    }

    public function elements()
    {
        return array();
    }
}
