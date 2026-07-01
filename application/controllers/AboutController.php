<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Web\Controller;

class AboutController extends Controller
{
    public function indexAction()
    {
        $this->view->version = Version::get();
        $this->view->libraries = Icinga::app()->getLibraries();
        $this->view->modules = Icinga::app()->getModuleManager()->getLoadedModules();
        $this->view->title = $this->translate('About');
        $this->view->tabs = $this->getTabs()->add(
            'about',
            [
                'label' => $this->translate('About'),
                'title' => $this->translate('About Icinga Web 2'),
                'url'   => 'about'
            ]
        )->activate('about');
    }
}
