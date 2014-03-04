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

# namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Benchmark;
use Icinga\Web\Url;

/**
 * Application wide index controller
 */
class IndexController extends ActionController
{
    /**
     * Use a default redirection rule to welcome page
     */
    public function preDispatch()
    {
        if ($this->getRequest()->getActionName() !== 'welcome') {
            $url = Url::fromPath('dashboard');
            $render = $this->_request->getParam('_render');
            if ($render) {
                $url->setParam('_render', $render);
            }
            $this->redirectNow($url);
        }
    }

    /**
     * Application's start page
     */
    public function welcomeAction()
    {
    }
}
// @codingStandardsIgnoreEnd
