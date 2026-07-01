<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Doc\Controllers;

use Icinga\Module\Doc\DocController;
use Icinga\Web\Url;

/**
 * Documentation module index
 */
class IndexController extends DocController
{
    /**
     * Documentation module landing page
     *
     * Lists documentation links
     */
    public function indexAction()
    {
        $this->getTabs()->add('documentation', [
            'active'    => true,
            'title'     => $this->translate('Documentation', 'Tab title'),
            'url'       => Url::fromRequest()
        ]);
    }
}
