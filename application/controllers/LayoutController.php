<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\ActionController;
use Icinga\Web\Menu;
use Icinga\Web\Url;

class LayoutController extends ActionController
{
    public function menuAction()
    {
        $this->view->url    = Url::fromRequest()->getRelativeUrl();
        $this->view->items  = Menu::fromConfig()->getChildren();
        $this->view->sub    = false;
    }
}
// @codingStandardsIgnoreEnd
