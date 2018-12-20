<?php

namespace Icinga\Controllers;

use Icinga\Web\Controller;

class ModalController extends Controller
{
    public function indexAction()
    {
    }

    public function contentAction()
    {
        $this->getResponse()->setHeader('X-Icinga-History', 'no', true);
    }
}
