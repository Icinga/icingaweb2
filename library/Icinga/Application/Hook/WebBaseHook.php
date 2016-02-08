<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Hook;

use Zend_Controller_Action_HelperBroker;
use Zend_View;

/**
 * Base class for web hooks
 *
 * The class provides access to the view
 */
class WebBaseHook
{
    /**
     * View instance
     *
     * @var Zend_View
     */
    private $view;

    /**
     * Set the view instance
     *
     * @param Zend_View $view
     */
    public function setView(Zend_View $view)
    {
        $this->view = $view;
    }

    /**
     * Get the view instance
     *
     * @return Zend_View
     */
    public function getView()
    {
        if ($this->view === null) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if ($viewRenderer->view === null) {
                $viewRenderer->initView();
            }
            $this->view = $viewRenderer->view;
        }

        return $this->view;
    }
}
