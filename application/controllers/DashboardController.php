<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotReadableError;
use Icinga\File\Ini\IniWriter;
use Icinga\Forms\Dashboard\AddUrlForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;

/**
 * Handle creation, removal and displaying of dashboards, panes and components
 *
 * @see Icinga\Web\Widget\Dashboard for more information about dashboards
 */
class DashboardController extends ActionController
{
    /**
     * Display the form for adding new components or add the new component if submitted
     */
    public function addurlAction()
    {
        $this->getTabs()->add(
            'addurl',
            array(
                'title' => 'Add Dashboard URL',
                'url' => Url::fromRequest()
            )
        )->activate('addurl');
        $form = new AddUrlForm();
        $form->handleRequest();
        $this->view->form = $form;
    }

    /**
     * Display the dashboard with the pane set in the 'pane' request parameter
     *
     * If no pane is submitted or the submitted one doesn't exist, the default pane is
     * displayed (normally the first one)
     */
    public function indexAction()
    {
        $dashboard = new Dashboard();
        $dashboard->setUser($this->getRequest()->getUser());
        $dashboard->load();

        if (! $dashboard->hasPanes()) {
            $this->view->title = 'Dashboard';
        } else {
            if ($this->_getParam('pane')) {
                $pane = $this->_getParam('pane');
                $dashboard->activate($pane);
            }

            if ($dashboard === null) {
                $this->view->title = 'Dashboard';
            } else {
                $this->view->title = $dashboard->getActivePane()->getTitle() . ' :: Dashboard';
                $this->view->tabs = $dashboard->getTabs();

                /* Temporarily removed
                $this->view->tabs->add(
                    'Add',
                    array(
                        'title' => '+',
                        'url' => Url::fromPath('dashboard/addurl')
                    )
                );
                */

                $this->view->dashboard = $dashboard;
            }
        }
    }
}
