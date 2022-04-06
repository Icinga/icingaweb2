<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Common\BaseDashboard;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Html\Contract\FormElement;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

/**
 * Base Form for all kinds of dashboard types
 */
abstract class BaseDashboardForm extends CompatForm
{
    const CREATE_NEW_HOME = 'Create new Home';

    const CREATE_NEW_PANE = 'Create new Dashboard';

    /**
     * Dashboard instance for which this form is being rendered
     *
     * @var Dashboard
     */
    protected $dashboard;

    /**
     * Create a new Dashboard Form
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        // This is needed for the modal views
        $this->setAction((string) Url::fromRequest());
    }

    public function hasBeenSubmitted()
    {
        // We don't use addElement() for the form controls, so the form has no way of knowing
        // that we do have a submit button and will always be submitted with autosubmit elements
        return $this->hasBeenSent() && $this->getPopulatedValue('submit');
    }

    /**
     * Populate form data from config
     *
     * @param BaseDashboard $dashboard
     *
     * @return void
     */
    public function load(BaseDashboard $dashboard)
    {
    }

    /**
     * Create custom form controls
     *
     * @return HtmlElement
     */
    protected function createFormControls()
    {
        return HtmlElement::create('div', ['class' => 'control-group form-controls']);
    }

    /**
     * Create a cancel button
     *
     * @return FormElement
     */
    protected function createCancelButton()
    {
        return $this->createElement('submitButton', 'btn_cancel', ['class' => 'modal-cancel', 'label' => t('Cancel')]);
    }

    /**
     * Create a remove button
     *
     * @param Url $action
     * @param string $label
     *
     * @return FormElement
     */
    protected function createRemoveButton(Url $action, $label)
    {
        return $this->createElement('submitButton', 'btn_remove', [
            'class'      => 'remove-button',
            'label'      => [new Icon('trash'), $label],
            'formaction' => (string) $action
        ]);
    }

    /**
     * Create and register a submit button
     *
     * @param string $label
     *
     * @return FormElement
     */
    protected function registerSubmitButton($label)
    {
        $submitElement = $this->createElement('submit', 'submit', ['class' => 'btn-primary', 'label' => $label]);
        $this->registerElement($submitElement);

        return $submitElement;
    }
}