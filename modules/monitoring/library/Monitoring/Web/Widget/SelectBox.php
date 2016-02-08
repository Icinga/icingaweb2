<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Widget\AbstractWidget;

class SelectBox extends AbstractWidget
{
    /**
     * The name of the form that will be created
     *
     * @var string
     */
    private $name;

    /**
     * An array containing all intervals with their associated labels
     *
     * @var array
     */
    private $values;

    /**
     * The label displayed next to the select box
     *
     * @var string
     */
    private $label;

    /**
     * The name of the url parameter to set
     *
     * @var string
     */
    private $parameter;

    /**
     * A request object used for initial form population
     *
     * @var Request
     */
    private $request;

    /**
     * Create a TimelineIntervalBox
     *
     * @param   string  $name       The name of the form that will be created
     * @param   array   $values     An array containing all intervals with their associated labels
     * @param   string  $label      The label displayed next to the select box
     * @param   string  $param      The request parameter name to set
     */
    public function __construct($name, array $values, $label = 'Select', $param = 'selection')
    {
        $this->name = $name;
        $this->values = $values;
        $this->label = $label;
        $this->parameter = $param;
    }

    /**
     * Apply the parameters from the given request on this widget
     *
     * @param   Request     $request    The request to use for populating the form
     */
    public function applyRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Return the chosen interval value or null
     *
     * @param   Request     $request    The request to fetch the value from
     *
     * @return  string|null
     */
    public function getInterval(Request $request = null)
    {
        if ($request === null && $this->request) {
            $request = $this->request;
        }

        if ($request) {
            return $request->getParam('interval');
        }
    }

    /**
     * Renders this widget and returns the HTML as a string
     *
     * @return  string
     */
    public function render()
    {
        $form = new Form();
        $form->setAttrib('class', 'inline');
        $form->setMethod('GET');
        $form->setUidDisabled();
        $form->setTokenDisabled();
        $form->setName($this->name);
        $form->addElement(
            'select',
            $this->parameter,
            array(
                'label'         => $this->label,
                'multiOptions'  => $this->values,
                'autosubmit'    => true
            )
        );

        if ($this->request) {
            $form->populate($this->request->getParams());
        }

        return $form;
    }
}
