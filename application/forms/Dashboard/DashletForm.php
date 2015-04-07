<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard\Dashlet;

/**
 * Form to add an url a dashboard pane
 */
class DashletForm extends Form
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_dashboard_addurl');
        if (! $this->getSubmitLabel()) {
            $this->setSubmitLabel($this->translate('Add To Dashboard'));
        }
        $this->setAction(URL::fromRequest());
    }

    /**
     * Build AddUrl form elements
     *
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $groupElements  = array();
        $panes          = array();

        if ($this->dashboard) {
            $panes = $this->dashboard->getPaneKeyTitleArray();
        }

        $this->addElement(
            'hidden',
            'org_pane',
            array(
                'required' => false
            )
        );

        $this->addElement(
            'hidden',
            'org_dashlet',
            array(
                'required' => false
            )
        );

        $this->addElement(
            'text',
            'url',
            array(
                'required'      => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate(
                    'Enter url being loaded in the dashlet. You can paste the full URL, including filters.'
                )
            )
        );
        $this->addElement(
            'text',
            'dashlet',
            array(
                'required'      => true,
                'label'         => $this->translate('Dashlet Title'),
                'description'   => $this->translate('Enter a title for the dashlet.')
            )
        );
        $this->addElement(
            'note',
            'note',
            array(
                'decorators' => array(
                    array('HtmlTag', array('tag' => 'hr'))
                )
            )
        );
        if (empty($panes) || ((isset($formData['create_new_pane']) && $formData['create_new_pane'] != false))) {
            $this->addElement(
                'text',
                'pane',
                array(
                    'required'      => true,
                    'label'         => $this->translate("New Dashboard Title"),
                    'description'   => $this->translate('Enter a title for the new pane.')
                )
            );
        } else {
            $this->addElement(
                'select',
                'pane',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Dashboard'),
                    'multiOptions'  => $panes,
                    'description'   => $this->translate('Select a pane you want to add the dashlet.')
                )
            );
        }

        $this->addElement(
            'checkbox',
            'create_new_pane',
            array(
                'autosubmit'    => true,
                'required'      => false,
                'label'         => $this->translate('New dashboard'),
                'description'   => $this->translate('Check this box if you want to add the dashlet to a new dashboard')
            )
        );
    }

    /**
     * @param \Icinga\Web\Widget\Dashboard $dashboard
     */
    public function setDashboard(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @return \Icinga\Web\Widget\Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @param Dashlet $dashlet
     */
    public function load(Dashlet $dashlet)
    {
        $this->populate(array(
            'pane'          => $dashlet->getPane()->getName(),
            'org_pane'      => $dashlet->getPane()->getName(),
            'dashlet'       => $dashlet->getTitle(),
            'org_dashlet'   => $dashlet->getTitle(),
            'url'           => $dashlet->getUrl()->getRelativeUrl()
        ));
    }
}
