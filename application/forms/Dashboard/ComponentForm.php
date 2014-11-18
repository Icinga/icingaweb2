<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Config;
use Icinga\Exception\ProgrammingError;
use Icinga\File\Ini\IniWriter;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Widget\Dashboard\Component;

/**
 * Form to add an url a dashboard pane
 */
class ComponentForm extends Form
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
            $this->setSubmitLabel(t('Add To Dashboard'));
        }
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
            'org_component',
            array(
                'required' => false
            )
        );

        $this->addElement(
            'text',
            'url',
            array(
                'required'      => true,
                'label'         => t('Url'),
                'description'   =>
                    t('Enter url being loaded in the dashlet. You can paste the full URL, including filters.')
            )
        );
        $this->addElement(
            'text',
            'component',
            array(
                'required'  => true,
                'label'     => t('Dashlet Title'),
                'description'  => t('Enter a title for the dashlet.')
            )
        );
        if (empty($panes) ||
            ((isset($formData['create_new_pane']) && $formData['create_new_pane'] != false) &&
             (false === isset($formData['use_existing_dashboard']) || $formData['use_existing_dashboard'] != true))
        ) {
            $groupElements[] = $this->createElement(
                'text',
                'pane',
                array(
                    'required'      => true,
                    'label'         => t("New Pane Title"),
                    'description'   =>
                        t('Enter a title for the new pane.')
                )
            );
            $groupElements[] = $this->createElement( // Prevent the button from being displayed again on validation errors
                'hidden',
                'create_new_pane',
                array(
                    'value' => 1
                )
            );
            if (false === empty($panes)) {
                $buttonExistingPane = $this->createElement(
                    'submit',
                    'use_existing_dashboard',
                    array(
                        'ignore'        => true,
                        'label'         => t('Use An Existing Pane'),
                        'description'   =>
                            t('Click on the button to add the dashlet to an existing pane on your dashboard.')
                    )
                );
                $buttonExistingPane->removeDecorator('Label');
                $groupElements[] = $buttonExistingPane;
            }
        } else {
            $groupElements[] = $this->createElement(
                'select',
                'pane',
                array(
                    'required'      => true,
                    'label'         => t('Pane'),
                    'multiOptions'  => $panes,
                    'description'   =>
                        t('Select a pane you want to add the dashlet.')
                )
            );
            $buttonNewPane = $this->createElement(
                'submit',
                'create_new_pane',
                array(
                    'ignore'        => true,
                    'label'         => t('Create A New Pane'),
                    'description'   =>
                        t('Click on the button if you want to add the dashlet to a new pane on the dashboard.')
                )
            );
            $buttonNewPane->removeDecorator('Label');
            $groupElements[] = $buttonNewPane;
        }
        $this->addDisplayGroup(
            $groupElements,
            'pane_group',
            array(
                'legend'        => t('Pane'),
                'description'   => t(
                    'Decide if you want add the dashlet to an existing pane'
                    . ' or create a new pane. Have a look on the button below.'
                ),
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group')),
                    array(
                        'Description',
                        array('tag' => 'span', 'class' => 'description', 'placement' => 'prepend')
                    ),
                    'Fieldset'
                )
            )
        );
    }

    /**
     * Adjust preferences and persist them
     *
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        return true;
    }

    /**
     * Populate data if any
     *
     * @see Form::onRequest()
     */
    public function onRequest(Request $request)
    {
        return true;
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
     * @param Component $component
     */
    public function load(Component $component)
    {
        $this->populate(array(
            'pane'          => $component->getPane()->getName(),
            'org_pane'      => $component->getPane()->getName(),
            'component'     => $component->getTitle(),
            'org_component' => $component->getTitle(),
            'url'           => $component->getUrl()
        ));
    }
}
