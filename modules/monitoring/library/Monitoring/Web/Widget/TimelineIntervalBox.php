<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Widget\AbstractWidget;

/**
 * @todo Might be better if this is a generic selection widget.
 */
class TimelineIntervalBox extends AbstractWidget
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
     */
    public function __construct($name, array $values)
    {
        $this->name = $name;
        $this->values = $values;
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
        $form->setTokenDisabled();
        $form->setName($this->name);
        $form->addElement(
            'select',
            'interval',
            array(
                'label'         => 'Timeline Interval',
                'multiOptions'  => $this->values,
                'class'         => 'autosubmit'
            )
        );

        if ($this->request) {
            $form->setAction($this->request->getRequestUri());
            $form->populate($this->request->getParams());
        }

        return $form;
    }
}
