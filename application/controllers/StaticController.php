<?php
// @codingStandardsIgnoreStart
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

# namespace Icinga\Web\Form;

use Icinga\Web\ActionController;
use Icinga\Application\Icinga;
use Zend_Controller_Action_Exception as ActionException;

use Icinga\Application\Benchmark;
/**
 * Class StaticController
 * @package Icinga\Web\Form
 */
class StaticController extends ActionController
{

    /**
     * @var bool
     */
    protected $handlesAuthentication = true;

    /**
     *
     */
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    /**
     * @return array
     */
    private function getModuleList()
    {
        $modules = Icinga::app()->moduleManager()->getLoadedModules();

        // preliminary static definition
        $result = array();
        foreach ($modules as $name => $module) {
            $hasJs = file_exists($module->getBasedir() . "/public/js/$name.js");
            $result[] = array(
                'name' => $name,
                'active' => true,
                'type' => 'generic',
                'behaviour' => $hasJs
            );
        }
        return $result;
    }

    /**
     *
     */
    public function modulelistAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        $this->getResponse()->setHeader("Content-Type", "application/json");
        echo "define(function() { return " . json_encode($this->getModuleList(), true) . "; })";
        exit;
    }

    /**
     * @throws \Zend_Controller_Action_Exception
     */
    public function javascriptAction()
    {
        $module = $this->_getParam('module_name');
        $file = $this->_getParam('file');
        $basedir = Icinga::app()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/js/' . $file;
        if (!file_exists($filePath)) {
            throw new ActionException(
                sprintf(
                    '%s does not exist',
                    $filePath
                ),
                404
            );
        }
        $hash = md5_file($filePath);
        $response = $this->getResponse();
        $response->setHeader('ETag', $hash);
        $response->setHeader('Content-Type', 'application/javascript');
        $response->setHeader('Cache-Control', 'max-age=3600', true);
        $response->setHeader(
            'Last-Modified',
            gmdate(
                'D, d M Y H:i:s',
                filemtime($filePath)
            ) . ' GMT'
        );

        $hash = md5_file($filePath);

        if ($hash === $this->getRequest()->getHeader('If-None-Match')) {
            $response->setHttpResponseCode(304);
            return;
        } else {
            readfile($filePath);
        }
        return;
    }
}

// @codingStandardsIgnoreEnd
