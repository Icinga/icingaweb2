<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Doc\Controllers;

use Icinga\Application\Icinga;
use Icinga\Web\Controller;
use Icinga\Web\Widget;

class StyleController extends Controller
{
    public function guideAction()
    {
        $this->view->tabs = $this->tabs()->activate('guide');
    }

    public function fontAction()
    {
        $this->view->tabs = $this->tabs()->activate('font');
        $confFile = Icinga::app()->getApplicationDir('fonts/fontello-ifont/config.json');
        $this->view->font = json_decode(file_get_contents($confFile));
    }

    protected function tabs()
    {
        return Widget::create('tabs')->add(
            'guide',
            [
                'label' => $this->translate('Style Guide'),
                'url'   => 'doc/style/guide'
            ]
        )->add(
            'font',
            [
                'label' => $this->translate('Icons'),
                'title' => $this->translate('List all available icons'),
                'url'   => 'doc/style/font'
            ]
        );
    }
}
