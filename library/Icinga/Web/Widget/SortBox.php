<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget;

use Zend_Form_Element_Submit;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Form\Decorator\ConditionalHidden;

/**
 *  Sortbox widget
 *
 *  The "SortBox" Widget allows you to create a generic sort input for sortable views.
 *  It automatically creates a form containing a select box with all sort options and a
 *  dropbox with the sort direction. It also handles automatic submission of sorting changes and draws an additional
 *  submit button when JavaScript is disabled.
 *
 *  The constructor takes an string for the component name ad an array containing the select options, where the key is
 *  the value to be submitted and the value is the label that will be shown. You then should call applyRequest in order
 *  to  make sure the form is correctly populated when a request with a sort parameter is being made.
 *
 *  Example:
 *      <pre><code>
 *      $this->view->sortControl = new SortBox(
 *      $this->getRequest()->getActionName(),
 *          $columns
 *      );
 *      $this->view->sortControl->applyRequest($this->getRequest());
 *      </code></pre>
 * By default the sortBox uses the GET parameter 'sort' for the sorting key and 'dir' for the sorting direction
 *
 */
class SortBox extends AbstractWidget
{

    /**
     * An array containing all sort columns with their associated labels
     *
     * @var array
     */
    private $sortFields;

    /**
     * The name of the form that will be created
     *
     * @var string
     */
    private $name;

    /**
     * A request object used for initial form population
     *
     * @var \Icinga\Web\Request
     */
    private $request;

    /**
     * Create a SortBox with the entries from $sortFields
     *
     * @param string    $name           The name of the sort form
     * @param array     $sortFields     An array containing the columns and their labels to be displayed
     *                                  in the sort select box
     */
    public function __construct($name, array $sortFields)
    {
        $this->name = $name;
        $this->sortFields = $sortFields;
    }

    /**
     * Apply the parameters from the given request on this SortBox
     *
     * @param Request $request The request to use for populating the form
     */
    public function applyRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Create a submit button that is hidden via the ConditionalDecorator
     * in order to allow sorting changes to be submitted in a JavaScript-less environment
     *
     * @return  Zend_Form_Element_Submit    The submit button that is hidden by default
     * @see     ConditionalDecorator
     */
    private function createFallbackSubmitButton()
    {
        $manualSubmitButton = new Zend_Form_Element_Submit(
            array(
                'name'      => 'submit_' . $this->name,
                'label'     => 'Sort',
                'class'     => '',
                'condition' => 0,
                'value'     => '{{SUBMIT_ICON}}'
            )
        );
        $manualSubmitButton->addDecorator(new ConditionalHidden());
        $manualSubmitButton->setAttrib('addLabelPlaceholder', true);
        return $manualSubmitButton;
    }

    /**
     * Renders this widget via the given view and returns the
     * HTML as a string
     *
     * @return  string
     */
    public function render()
    {
        $form = new Form();
        $form->setAttrib('class', 'inline');
        $form->setMethod('POST');
        $form->setTokenDisabled();
        $form->setName($this->name);
        $form->addElement('select', 'sort', array(
            'label'         => 'Sort By',
            'multiOptions'  => $this->sortFields,
            'style'         => 'width: 12em',
            'autosubmit'    => true
        ));
        $form->addElement('select', 'dir', array(
            'multiOptions'  => array(
                'asc'       => 'Asc',
                'desc'      => 'Desc',
            ),
            'style'         => 'width: 5em',
            'autosubmit'    => true
        ));
        $sort = $form->getElement('sort')->setDecorators(array('ViewHelper'));
        $dir = $form->getElement('dir')->setDecorators(array('ViewHelper'));
        if ($this->request) {
            $form->populate($this->request->getParams());
        }
        return $form;
    }
}
