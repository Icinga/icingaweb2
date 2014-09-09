<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\MenuRenderer;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Hook;
use Icinga\Web\Menu;
use Icinga\Web\Url;

/**
 * Create complex layout parts
 */
class LayoutController extends ActionController
{
    /**
     * Render the menu
     */
    public function menuAction()
    {
        $this->setAutorefreshInterval(15);
        $this->_helper->layout()->disableLayout();

        $url = Url::fromRequest();
        $menu = new MenuRenderer(Menu::load(), $url->getRelativeUrl());
        $this->view->menuRenderer = $menu->useCustomRenderer();
    }

    /**
     * Render the top bar
     */
    public function topbarAction()
    {
        $topbarHtmlParts = array();

        /** @var Hook\TopBarHook $hook */
        $hook = null;

        foreach (Hook::all('TopBar') as $hook) {
            $topbarHtmlParts[] = $hook->getHtml($this->getRequest());
        }

        $this->view->topbarHtmlParts = $topbarHtmlParts;


        $this->renderScript('parts/topbar.phtml');
    }
}
