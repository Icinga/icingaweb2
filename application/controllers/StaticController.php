<?php
// @codingStandardsIgnoreStart
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

use \Zend_Controller_Action_Exception as ActionException;
use \Icinga\Web\Controller\ActionController;
use \Icinga\Application\Icinga;
use \Icinga\Application\Logger;

class StaticController extends ActionController
{
    /**
     * Static routes don't require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Disable layout rendering as this controller doesn't provide any html layouts
     */
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Return an image from the application's or the module's public folder
     */
    public function imgAction()
    {
        $module = $this->_getParam('module_name');
        $file   = $this->_getParam('file');

        $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();

        $filePath = $basedir . '/public/img/' . $file;
        if (! file_exists($filePath)) {
            throw new ActionException(sprintf(
                '%s does not exist',
                $filePath
            ), 404);
        }
        if (preg_match('/\.([a-z]+)$/i', $file, $m)) {
            $extension = $m[1];
        } else {
            $extension = 'fixme';
        }
        $hash = md5_file($filePath);
        if ($hash === $this->getRequest()->getHeader('If-None-Match')) {
            $this->getResponse()->setHttpResponseCode(304);
            return;
        }
        header('ETag: ' . $hash);
        header('Content-Type: image/' . $extension);
        header('Cache-Control: max-age=3600');
        header('Last-Modified: ' . gmdate(
            'D, d M Y H:i:s',
            filemtime($filePath)
        ) . ' GMT');

        readfile($filePath);
        return;
    }

    /**
     * Return a javascript file from the application's or the module's public folder
     */
    public function javascriptAction()
    {
        $module = $this->_getParam('module_name');
        $file   = $this->_getParam('file');

        if ($module == 'app') {
            $basedir = Icinga::app()->getApplicationDir('../public/js/icinga/components/');
            $filePath = $basedir . $file;
        } else {
            if (!Icinga::app()->getModuleManager()->hasEnabled($module)) {
                Logger::error(
                    'Non-existing frontend component "' . $module . '/' . $file
                    . '" was requested. The module "' . $module . '" does not exist or is not active.');
                echo "/** Module not enabled **/";
                return;
            }
            $basedir = Icinga::app()->getModuleManager()->getModule($module)->getBaseDir();
            $filePath = $basedir . '/public/js/' . $file;
        }

        if (!file_exists($filePath)) {
            Logger::error(
                'Non-existing frontend component "' . $module . '/' . $file
                . '" was requested, which would resolve to the the path: ' . $filePath);
            echo '/** Module has no js files **/';
            return;
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
